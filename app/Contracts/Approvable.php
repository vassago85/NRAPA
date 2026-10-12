<?php

namespace App\Contracts;

use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Models\User;
use App\Support\Approvals\EvidenceBlock;
use App\Support\Approvals\PreflightCheck;
use Carbon\CarbonInterface;

/**
 * Anything that lands in the approval queue. The queue and the review
 * workspace render items purely through this interface.
 *
 * The decision methods are suffixed "FromQueue" because several models
 * already have approve()/reject() methods with narrower meanings that other
 * code depends on.
 */
interface Approvable
{
    public function approvalType(): ApprovalType;

    /**
     * The member the item belongs to.
     */
    public function approvalUserId(): ?int;

    /**
     * Open queue state implied by the model's own status, or null when the
     * model is not waiting on anyone.
     */
    public function approvalStateFromModel(): ?ApprovalState;

    /**
     * Terminal state implied by the model when it closed outside the queue
     * (legacy page, member withdrew, etc.).
     */
    public function approvalOutcomeFromModel(): ApprovalState;

    public function approvalSubmittedAt(): ?CarbonInterface;

    public function summaryLine(): string;

    public function detailLine(): string;

    /**
     * Short warning the row highlights in amber, e.g. "1 falls outside the
     * membership period". Null when nothing needs flagging.
     */
    public function detailWarning(): ?string;

    public function evidence(): EvidenceBlock;

    /**
     * @return list<PreflightCheck>
     */
    public function preflightChecks(): array;

    /**
     * Whether this type of item may be approved straight from the queue.
     * The queue additionally requires every preflight check to pass.
     */
    public function canBeApprovedInline(): bool;

    /**
     * The sentence shown under the decision bar.
     */
    public function approvalConsequence(): string;

    public function approveFromQueue(User $actor, ?string $note = null): void;

    public function declineFromQueue(User $actor, string $reason): void;

    /**
     * @param  list<string>  $items
     */
    public function requestChangesFromQueue(User $actor, array $items, ?string $note = null): void;

    /**
     * Whether the model's own decision methods already email the member for
     * this decision. When false the queue sends a generic notification.
     */
    public function notifiesMemberOnDecision(\App\Enums\ApprovalDecision $decision): bool;
}
