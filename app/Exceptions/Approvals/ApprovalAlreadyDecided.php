<?php

namespace App\Exceptions\Approvals;

use App\Models\Approval;
use RuntimeException;

/**
 * Another admin decided the item between this admin loading it and acting.
 */
class ApprovalAlreadyDecided extends RuntimeException
{
    public function __construct(public readonly Approval $approval)
    {
        parent::__construct($this->describe());
    }

    public static function for(Approval $approval): self
    {
        return new self($approval->fresh(['decider', 'firstApprover']) ?? $approval);
    }

    public function describe(): string
    {
        $approval = $this->approval;

        if ($approval->awaitsSecondSignature() && ! $approval->decided_at) {
            return 'First signature already recorded by '.($approval->firstApprover?->name ?? 'another admin').' '.$approval->first_approved_at?->diffForHumans().'.';
        }

        $who = $approval->decider?->name ?? 'another admin';
        $when = $approval->decided_at?->diffForHumans() ?? 'just now';

        return "Already decided by {$who} {$when} ({$approval->state->label()}).";
    }
}
