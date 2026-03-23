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

// Daily database backup at 2 AM
Schedule::command('nrapa:daily-database-backup')
    ->dailyAt('02:00')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->runInBackground();

// Auto-renew lifetime member certificates daily at 6 AM
Schedule::command('nrapa:renew-lifetime-certificates')
    ->dailyAt('06:00')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->runInBackground();

// Purge verified document files from storage after 7 days (POPIA compliance)
Schedule::command('nrapa:purge-verified-document-files')
    ->dailyAt('03:00')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->runInBackground();
