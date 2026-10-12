<?php

namespace App\Queries;

use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Models\Approval;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Every queue aggregate goes through here so the sidebar, dashboard, review
 * workspace and legacy pages always agree.
 */
class ApprovalQueue
{
    /**
     * @return Builder<Approval>
     */
    public function waitingOnAdmin(ApprovalType|string|null $type = null): Builder
    {
        return Approval::query()
            ->waitingOnAdmin()
            ->when($type, fn (Builder $q) => $q->ofType($type))
            ->orderBy('submitted_at')
            ->orderBy('id');
    }

    /**
     * Rows waiting on the member. Memberships blocked on payment are left out:
     * the payment row stands for them.
     *
     * @return Builder<Approval>
     */
    public function waitingOnMember(ApprovalType|string|null $type = null): Builder
    {
        return Approval::query()
            ->waitingOnMember()
            ->when($type, fn (Builder $q) => $q->ofType($type))
            ->where(fn (Builder $q) => $q
                ->whereNull('meta->blocked_by_payment')
                ->orWhere('meta->blocked_by_payment', false))
            ->orderByRaw('coalesce(updated_at, submitted_at)');
    }

    /**
     * Adds the chase count and last chase time for Approval::chaseSummary().
     *
     * @param  Builder<Approval>  $query
     * @return Builder<Approval>
     */
    public function withChaseSummary(Builder $query): Builder
    {
        return $query->withCount('chases')->withMax('chases', 'sent_at');
    }

    /**
     * @return Collection<int, Approval>
     */
    public function loadForDisplay(Builder $query, ?int $limit = null): Collection
    {
        return $query
            ->with(['approvable', 'user', 'firstApprover', 'decider'])
            ->when($limit, fn (Builder $q) => $q->limit($limit))
            ->get()
            ->filter(fn (Approval $a) => $a->approvable !== null)
            ->values();
    }

    /**
     * Waiting-on-admin counts for every type in one grouped query. Types
     * with nothing waiting are present with 0.
     *
     * @return array<string, int>
     */
    public function countsByType(): array
    {
        $counts = Approval::query()
            ->waitingOnAdmin()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $result = [];
        foreach (ApprovalType::cases() as $type) {
            $result[$type->value] = (int) ($counts[$type->value] ?? 0);
        }

        return $result;
    }

    public function totalWaitingOnAdmin(): int
    {
        return array_sum($this->countsByType());
    }

    /**
     * Cached total for the sidebar badge; flushed whenever an approval changes state.
     */
    public function sidebarTotal(): int
    {
        return (int) Cache::remember(Approval::CACHE_KEY_SIDEBAR, 60, fn () => $this->totalWaitingOnAdmin());
    }

    public function oldest(): ?Approval
    {
        return $this->waitingOnAdmin()->first();
    }

    public function breachCount(int $hours = Approval::SLA_HOURS): int
    {
        return Approval::query()
            ->waitingOnAdmin()
            ->where('submitted_at', '<=', now()->subHours($hours))
            ->count();
    }

    public function waitingOnMemberCount(): int
    {
        return $this->waitingOnMember()->count();
    }

    /**
     * Team-wide decisions since a point in time. Never grouped by decider.
     */
    public function clearedSince(CarbonInterface $since): int
    {
        return Approval::query()
            ->whereIn('state', [
                ApprovalState::Approved->value,
                ApprovalState::Declined->value,
                ApprovalState::ChangesRequested->value,
            ])
            ->whereNotNull('decided_by')
            ->where('decided_at', '>=', $since)
            ->count();
    }

    /**
     * Team-wide median hours from submission to decision since a point in
     * time. Computed in PHP to stay portable across MySQL and SQLite.
     */
    public function medianHoursToDecision(CarbonInterface $since): ?float
    {
        $durations = Approval::query()
            ->whereNotNull('decided_by')
            ->where('decided_at', '>=', $since)
            ->get(['submitted_at', 'decided_at'])
            ->map(fn (Approval $a) => $a->submitted_at->diffInMinutes($a->decided_at, true) / 60)
            ->sort()
            ->values();

        $count = $durations->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        return round($count % 2 ? $durations[$middle] : ($durations[$middle - 1] + $durations[$middle]) / 2, 1);
    }

    /**
     * Rand value of payments the member still has to make (no proof uploaded yet).
     */
    public function amountOutstandingFromMembers(): float
    {
        return (float) Payment::query()
            ->whereIn('id', Approval::query()
                ->waitingOnMember()
                ->ofType(ApprovalType::Payment)
                ->select('approvable_id'))
            ->sum('amount');
    }

    /**
     * The next item after $current in queue order, wrapping to the start.
     * With an actor, skips items they cannot decide: their own submissions
     * and items already carrying their first signature.
     */
    public function nextAfter(Approval $current, ApprovalType|string|null $type = null, ?User $actor = null): ?Approval
    {
        $base = fn () => $this->waitingOnAdmin($type)
            ->where('id', '!=', $current->id)
            ->when($actor, fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $actor->id))
                ->where(fn (Builder $q) => $q->whereNull('first_approved_by')->orWhere('first_approved_by', '!=', $actor->id)));

        return $base()
            ->where(fn (Builder $q) => $q
                ->where('submitted_at', '>', $current->submitted_at)
                ->orWhere(fn (Builder $q) => $q->where('submitted_at', $current->submitted_at)->where('id', '>', $current->id)))
            ->first()
            ?? $base()->first();
    }
}
