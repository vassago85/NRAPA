<?php

namespace App\Queries;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The member card and history shown beside an item in the review workspace.
 * History lists audit entries; it never totals them by actor.
 */
class ReviewContext
{
    /**
     * @return array{membership: ?Membership, status: ?string, paid_to: ?string, member_since: ?string, documents_valid: int, documents_expiring: int, endorsements: int, last_endorsement_year: ?int, hold: ?string}
     */
    public function memberCard(User $member): array
    {
        $membership = $member->activeMembership()->with('type')->first()
            ?? $member->memberships()->with('type')->latest('id')->first();

        $documents = MemberDocument::query()
            ->where('user_id', $member->id)
            ->where('status', 'verified')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', today()))
            ->selectRaw('count(*) as valid, sum(case when expires_at is not null and expires_at <= ? then 1 else 0 end) as expiring', [today()->addDays(60)])
            ->first();

        $endorsements = EndorsementRequest::query()
            ->where('user_id', $member->id)
            ->where('status', EndorsementRequest::STATUS_ISSUED)
            ->selectRaw('count(*) as total, max(issued_at) as last_issued')
            ->first();

        $firstJoined = $member->memberships()->min('applied_at') ?? $member->created_at;

        return [
            'membership' => $membership,
            'status' => $membership?->status,
            'paid_to' => $membership ? ($membership->expires_at?->format('d M Y') ?? 'Lifetime') : null,
            'member_since' => $firstJoined ? \Illuminate\Support\Carbon::parse($firstJoined)->format('d M Y') : null,
            'documents_valid' => (int) ($documents->valid ?? 0),
            'documents_expiring' => (int) ($documents->expiring ?? 0),
            'endorsements' => (int) ($endorsements->total ?? 0),
            'last_endorsement_year' => $endorsements?->last_issued ? \Illuminate\Support\Carbon::parse($endorsements->last_issued)->year : null,
            'hold' => $member->disciplinary_hold_at
                ? ($member->disciplinary_hold_reason ?: 'Since '.$member->disciplinary_hold_at->format('d M Y'))
                : null,
        ];
    }

    /**
     * Recent approval decisions on this member's items, newest first, with
     * the current item's submission as the anchor entry.
     *
     * @return Collection<int, array{label: string, at: \Carbon\CarbonInterface, by: ?string, current: bool}>
     */
    public function history(Approval $approval, int $limit = 8): Collection
    {
        $subjects = Approval::query()
            ->where('user_id', $approval->user_id)
            ->latest('submitted_at')
            ->limit(50)
            ->get(['id', 'type', 'approvable_type', 'approvable_id']);

        $byPair = $subjects->keyBy(fn (Approval $a) => $a->approvable_type.':'.$a->approvable_id);

        $entries = $subjects->isEmpty() ? collect() : AuditLog::query()
            ->where('event', 'like', 'approval.%')
            ->where(function (Builder $q) use ($subjects) {
                foreach ($subjects as $subject) {
                    $q->orWhere(fn (Builder $inner) => $inner
                        ->where('auditable_type', $subject->approvable_type)
                        ->where('auditable_id', $subject->approvable_id));
                }
            })
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (AuditLog $log) use ($byPair, $approval) {
                $subject = $byPair->get($log->auditable_type.':'.$log->auditable_id);

                return [
                    'label' => trim(($subject?->type->label() ?? 'Item').' '.self::eventLabel($log->event)),
                    'at' => $log->created_at,
                    'by' => $log->user?->name,
                    'current' => $subject?->id === $approval->id,
                ];
            });

        return collect([[
            'label' => $approval->type->label().' submitted',
            'at' => $approval->submitted_at,
            'by' => null,
            'current' => true,
        ]])->merge($entries)->sortByDesc(fn ($e) => $e['at']?->getTimestamp() ?? 0)->values();
    }

    private static function eventLabel(string $event): string
    {
        return match ($event) {
            'approval.approved' => 'approved',
            'approval.declined' => 'declined',
            'approval.changes_requested' => 'sent back for changes',
            'approval.first_signature' => 'first signature',
            'approval.closed' => 'closed',
            default => str($event)->after('approval.')->replace('_', ' ')->toString(),
        };
    }
}
