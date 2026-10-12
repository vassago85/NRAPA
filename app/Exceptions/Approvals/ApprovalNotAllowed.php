<?php

namespace App\Exceptions\Approvals;

use RuntimeException;

class ApprovalNotAllowed extends RuntimeException
{
    public static function selfApproval(): self
    {
        return new self('You cannot decide on your own submission.');
    }

    public static function notAdmin(): self
    {
        return new self('Only administrators can decide approvals.');
    }

    public static function sameSecondSigner(): self
    {
        return new self('The second approval must come from a different admin.');
    }

    public static function missingRecord(): self
    {
        return new self('The record behind this approval no longer exists.');
    }
}
