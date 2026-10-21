<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\AuditLog;
use App\Models\Membership;
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
 * Rows: every current member (role=member with a current, in-force
 * membership) — the Status, Good Standing, and Required Activities columns
 * then tell the full compliance story per row.
 *
 * Columns (confirmed with the client):
 *   - Name
 *   - Surname
 *   - ID
 *   - NRAPA Number (formatted member number, e.g. NRAPA-00042)
 *   - Phone
 *   - Email
 *   - Membership (type name)
 *   - Status (active / expired / suspended / etc.)
 *   - Good Standing (Payment) — Yes for lifetime members always; Yes for
 *     non-lifetime members whose current membership is active and in force
 *     through the end of the activity window (31 October of the chosen year).
 *   - Required Activities Compliant — Yes when the member has at least the
 *     SystemSetting-defined minimum of APPROVED shooting activities dated
 *     inside 1 January–31 October of the chosen year. Reads "Not applicable"
 *     for lifetime, honorary, and non-dedicated-status types (Basic etc.) —
 *     those memberships do not carry the activity requirement at all.
 *
 * Name + Surname are sourced from the verified ID document when one is on
 * file (metadata.names / metadata.surname — same source as `User::getIdName()`);
 * otherwise `users.name` is split on the last whitespace, mirroring
 * `Membership::extractSurname()`.
 *
 * Each row is written through to the audit log so admins can see who
 * exported what and when (POPIA).
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
            'Good Standing (Payment)',
            "Required Activities Compliant ({$this->year})",
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
        $required = self::requiredCount();
        $windowEnd = $this->activityWindowEnd();

        // Pass $escape='' explicitly so we don't trip the PHP 8.4+ deprecation
        // that nags about relying on the default escape character.
        fputcsv($out, $this->headers(), ',', '"', '');

        $this->query()
            ->with(['memberships' => fn ($q) => $q->with('type')->latest('id')])
            ->chunkById(200, function ($users) use ($out, $batchId, $required, $windowEnd, &$exported) {
                foreach ($users as $user) {
                    [$first, $last] = self::splitName($user);
                    $membership = $user->activeMembership;
                    $type = $membership?->type;
                    $activityCell = self::activitiesApplyTo($type)
                        ? ($this->approvedActivityCountFor($user) >= $required ? 'Yes' : 'No')
                        : 'Not applicable';

                    fputcsv($out, [
                        $first,
                        $last,
                        $user->getIdNumber() ?? '',
                        $user->formatted_member_number,
                        $user->phone ?? '',
                        $user->email ?? '',
                        $type?->name ?? '',
                        $user->membershipState()->label(),
                        self::goodStanding($membership, $windowEnd) ? 'Yes' : 'No',
                        $activityCell,
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
     * Every member on the books — role=member with an `active` membership
     * (lifetime or annual, regardless of whether the annual has lapsed in
     * its expires_at). We deliberately include lapsed annuals so the
     * Good Standing column can flag them as No; filtering by expires_at
     * here would hide exactly the rows admins want to see at year end.
     *
     * Suspended / revoked / applied / rejected memberships are excluded
     * — they aren't "members" for a year-end activity report.
     */
    public function query(): Builder
    {
        return User::query()
            ->where('users.role', User::ROLE_MEMBER)
            ->whereHas('memberships', function (Builder $q) {
                $q->where('memberships.status', 'active');
            })
            ->orderBy('users.name');
    }

    /**
     * The last datetime that counts toward the activity window (31 October
     * at 23:59:59.999 of the chosen year).
     */
    public function activityWindowEnd(): Carbon
    {
        return Carbon::create($this->year, 10, 31)->endOfDay();
    }

    /**
     * Whether the activity requirement applies to this membership type for
     * CSV reporting purposes. A type that does not allow dedicated status
     * (Basic, transfer-only, etc.) never carries the requirement and should
     * read "Not applicable" rather than being bucketed Yes/No.
     *
     * Lifetime and honorary types KEEP their Yes/No reading here even though
     * their compliance surface treats activities as not-applicable: the CSV
     * is a payment + activity audit, not a compliance resolver, and admins
     * have historically used the Yes/No column for lifetime/honorary members
     * too. Only switch non-dedicated types to "Not applicable".
     */
    public static function activitiesApplyTo(?\App\Models\MembershipType $type): bool
    {
        if (! $type) {
            // No membership type — behave as before and let the Yes/No column
            // compute from the (likely zero) approved count.
            return true;
        }

        return (bool) $type->allows_dedicated_status;
    }

    /**
     * "Member in good standing (payment)" per row:
     *   - Lifetime members are ALWAYS in good standing — their payment is
     *     a one-off and there is no renewal to miss.
     *   - Non-lifetime members are in good standing when their current
     *     membership is active and in force through the end of the activity
     *     window. A member whose membership expired on 30 June 2026 is NOT
     *     in good standing for the Oct-end 2026 report, even if the Status
     *     column still shows "Active" momentarily.
     */
    public static function goodStanding(?Membership $membership, Carbon $windowEnd): bool
    {
        if (! $membership) {
            return false;
        }

        if ($membership->type?->isLifetime()) {
            return true;
        }

        if ($membership->status !== 'active') {
            return false;
        }

        // No expiry on a non-lifetime membership is unusual, but if it has
        // no expiry it is effectively always current.
        if ($membership->expires_at === null) {
            return true;
        }

        return $membership->expires_at->greaterThanOrEqualTo($windowEnd);
    }

    /**
     * Approved shooting activities dated inside 1 January–31 October of
     * the configured year. Runs one COUNT per member — fine inside a 200-
     * row chunk and keeps the main query simple.
     */
    protected function approvedActivityCountFor(User $user): int
    {
        return (int) ShootingActivity::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('activity_date', [
                Carbon::create($this->year, 1, 1)->startOfDay(),
                $this->activityWindowEnd(),
            ])
            ->count();
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
