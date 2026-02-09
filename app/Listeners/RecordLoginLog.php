<?php

namespace App\Listeners;

use App\Models\LoginLog;
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
            // Don't break login flow if logging fails (e.g. table not migrated yet)
            report($e);
        }
    }
}
