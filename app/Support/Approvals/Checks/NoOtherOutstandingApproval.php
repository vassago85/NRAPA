<?php

namespace App\Support\Approvals\Checks;

use App\Models\Approval;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Database\Eloquent\Model;

class NoOtherOutstandingApproval
{
    public static function check(Model $subject, ?int $memberId): PreflightCheck
    {
        $label = 'No other outstanding approval';

        if (! $memberId) {
            return PreflightCheck::pass('other_outstanding', $label, 'No other items waiting');
        }

        $others = Approval::query()
            ->where('user_id', $memberId)
            ->waitingOnAdmin()
            ->where(fn ($q) => $q->where('approvable_type', '!=', $subject->getMorphClass())
                ->orWhere('approvable_id', '!=', $subject->getKey()))
            ->pluck('type');

        if ($others->isEmpty()) {
            return PreflightCheck::pass('other_outstanding', $label, 'No other items waiting for this member');
        }

        $summary = $others->countBy(fn ($type) => $type->label())
            ->map(fn ($count, $label) => "{$count} {$label}")
            ->implode(', ');

        return PreflightCheck::fail('other_outstanding', $label, "Also waiting: {$summary}");
    }
}
