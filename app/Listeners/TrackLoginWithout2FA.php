<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class TrackLoginWithout2FA
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Track logins for users who require 2FA but haven't enabled it
        if ($user->requires2FA() && ! $user->has2FAEnabled()) {
            $user->incrementLoginWithout2FA();
        }
    }
}
