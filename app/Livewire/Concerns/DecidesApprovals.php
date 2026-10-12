<?php

namespace App\Livewire\Concerns;

use App\Enums\ApprovalDecision;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Models\Approval;
use App\Services\Approvals\ApprovalService;
use Illuminate\Database\Eloquent\Model;

/**
 * Lets the per-type admin pages route decisions through ApprovalService
 * instead of calling the model's own approve/reject methods.
 */
trait DecidesApprovals
{
    /**
     * Returns true when the decision was recorded. On failure an error is
     * flashed and false is returned.
     *
     * @param  list<string>  $items
     */
    protected function decideFor(Model $record, ApprovalDecision $decision, ?string $note = null, array $items = []): bool
    {
        $approval = Approval::forApprovable($record)->open()->first();

        if (! $approval) {
            session()->flash('error', 'This item is no longer waiting for a decision.');

            return false;
        }

        try {
            app(ApprovalService::class)->decide($approval, $decision, auth()->user(), $note, $items);

            return true;
        } catch (ApprovalAlreadyDecided|ApprovalNotAllowed $e) {
            session()->flash('error', $e->getMessage());

            return false;
        }
    }

    protected function hasOpenApproval(Model $record): bool
    {
        return Approval::forApprovable($record)->open()->exists();
    }
}
