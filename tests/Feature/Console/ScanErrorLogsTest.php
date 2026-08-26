<?php

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->logDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nrapa-error-cmd-'.uniqid();
    File::ensureDirectoryExists($this->logDir);

    Http::fake([
        'https://ntfy.sh/*' => Http::response('ok', 200),
    ]);

    $this->developer = User::factory()->create([
        'role' => User::ROLE_DEVELOPER,
        'is_admin' => true,
    ]);

    NotificationPreference::create([
        'user_id' => $this->developer->id,
        'ntfy_enabled' => true,
        'ntfy_topic' => 'nrapa-dev-errors',
        'notify_system_errors' => true,
        'respect_working_hours' => false,
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->logDir);
});

test('notifies developers when the daily scan finds errors', function () {
    $stamp = now()->subHours(2)->format('Y-m-d H:i:s');
    file_put_contents($this->logDir.DIRECTORY_SEPARATOR.'laravel.log', "[{$stamp}] testing.ERROR: Daily database backup failed\n");

    $this->artisan('nrapa:scan-error-logs', [
        '--path' => $this->logDir,
        '--hours' => 24,
    ])->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'nrapa-dev-errors')
            && $request->hasHeader('Title')
            && str_contains($request->header('Title')[0], 'error')
            && str_contains($request->body(), 'Daily database backup failed');
    });
});

test('does not notify when the log is clean', function () {
    $stamp = now()->subHours(2)->format('Y-m-d H:i:s');
    file_put_contents($this->logDir.DIRECTORY_SEPARATOR.'laravel.log', "[{$stamp}] testing.INFO: all quiet\n");

    $this->artisan('nrapa:scan-error-logs', [
        '--path' => $this->logDir,
        '--hours' => 24,
    ])->assertSuccessful();

    Http::assertNothingSent();
});

test('dry-run reports errors without sending ntfy', function () {
    $stamp = now()->subHours(2)->format('Y-m-d H:i:s');
    file_put_contents($this->logDir.DIRECTORY_SEPARATOR.'laravel.log', "[{$stamp}] testing.ERROR: something exploded\n");

    $this->artisan('nrapa:scan-error-logs', [
        '--path' => $this->logDir,
        '--hours' => 24,
        '--dry-run' => true,
    ])->assertSuccessful();

    Http::assertNothingSent();
});
