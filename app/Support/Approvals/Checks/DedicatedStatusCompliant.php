<?php

namespace App\Support\Approvals\Checks;

use App\Models\EndorsementRequest;
use App\Models\User;
use App\Support\Approvals\PreflightCheck;

class DedicatedStatusCompliant
{
    public static function check(?User $member): PreflightCheck
    {
        $label = 'Dedicated status compliant';

        if (! $member) {
            return PreflightCheck::fail('dedicated_compliant', $label, 'No member on file');
        }

        $summary = EndorsementRequest::getEligibilitySummary($member);

        if ($summary['eligible'] ?? false) {
            return PreflightCheck::pass('dedicated_compliant', $label, 'Activities, knowledge test and documents in order');
        }

        $errors = array_values(array_filter(array_map(
            fn ($error) => is_array($error) ? ($error['message'] ?? null) : $error,
            $summary['errors'] ?? [],
        )));

        $detail = match (count($errors)) {
            0 => 'Not compliant',
            1 => rtrim($errors[0], '.'),
            default => count($errors).' eligibility issues, listed above',
        };

        return PreflightCheck::fail('dedicated_compliant', $label, $detail);
    }
}
