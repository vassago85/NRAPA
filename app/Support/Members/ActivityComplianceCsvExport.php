<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\AuditLog;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Year-end compliance report CSV.
 *
 * Rows: current members with at least the activity minimum of APPROVED
 * shooting activities dated inside 1 January–31 October of the chosen year.
 *
 * Columns (confirmed with the client — matches the Excel sheet they used
 * historically, with the "Contact details" and "Membership/Status" items
 * split into two columns each so Excel can sort/filter them cleanly):
 *   - Name
 *   - Surname
 *   - ID
 *   - NRAPA Number (formatted member number, e.g. NRAPA-00042)
 *   - Phone
 *   - Email
 *   - Membership (type name)
 *   - Status (active / expired / suspended / etc.)
 *   - Paid Up & Active for {year} (Yes/No)
 *
 * Name + Surname are sourced from the verified ID document when one is on
 * file (metadata.names / metadata.surname — same source as `User::getIdName()`);
 * otherwise `users.name` is split on the last whitespace, mirroring
 * `Membership::extractSurname()`.
 *
 * "Paid Up & Active for {year}" is the overall compliance signal from
 * {@see MemberCompliance::isCompliant()} — a member who has banked their
 * activities but is still missing (say) a verified ID document shows as "No".
 * This matches what the member sees on their own dashboard, so the report
 * and the portal agree.
 */
class ActivityComplianceCsvExport
{
    public function __construct(
        private User $actor,
        private int $year,
    ) {}

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'Name',
            'Surname',
            'ID',
            'NRAPA Number',
            'Phone',
            'Email',
            'Membership',
            'Status',
            "Paid Up & Active for {$this->year}",
        ];
    }

    public static function defaultYear(): int
    {
        return (int) now()->year;
    }

    public static function requiredCount(): int
    {
        return (int) SystemSetting::get('endorsement_min_activities', 2);
    }

    public function filename(): string
    {
        return "activity-compliance-{$this->year}.csv";
    }

    public function download(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            $this->write($out);
            fclose($out);
        }, $this->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $out
     */
    public function write($out): int
    {
        $batchId = (string) Str::uuid();
        $exported = 0;

        // Pass $escape='' explicitly so we don't trip the PHP 8.4+ deprecation
        // that nags about relying on the default escape character.
        fputcsv($out, $this->headers(), ',', '"', '');

        $this->query()
            ->with(['memberships' => fn ($q) => $q->with('type')->latest('id')])
            ->chunkById(200, function ($users) use ($out, $batchId, &$exported) {
                MemberCompliance::flush();
                foreach ($users as $user) {
                    [$first, $last] = self::splitName($user);
                    $membership = $user->activeMembership;
                    $compliant = MemberCompliance::for($user)->isCompliant() ? 'Yes' : 'No';

                    fputcsv($out, [
                        $first,
                        $last,
                        $user->getIdNumber() ?? '',
                        $user->formatted_member_number,
                        $user->phone ?? '',
                        $user->email ?? '',
                        $membership?->type?->name ?? '',
                        $user->membershipState()->label(),
                        $compliant,
                    ], ',', '"', '');

                    AuditLog::log('member.activity_report_exported', $user, null, null, $this->actor, [
                        'bulk_batch' => $batchId,
                        'year' => $this->year,
                    ]);

                    $exported++;
                }
            }, 'users.id', 'id');

        return $exported;
    }

    /**
     * Current members with at least the minimum approved activities dated
     * inside the Jan–Oct window of the configured year. Deliberately scoped
     * to currently-active memberships only — the end-of-year report is "who
     * is on the books and already compliant on activities".
     */
    public function query(): Builder
    {
        $required = self::requiredCount();
        $start = Carbon::create($this->year, 1, 1)->startOfDay();
        $end = Carbon::create($this->year, 10, 31)->endOfDay();

        return User::query()
            ->where('users.role', User::ROLE_MEMBER)
            // Must have a current (active, in-force) membership.
            ->whereHas('memberships', function (Builder $q) {
                $q->where('memberships.status', 'active')
                    ->where(function (Builder $q2) {
                        $q2->whereNull('memberships.expires_at')
                            ->orWhere('memberships.expires_at', '>=', now()->startOfDay())
                            ->orWhereHas('type', fn (Builder $t) => $t->where('duration_type', 'lifetime'));
                    });
            })
            ->whereRaw(
                '(SELECT COUNT(*) FROM shooting_activities sa'
                .' WHERE sa.user_id = users.id'
                .'   AND sa.status = ?'
                .'   AND sa.activity_date BETWEEN ? AND ?) >= ?',
                ['approved', $start->toDateTimeString(), $end->toDateTimeString(), $required]
            )
            ->orderBy('users.name');
    }

    /**
     * Split a member's name into (first, last). Prefers the verified ID
     * document's `names` + `surname` metadata; falls back to splitting
     * `users.name` on the last whitespace so a middle name goes with the
     * first name block (same convention as Membership::extractSurname()).
     *
     * @return array{0: string, 1: string}
     */
    public static function splitName(User $user): array
    {
        $idDoc = $user->getVerifiedIdDocument();

        if ($idDoc && is_array($idDoc->metadata)) {
            $surname = trim((string) ($idDoc->metadata['surname'] ?? ''));
            $names = trim((string) ($idDoc->metadata['names'] ?? ''));

            if ($surname !== '' || $names !== '') {
                return [$names, $surname];
            }
        }

        $display = trim((string) $user->name);

        if ($display === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $display) ?: [$display];
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }
}
