<?php

namespace App\Support\Approvals\Checks;

use App\Models\Membership;
use App\Models\ShootingActivity;
use App\Support\Approvals\PreflightCheck;

class ActivityWithinMembershipPeriod
{
    public static function check(ShootingActivity $activity): PreflightCheck
    {
        $label = 'Dated within membership period';

        if (! $activity->activity_date) {
            return PreflightCheck::fail('within_period', $label, 'Activity has no date');
        }

        if (self::covered($activity)) {
            return PreflightCheck::pass('within_period', $label, 'Dated '.$activity->activity_date->format('d M Y').', within a membership period');
        }

        return PreflightCheck::fail('within_period', $label, 'Dated '.$activity->activity_date->format('d M Y').', outside the membership period');
    }

    public static function covered(ShootingActivity $activity): bool
    {
        $date = $activity->activity_date->toDateString();

        return Membership::query()
            ->where('user_id', $activity->user_id)
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->whereRaw('date(coalesce(activated_at, approved_at, applied_at, created_at)) <= ?', [$date])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $date))
            ->exists();
    }
}
