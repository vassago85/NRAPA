<?php

namespace App\Concerns;

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Services\Approvals\ApprovalSync;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Shared plumbing for models implementing App\Contracts\Approvable.
 */
trait SyncsApproval
{
    public static function bootSyncsApproval(): void
    {
        static::saved(function ($model) {
            app(ApprovalSync::class)->sync($model);
        });

        static::deleted(function ($model) {
            app(ApprovalSync::class)->deleted($model);
        });
    }

    public function approval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable');
    }

    public function approvalUserId(): ?int
    {
        return $this->user_id;
    }

    public function notifiesMemberOnDecision(ApprovalDecision $decision): bool
    {
        return $decision !== ApprovalDecision::RequestChanges;
    }

    /**
     * Whether any later member activity (e.g. a document upload) counts as a
     * resubmission after changes were requested. Types with a status the
     * member flips themselves leave this false.
     */
    public function resubmitsOnMemberActivity(): bool
    {
        return false;
    }

    /**
     * Inline approval also requires every preflight check to pass.
     */
    public function isInlineApprovable(): bool
    {
        return $this->canBeApprovedInline() && PreflightCheck::allPass($this->preflightChecks());
    }
}
