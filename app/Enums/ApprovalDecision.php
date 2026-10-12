<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case Approve = 'approve';
    case Decline = 'decline';
    case RequestChanges = 'request_changes';

    public function auditEvent(): string
    {
        return match ($this) {
            self::Approve => 'approval.approved',
            self::Decline => 'approval.declined',
            self::RequestChanges => 'approval.changes_requested',
        };
    }

    public function resultingState(): ApprovalState
    {
        return match ($this) {
            self::Approve => ApprovalState::Approved,
            self::Decline => ApprovalState::Declined,
            self::RequestChanges => ApprovalState::WaitingOnMember,
        };
    }

    public function pastTense(): string
    {
        return match ($this) {
            self::Approve => 'approved',
            self::Decline => 'declined',
            self::RequestChanges => 'requested changes on',
        };
    }
}
