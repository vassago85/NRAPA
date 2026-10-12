<?php

namespace App\Support\Membership;

use App\Enums\MembershipState;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single definition of membership counts.
 *
 * Total: users with role member, including those with no membership.
 * Active: has a membership with status active and expires_at empty or on/after
 *         today (or a type whose duration_type is lifetime).
 * Suspended: not active, and has a suspended or revoked membership.
 * Expired: not active or suspended, and had a membership that was activated
 *          (or is marked expired, or is active with a past expiry).
 * Pending: none of the above, and has an application in progress.
 * None: none of the above.
 *
 * Expiring in 30 days and awaiting payment are overlays on those states.
 * Soft-deleted memberships never count.
 */
class MembershipStats
{
    public const CACHE_KEY = 'membership_stats_snapshot';

    public const SEGMENTS = ['all', 'active', 'expiring_30', 'expired', 'awaiting_payment'];

    private const STATE_CONDITIONS = [
        'active' => 'm.is_active = 1',
        'suspended' => 'm.is_active = 0 AND m.is_suspended = 1',
        'expired' => 'm.is_active = 0 AND m.is_suspended = 0 AND m.is_lapsed = 1',
        'pending' => 'm.is_active = 0 AND m.is_suspended = 0 AND m.is_lapsed = 0 AND m.is_pending = 1',
        'expiring_30' => 'm.is_expiring = 1',
        'awaiting_payment' => 'm.is_awaiting = 1',
    ];

    public static function snapshot(): MembershipSnapshot
    {
        $data = Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => self::compute());

        return MembershipSnapshot::fromArray($data);
    }

    public static function refresh(): MembershipSnapshot
    {
        self::flush();

        return self::snapshot();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Users counted by the snapshot. Part B adds the archived exclusion here.
     */
    public static function countableUsers(): EloquentBuilder
    {
        return User::query()->where('users.role', User::ROLE_MEMBER);
    }

    /**
     * Restrict a users query to one segment, using the same rules as snapshot().
     */
    public static function applySegment(EloquentBuilder $query, string $segment): EloquentBuilder
    {
        if ($segment === 'all') {
            return $query;
        }

        if ($segment === 'none') {
            return $query->whereNotIn('users.id', DB::query()
                ->fromSub(self::flagsPerUser(), 'm')
                ->whereRaw('(m.is_active = 1 OR m.is_suspended = 1 OR m.is_lapsed = 1 OR m.is_pending = 1)')
                ->select('m.user_id'));
        }

        $condition = self::STATE_CONDITIONS[$segment]
            ?? throw new InvalidArgumentException("Unknown membership segment [{$segment}].");

        return $query->whereIn('users.id', DB::query()
            ->fromSub(self::flagsPerUser(), 'm')
            ->whereRaw($condition)
            ->select('m.user_id'));
    }

    private static function compute(): array
    {
        $sum = fn (string $condition) => "SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END)";

        $row = self::countableUsers()
            ->toBase()
            ->leftJoinSub(self::flagsPerUser(), 'm', 'm.user_id', '=', 'users.id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw($sum('COALESCE(m.is_active, 0) = 1').' AS active')
            ->selectRaw($sum(self::STATE_CONDITIONS['suspended']).' AS suspended')
            ->selectRaw($sum(self::STATE_CONDITIONS['expired']).' AS expired')
            ->selectRaw($sum(self::STATE_CONDITIONS['pending']).' AS pending')
            ->selectRaw($sum(self::STATE_CONDITIONS['expiring_30']).' AS expiring_30')
            ->selectRaw($sum(self::STATE_CONDITIONS['awaiting_payment']).' AS awaiting_payment')
            ->first();

        $total = (int) ($row->total ?? 0);
        $active = (int) ($row->active ?? 0);
        $suspended = (int) ($row->suspended ?? 0);
        $expired = (int) ($row->expired ?? 0);
        $pending = (int) ($row->pending ?? 0);

        return [
            'total' => $total,
            'active' => $active,
            'suspended' => $suspended,
            'expired' => $expired,
            'pending' => $pending,
            'none' => max(0, $total - $active - $suspended - $expired - $pending),
            'expiring_30' => (int) ($row->expiring_30 ?? 0),
            'awaiting_payment' => (int) ($row->awaiting_payment ?? 0),
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * One row per user with 0/1 flags describing their non-deleted memberships.
     */
    private static function flagsPerUser(): Builder
    {
        $today = now()->startOfDay()->toDateTimeString();
        $in30 = now()->addDays(30)->endOfDay()->toDateTimeString();
        $placeholders = fn (array $values) => implode(',', array_fill(0, count($values), '?'));

        $flag = fn (string $condition) => "MAX(CASE WHEN {$condition} THEN 1 ELSE 0 END)";
        $lifetime = "COALESCE(mt.duration_type, '') = 'lifetime'";
        $current = "mem.status = 'active' AND (mem.expires_at IS NULL OR mem.expires_at >= ? OR {$lifetime})";

        return DB::table('memberships as mem')
            ->leftJoin('membership_types as mt', 'mt.id', '=', 'mem.membership_type_id')
            ->whereNull('mem.deleted_at')
            ->groupBy('mem.user_id')
            ->select('mem.user_id')
            ->selectRaw($flag($current).' AS is_active', [$today])
            ->selectRaw($flag('mem.status IN ('.$placeholders(MembershipState::SUSPENDED_STATUSES).')').' AS is_suspended', MembershipState::SUSPENDED_STATUSES)
            ->selectRaw($flag("(mem.activated_at IS NOT NULL OR mem.status = 'expired' OR (mem.status = 'active' AND NOT ({$current})))").' AS is_lapsed', [$today])
            ->selectRaw($flag('mem.status IN ('.$placeholders(MembershipState::PENDING_STATUSES).')').' AS is_pending', MembershipState::PENDING_STATUSES)
            ->selectRaw($flag("mem.status = 'active' AND mem.expires_at >= ? AND mem.expires_at <= ? AND NOT ({$lifetime})").' AS is_expiring', [$today, $in30])
            ->selectRaw(
                $flag("mem.proof_of_payment_path IS NULL AND mem.payment_confirmed_at IS NULL AND (mem.status = 'pending_payment' OR (mem.status = 'applied' AND mem.source IN (".$placeholders(Membership::BILLABLE_SOURCES).')))').' AS is_awaiting',
                Membership::BILLABLE_SOURCES
            );
    }
}
