<?php

namespace App\Services\Approvals;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Mail\ApprovalDecisionMail;
use App\Models\Approval;
use App\Models\User;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * The only place approval decisions are made. Every decision checks the
 * policy, claims the row with a conditional update (optimistic lock), runs
 * the record's own action, and writes exactly one audit entry, all in one
 * transaction. The member is notified after commit.
 */
class ApprovalService
{
    public function __construct(
        private ApprovalSync $sync,
        private ApprovalAuditor $auditor,
    ) {}

    /**
     * @param  list<string>  $items
     */
    public function decide(Approval $approval, ApprovalDecision $decision, User $actor, ?string $note = null, array $items = []): Approval
    {
        $note = ($note = trim((string) $note)) === '' ? null : $note;
        $items = array_values(array_filter(array_map(fn ($i) => trim((string) $i), $items), fn ($i) => $i !== ''));

        $this->authorize($approval, $actor);
        $this->validate($decision, $note, $items);

        if (! in_array($approval->state->value, $this->claimableStates($decision), true)) {
            throw ApprovalAlreadyDecided::for($approval);
        }

        /** @var (\Illuminate\Database\Eloquent\Model&Approvable)|null $approvable */
        $approvable = $approval->approvable;

        if (! $approvable) {
            throw ApprovalNotAllowed::missingRecord();
        }

        $preflight = array_map(fn (PreflightCheck $c) => $c->toArray(), $approvable->preflightChecks());
        $firstSignature = $decision === ApprovalDecision::Approve
            && $approval->requires_second_approval
            && $approval->first_approved_by === null;

        if ($decision === ApprovalDecision::Approve && $approval->requires_second_approval && (int) $approval->first_approved_by === (int) $actor->id) {
            throw ApprovalNotAllowed::sameSecondSigner();
        }

        $modelNotifies = ! $firstSignature && $approvable->notifiesMemberOnDecision($decision);

        $approval = DB::transaction(function () use ($approval, $approvable, $decision, $actor, $note, $items, $preflight, $firstSignature) {
            if ($firstSignature) {
                return $this->recordFirstSignature($approval, $actor, $note, $preflight);
            }

            $this->claim($approval, $decision, $actor, $note);

            [, $cascades] = $this->sync->collectCascades(fn () => $this->sync->suspendWhile($approvable, fn () => match ($decision) {
                ApprovalDecision::Approve => $approvable->approveFromQueue($actor, $note),
                ApprovalDecision::Decline => $approvable->declineFromQueue($actor, (string) $note),
                ApprovalDecision::RequestChanges => $approvable->requestChangesFromQueue($actor, $items, $note),
            }));

            $approval->refresh();
            $approval->withMeta(array_filter([
                'last_decision' => $decision->value,
                'changes_requested' => $decision === ApprovalDecision::RequestChanges ? $items : null,
                'awaiting_resubmission' => $decision === ApprovalDecision::RequestChanges ? true : null,
                'resubmits_on_member_activity' => $decision === ApprovalDecision::RequestChanges && method_exists($approvable, 'resubmitsOnMemberActivity')
                    ? $approvable->resubmitsOnMemberActivity()
                    : null,
                'waiting_since' => $decision === ApprovalDecision::RequestChanges ? now()->toIso8601String() : null,
            ], fn ($v) => $v !== null));

            if ($decision === ApprovalDecision::RequestChanges) {
                $approval->state = ApprovalState::WaitingOnMember;
            }

            $this->sync->rememberDerived($approval, $approvable->fresh() ?? $approvable);

            $this->auditor->record($approval, $decision->auditEvent(), $actor, array_filter([
                'note' => $note,
                'items' => $items ?: null,
                'preflight' => $preflight,
                'stage' => $approval->requires_second_approval ? 'second_signature' : null,
                'first_approved_by' => $approval->requires_second_approval ? $approval->first_approved_by : null,
                'cascaded' => $cascades ?: null,
            ], fn ($v) => $v !== null));

            return $approval;
        });

        Approval::flushCountCaches();

        if (! $firstSignature && ! $modelNotifies) {
            DB::afterCommit(fn () => $this->notify($approval, $decision, $note, $items));
        }

        return $approval;
    }

    /**
     * Only called from the queue for items where every preflight check passes.
     */
    public function approveInline(Approval $approval, User $actor): Approval
    {
        $approvable = $approval->approvable;

        if (! $approvable || ! $approvable->isInlineApprovable()) {
            throw ValidationException::withMessages(['approval' => 'This item needs a full review before it can be approved.']);
        }

        return $this->decide($approval, ApprovalDecision::Approve, $actor);
    }

    /**
     * "Mark paid": the admin saw the money arrive before the member uploaded
     * proof. Still a normal Approve decision with its own audit entry.
     */
    public function confirmPaymentWithoutProof(Approval $approval, User $actor, ?string $note = null): Approval
    {
        if ($approval->type !== \App\Enums\ApprovalType::Payment) {
            throw new \InvalidArgumentException('Only payments can be confirmed without a submission.');
        }

        $this->authorize($approval, $actor);

        return DB::transaction(function () use ($approval, $actor, $note) {
            $moved = Approval::query()
                ->whereKey($approval->id)
                ->where('state', ApprovalState::WaitingOnMember->value)
                ->update(['state' => ApprovalState::WaitingOnAdmin->value, 'updated_at' => now()]);

            $approval->refresh();

            if (! $moved && $approval->state !== ApprovalState::WaitingOnAdmin) {
                throw ApprovalAlreadyDecided::for($approval);
            }

            $approval->withMeta(['confirmed_without_proof' => true])->saveQuietly();

            return $this->decide($approval, ApprovalDecision::Approve, $actor, $note ?? 'Payment confirmed by admin without a proof of payment upload.');
        });
    }

    public function authorize(Approval $approval, User $actor): void
    {
        if (! $actor->isAdmin()) {
            throw ApprovalNotAllowed::notAdmin();
        }

        if (Gate::forUser($actor)->denies('decide', $approval)) {
            throw ApprovalNotAllowed::selfApproval();
        }
    }

    /**
     * @param  list<string>  $items
     */
    private function validate(ApprovalDecision $decision, ?string $note, array $items): void
    {
        if ($decision === ApprovalDecision::Decline && $note === null) {
            throw ValidationException::withMessages(['note' => 'Give the member a reason for declining.']);
        }

        if ($decision === ApprovalDecision::RequestChanges && $items === [] && $note === null) {
            throw ValidationException::withMessages(['note' => 'Tell the member what needs to change.']);
        }
    }

    private function claim(Approval $approval, ApprovalDecision $decision, User $actor, ?string $note): void
    {
        $query = Approval::query()
            ->whereKey($approval->id)
            ->whereIn('state', $this->claimableStates($decision));

        if ($approval->requires_second_approval && $decision === ApprovalDecision::Approve) {
            $query->where('first_approved_by', $approval->first_approved_by);
        }

        $claimed = $query->update([
            'state' => $decision === ApprovalDecision::RequestChanges
                ? ApprovalState::ChangesRequested->value
                : $decision->resultingState()->value,
            'decided_at' => now(),
            'decided_by' => $actor->id,
            'decision_note' => $note,
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            throw ApprovalAlreadyDecided::for($approval);
        }
    }

    /**
     * An item can be declined while it still waits on the member (e.g. an
     * application that was never paid for); approving needs a submission.
     *
     * @return list<string>
     */
    private function claimableStates(ApprovalDecision $decision): array
    {
        return $decision === ApprovalDecision::Decline
            ? ApprovalState::openValues()
            : [ApprovalState::WaitingOnAdmin->value];
    }

    private function recordFirstSignature(Approval $approval, User $actor, ?string $note, array $preflight): Approval
    {
        $claimed = Approval::query()
            ->whereKey($approval->id)
            ->where('state', ApprovalState::WaitingOnAdmin->value)
            ->whereNull('first_approved_by')
            ->update([
                'first_approved_by' => $actor->id,
                'first_approved_at' => now(),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            throw ApprovalAlreadyDecided::for($approval);
        }

        $approval->refresh();
        $approval->withMeta(['first_signature_note' => $note])->save();

        $this->auditor->record($approval, 'approval.first_signature', $actor, array_filter([
            'note' => $note,
            'preflight' => $preflight,
            'stage' => 'first_signature',
        ], fn ($v) => $v !== null));

        return $approval;
    }

    /**
     * @param  list<string>  $items
     */
    private function notify(Approval $approval, ApprovalDecision $decision, ?string $note, array $items): void
    {
        $member = $approval->user;

        if (! $member?->email) {
            return;
        }

        try {
            Mail::to($member->email)->queue(new ApprovalDecisionMail($approval, $decision, $note, $items));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue approval decision email', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
