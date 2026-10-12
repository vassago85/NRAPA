<?php

namespace App\Support\Approvals\Checks;

use App\Models\Membership;
use App\Models\User;
use App\Support\Approvals\PreflightCheck;

class MembershipActive
{
    public static function check(?User $member): PreflightCheck
    {
        $label = 'Membership active';

        $membership = $member ? self::current($member) : null;

        if (! $membership) {
            return PreflightCheck::fail('membership_active', $label, 'No active membership on file');
        }

        if (! $membership->expires_at) {
            return PreflightCheck::pass('membership_active', $label, 'Membership active, no expiry');
        }

        return PreflightCheck::pass('membership_active', $label, 'Membership active, paid to '.$membership->expires_at->format('d M Y'));
    }

    public static function current(User $member): ?Membership
    {
        return Membership::query()
            ->where('user_id', $member->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', today()))
            ->latest('activated_at')
            ->first();
    }
}
