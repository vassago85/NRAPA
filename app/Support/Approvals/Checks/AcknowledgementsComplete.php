<?php

namespace App\Support\Approvals\Checks;

use App\Models\EndorsementRequest;
use App\Support\Approvals\PreflightCheck;

class AcknowledgementsComplete
{
    public static function check(EndorsementRequest $request): PreflightCheck
    {
        $label = 'Acknowledgements complete';

        if (! $request->isSelfDefence()) {
            return PreflightCheck::pass('acknowledgements', $label, 'Declaration accepted '.($request->declaration_accepted_at?->format('d M Y') ?? ''));
        }

        $required = array_keys(EndorsementRequest::selfDefenceClauses());
        $accepted = $request->acknowledgements()
            ->where('accepted', true)
            ->pluck('clause_key')
            ->all();

        $missing = array_diff($required, $accepted);

        if ($missing === []) {
            return PreflightCheck::pass('acknowledgements', $label, 'All '.count($required).' clauses accepted');
        }

        return PreflightCheck::fail('acknowledgements', $label, count($missing).' of '.count($required).' clauses not accepted');
    }
}
