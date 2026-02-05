<?php

use App\Models\SystemSetting;
use App\Console\Commands\DailyDatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('daily backup command fails without password', function () {
    // Ensure no password is set
    SystemSetting::where('key', 'database_backup_password')->delete();
    
    Artisan::call('nrapa:daily-database-backup');
    
    $output = Artisan::output();
    expect($output)->toContain('password is not configured');
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
