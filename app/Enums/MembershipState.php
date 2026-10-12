<?php

namespace App\Enums;

use App\Models\Membership;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A member's overall standing, derived from all their non-deleted memberships.
 * Same rules, in the same priority order, as MembershipStats::snapshot().
 */
enum MembershipState: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Pending = 'pending';
    case None = 'none';

    public const PENDING_STATUSES = ['applied', 'approved', 'pending_payment'];

    public const SUSPENDED_STATUSES = ['suspended', 'revoked'];

    /**
     * @param  Collection<int, Membership>  $memberships
     */
    public static function fromMemberships(Collection $memberships, ?CarbonInterface $today = null): self
    {
        $today ??= now()->startOfDay();

        return match (true) {
            $memberships->contains(fn (Membership $m) => self::isCurrent($m, $today)) => self::Active,
            $memberships->contains(fn (Membership $m) => in_array($m->status, self::SUSPENDED_STATUSES, true)) => self::Suspended,
            $memberships->contains(fn (Membership $m) => self::hasLapsed($m, $today)) => self::Expired,
            $memberships->contains(fn (Membership $m) => in_array($m->status, self::PENDING_STATUSES, true)) => self::Pending,
            default => self::None,
        };
    }

    public static function isCurrent(Membership $membership, CarbonInterface $today): bool
    {
        return $membership->status === 'active'
            && ($membership->expires_at === null || $membership->expires_at->gte($today) || $membership->type?->isLifetime());
    }

    private static function hasLapsed(Membership $membership, CarbonInterface $today): bool
    {
        return $membership->activated_at !== null
            || $membership->status === 'expired'
            || ($membership->status === 'active' && ! self::isCurrent($membership, $today));
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Pending => 'Pending',
            self::None => 'No membership',
        };
    }
}
