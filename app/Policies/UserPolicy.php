<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Bulk actions from the members index only touch ordinary members, never
     * staff accounts and never the acting admin.
     */
    public function bulkManage(User $actor, User $member): bool
    {
        return $actor->isAdmin()
            && $member->role === User::ROLE_MEMBER
            && (int) $member->id !== (int) $actor->id;
    }
}
