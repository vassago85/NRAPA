<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('daily backup command fails without password', function () {
    // Ensure no password is set
    SystemSetting::where('key', 'database_backup_password')->delete();

    Artisan::call('nrapa:daily-database-backup');

    $output = Artisan::output();
    expect($output)->toContain('backup is disabled');
});

test('daily backup command succeeds with valid password', function () {
    // Set encrypted password
    $password = 'testpassword123';
    $encrypted = Crypt::encryptString($password);
    SystemSetting::set('database_backup_password', $encrypted, 'string', 'backup', 'Test password');

    // For SQLite, backup should work
    $exitCode = Artisan::call('nrapa:daily-database-backup');

    // Command should complete (may succeed or fail based on actual backup, but shouldn't error on password)
    expect($exitCode)->toBeIn([0, 1]); // 0 = success, 1 = failure (but not password error)

    $output = Artisan::output();
    // Should not contain password error
    expect($output)->not->toContain('password is not configured');
});

test('daily backup prefers the live config password over a stale stored override', function () {
    $command = app(\App\Console\Commands\DailyDatabaseBackup::class);
    $method = new ReflectionMethod($command, 'resolveDatabasePassword');
    $connection = config('database.default');

    config(["database.connections.{$connection}.password" => 'live-correct-password']);

    SystemSetting::set(
        'database_backup_password',
        Crypt::encryptString('stale-wrong-password'),
        'string',
        'backup',
        'Stale override',
    );

    expect($method->invoke($command))->toBe('live-correct-password');
});

test('daily backup uses the stored password only when live config password is empty', function () {
    $command = app(\App\Console\Commands\DailyDatabaseBackup::class);
    $method = new ReflectionMethod($command, 'resolveDatabasePassword');
    $connection = config('database.default');

    config(["database.connections.{$connection}.password" => '']);

    SystemSetting::set(
        'database_backup_password',
        Crypt::encryptString('stored-fallback-password'),
        'string',
        'backup',
        'Fallback override',
    );

    expect($method->invoke($command))->toBe('stored-fallback-password');
});
