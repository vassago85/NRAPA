<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| These tasks are run automatically via Laravel's scheduler.
| Make sure to add the cron entry on your server:
| * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Send license expiry notifications daily at 8 AM
Schedule::command('nrapa:send-license-expiry-notifications')
    ->dailyAt('08:00')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->runInBackground();
