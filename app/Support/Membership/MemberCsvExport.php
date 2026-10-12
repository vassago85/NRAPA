<?php

namespace App\Support\Membership;

use App\Enums\MembershipState;
use App\Jobs\BulkMemberAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the members index selection as CSV, chunked so a whole segment never
 * sits in memory. Every exported member gets an audit entry (POPIA).
 */
class MemberCsvExport
{
    public const HEADERS = ['Membership number', 'Name', 'Email', 'Phone', 'Membership type', 'State', 'Expires', 'Email status'];

    public function __construct(
        private User $actor,
        private ?string $segment,
        private string $search = '',
        private array $memberIds = [],
    ) {}

    public function download(): StreamedResponse
    {
        $filename = 'members-'.($this->segment ?? 'selection').'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            $this->write($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $out
     */
    public function write($out): int
    {
        $batchId = (string) Str::uuid();
        $exported = 0;
        $today = now()->startOfDay();

        fputcsv($out, self::HEADERS);

        BulkMemberAction::targets($this->segment, $this->search, $this->memberIds)
            ->select('users.*')
            ->with(['memberships' => fn ($q) => $q->with('type')->latest('id')])
            ->chunkById(500, function ($members) use ($out, $batchId, $today, &$exported) {
                foreach ($members as $member) {
                    if (Gate::forUser($this->actor)->denies('bulkManage', $member)) {
                        continue;
                    }

                    $membership = $member->memberships->first(fn ($m) => MembershipState::isCurrent($m, $today)) ?? $member->memberships->first();

                    fputcsv($out, [
                        $membership?->membership_number,
                        $member->name,
                        $member->email,
                        $member->phone,
                        $membership?->type?->name,
                        $member->membershipState()->label(),
                        $membership?->expires_at?->toDateString(),
                        $member->email_status,
                    ]);

                    AuditLog::log('member.exported', $member, null, null, $this->actor, [
                        'bulk_batch' => $batchId,
                        'action' => 'export_csv',
                        'segment' => $this->segment,
                    ]);

                    $exported++;
                }
            }, 'users.id', 'id');

        return $exported;
    }
}
