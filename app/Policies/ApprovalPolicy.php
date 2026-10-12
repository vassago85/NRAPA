<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins never decide on their own submissions. Second signatures are
     * checked separately by ApprovalService.
     */
    public function decide(User $user, Approval $approval): bool
    {
        return $user->isAdmin() && (int) $approval->user_id !== (int) $user->id;
    }
}
