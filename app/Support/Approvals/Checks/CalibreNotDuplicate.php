<?php

namespace App\Support\Approvals\Checks;

use App\Models\CalibreRequest;
use App\Support\Approvals\PreflightCheck;

class CalibreNotDuplicate
{
    public static function check(CalibreRequest $request): PreflightCheck
    {
        $label = 'Not already in the calibre list';

        $existing = $request->exactMatch();

        if (! $existing) {
            return PreflightCheck::pass('calibre_not_duplicate', $label, 'No calibre with this name yet');
        }

        return PreflightCheck::fail('calibre_not_duplicate', $label, "Already listed as {$existing->name}; approving links to it");
    }
}
