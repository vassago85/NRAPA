<?php

namespace App\Services\Approvals;

use App\Contracts\Approvable;
use App\Enums\ApprovalState;
use App\Models\Approval;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps the approvals table in step with each approvable model's own status.
 *
 * It reacts to changes in the state the model implies (tracked in
 * meta.derived), not to the absolute state, so a decision recorded by the
 * queue is never overwritten by an unrelated save of the underlying record.
 * Decisions made through ApprovalService suspend syncing for that record.
 */
class ApprovalSync
{
    /** @var array<string, true> */
    private array $suspended = [];

    /** @var list<array{approval_id: int, type: string, state: string}>|null */
    private ?array $cascades = null;

    public function __construct(private ApprovalAuditor $auditor) {}

    /**
     * Run a queue decision, collecting other approvals it closes as a side
     * effect (e.g. a renewal activated by approving its payment). Those are
     * folded into the decision's own audit entry instead of getting their own.
     *
     * @return array{0: mixed, 1: list<array{approval_id: int, type: string, state: string}>}
     */
    public function collectCascades(Closure $callback): array
    {
        $previous = $this->cascades;
        $this->cascades = [];

        try {
            $result = $callback();

            return [$result, $this->cascades];
        } finally {
            $this->cascades = $previous;
        }
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function suspendWhile(Model $model, Closure $callback): mixed
    {
        $key = $this->key($model);
        $this->suspended[$key] = true;

        try {
            return $callback();
        } finally {
            unset($this->suspended[$key]);
        }
    }

    public function sync(Model&Approvable $model): ?Approval
    {
        if (isset($this->suspended[$this->key($model)])) {
            return null;
        }

        $derived = $model->approvalStateFromModel();
        $approval = Approval::forApprovable($model)->first();

        if (! $approval) {
            return $derived ? $this->open($model, $derived) : null;
        }

        $extraMeta = $this->modelMeta($model);

        if ($derived?->value === $approval->metaValue('derived')) {
            if ($this->metaDiffers($approval, $extraMeta)) {
                $approval->withMeta($extraMeta)->save();
            }

            return $approval;
        }

        $approval->withMeta(['derived' => $derived?->value] + $extraMeta);

        if ($derived === ApprovalState::WaitingOnAdmin && $approval->state !== ApprovalState::WaitingOnAdmin) {
            $this->moveToAdmin($approval);
        } elseif ($derived === ApprovalState::WaitingOnMember && $approval->state !== ApprovalState::WaitingOnMember) {
            $this->moveToMember($approval);
        } elseif ($derived === null && $approval->isOpen() && ! $approval->metaValue('awaiting_resubmission')) {
            $this->closeOutsideQueue($approval, $model->approvalOutcomeFromModel());

            return $approval;
        }

        $approval->save();

        return $approval;
    }

    /**
     * Record the state the model implies right after the queue acted on it,
     * so the next unrelated save is not mistaken for a change.
     */
    public function rememberDerived(Approval $approval, Model&Approvable $model): void
    {
        $approval->withMeta(['derived' => $model->approvalStateFromModel()?->value] + $this->modelMeta($model))->save();
    }

    /**
     * A member who was asked for changes did something that counts as a
     * resubmission for items that have no status of their own to flip
     * (e.g. uploading a document for a membership application).
     */
    public function memberResubmitted(User|int $member, ?array $types = null): void
    {
        $memberId = $member instanceof User ? $member->id : $member;

        Approval::query()
            ->where('user_id', $memberId)
            ->waitingOnMember()
            ->when($types, fn ($q) => $q->whereIn('type', array_map(fn ($t) => $t->value, $types)))
            ->get()
            ->filter(fn (Approval $a) => $a->metaValue('awaiting_resubmission') && $a->metaValue('resubmits_on_member_activity'))
            ->each(function (Approval $approval) {
                $this->moveToAdmin($approval);
                $approval->save();
            });
    }

    /**
     * Drop an undecided approval that should never have existed, e.g. a
     * document that turned out to be an activity's evidence.
     */
    public function discard(Model $model): void
    {
        Approval::forApprovable($model)
            ->open()
            ->whereNull('decided_at')
            ->whereNull('first_approved_by')
            ->delete();
    }

    public function deleted(Model&Approvable $model): void
    {
        $approval = Approval::forApprovable($model)->open()->first();

        if ($approval) {
            $approval->state = ApprovalState::Withdrawn;
            $approval->decided_at = now();
            $approval->withMeta(['withdrawn_reason' => 'record_deleted'])->save();
        }
    }

    private function open(Model&Approvable $model, ApprovalState $state): Approval
    {
        $type = $model->approvalType();

        $approval = Approval::create([
            'approvable_type' => $model->getMorphClass(),
            'approvable_id' => $model->getKey(),
            'user_id' => $model->approvalUserId(),
            'type' => $type,
            'state' => $state,
            'submitted_at' => $state === ApprovalState::WaitingOnAdmin
                ? ($model->approvalSubmittedAt() ?? now())
                : now(),
            'requires_second_approval' => $type->requiresSecondApprovalByDefault(),
            'meta' => array_filter([
                'derived' => $state->value,
                'waiting_since' => $state === ApprovalState::WaitingOnMember ? now()->toIso8601String() : null,
            ]) + $this->modelMeta($model),
        ]);

        $this->supersedeOlder($approval, $model);

        return $approval;
    }

    private function supersedeOlder(Approval $new, Model&Approvable $model): void
    {
        Approval::query()
            ->where('user_id', $new->user_id)
            ->where('type', $new->type->value)
            ->where('id', '!=', $new->id)
            ->waitingOnMember()
            ->with('approvable')
            ->get()
            ->filter(fn (Approval $old) => $old->metaValue('awaiting_resubmission')
                && $old->approvable
                && (! method_exists($model, 'supersedesApprovableOf') || $model->supersedesApprovableOf($old->approvable)))
            ->each(function (Approval $old) use ($new) {
                $old->state = ApprovalState::Withdrawn;
                $old->decided_at = now();
                $old->withMeta(['superseded_by' => $new->id, 'awaiting_resubmission' => false])->save();
            });
    }

    private function moveToAdmin(Approval $approval): void
    {
        $wasClosed = ! $approval->isOpen() || $approval->metaValue('awaiting_resubmission');

        $approval->state = ApprovalState::WaitingOnAdmin;
        $approval->submitted_at = now();
        $approval->withMeta([
            'awaiting_resubmission' => false,
            'resubmitted_at' => now()->toIso8601String(),
            'waiting_since' => null,
        ]);

        if ($wasClosed) {
            $approval->decided_at = null;
            $approval->decided_by = null;
            $approval->decision_note = null;
            $approval->first_approved_by = null;
            $approval->first_approved_at = null;
        }
    }

    private function moveToMember(Approval $approval): void
    {
        $approval->state = ApprovalState::WaitingOnMember;
        $approval->withMeta(['waiting_since' => now()->toIso8601String()]);

        if ($approval->decided_at && ! $approval->metaValue('awaiting_resubmission')) {
            $approval->decided_at = null;
            $approval->decided_by = null;
            $approval->decision_note = null;
        }
    }

    private function closeOutsideQueue(Approval $approval, ApprovalState $outcome): void
    {
        $actor = auth()->user();
        $actor = $actor instanceof User && $actor->isAdmin() ? $actor : null;

        $approval->state = $outcome;
        $approval->decided_at = now();
        $approval->decided_by = $outcome === ApprovalState::Withdrawn ? null : $actor?->id;

        if ($this->cascades !== null) {
            $approval->withMeta(['closed_by_cascade' => true])->save();
            $this->cascades[] = [
                'approval_id' => $approval->id,
                'type' => $approval->type->value,
                'state' => $outcome->value,
            ];

            return;
        }

        $approval->withMeta(['closed_outside_queue' => true])->save();

        if (in_array($outcome, [ApprovalState::Approved, ApprovalState::Declined], true)) {
            $this->auditor->record(
                $approval,
                $outcome === ApprovalState::Approved ? 'approval.approved' : 'approval.declined',
                $actor,
                ['source' => 'outside_queue'],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function modelMeta(Model $model): array
    {
        return method_exists($model, 'approvalMetaFromModel') ? $model->approvalMetaFromModel() : [];
    }

    private function metaDiffers(Approval $approval, array $meta): bool
    {
        foreach ($meta as $key => $value) {
            if ($approval->metaValue($key) !== $value) {
                return true;
            }
        }

        return false;
    }

    private function key(Model $model): string
    {
        return $model->getMorphClass().':'.$model->getKey();
    }
}
