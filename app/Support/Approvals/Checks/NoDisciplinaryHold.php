<?php

namespace App\Support\Approvals\Checks;

use App\Models\User;
use App\Support\Approvals\PreflightCheck;

class NoDisciplinaryHold
{
    public static function check(?User $member): PreflightCheck
    {
        $label = 'No open disciplinary flag';

        if ($member?->disciplinary_hold_at) {
            $reason = $member->disciplinary_hold_reason
                ? 'Disciplinary hold: '.$member->disciplinary_hold_reason
                : 'Disciplinary hold since '.$member->disciplinary_hold_at->format('d M Y');

            return PreflightCheck::fail('disciplinary', $label, $reason);
        }

        return PreflightCheck::pass('disciplinary', $label, 'No open disciplinary flag');
    }
}
