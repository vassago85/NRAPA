<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLoginLog
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            LoginLog::record(
                $event->user,
                $event->remember ?? false,
            );
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $user = $event->user;
            if ($user instanceof User && $user->hasRoleLevel(User::ROLE_ADMIN)) {
                app(\App\Services\NtfyService::class)->notifyAdmins(
                    'new_member',
                    'Admin Login',
                    "{$user->name} ({$user->role}) signed in.",
                    'low',
                );
            }
        } catch (\Throwable $e) {}
    }
}
