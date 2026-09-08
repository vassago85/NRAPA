<?php

use App\Models\EmailLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

function makeEmailLog(CarbonInterface $createdAt, string $subject): EmailLog
{
    // Rewind the clock so Eloquent stamps `created_at`/`updated_at` in the past.
    Carbon::setTestNow($createdAt);
    $log = EmailLog::create([
        'to_email' => 'recipient-'.uniqid().'@example.com',
        'subject' => $subject,
        'mailable_class' => 'App\\Mail\\Fake',
        'status' => 'sent',
        'sent_at' => $createdAt,
    ]);
    Carbon::setTestNow();

    return $log;
}

afterEach(function () {
    Carbon::setTestNow();
});

test('purges email logs older than the configured retention window', function () {
    $old = makeEmailLog(now()->subDays(45), 'old-log');
    $recent = makeEmailLog(now()->subDays(10), 'recent-log');

    $this->artisan('nrapa:purge-email-logs')
        ->expectsOutputToContain('Purged 1 email log(s) older than 30 days.')
        ->assertSuccessful();

    expect(EmailLog::find($old->id))->toBeNull();
    expect(EmailLog::find($recent->id))->not->toBeNull();
});

test('respects a custom --days retention window', function () {
    $olderThanSeven = makeEmailLog(now()->subDays(10), 'older-than-7');
    $withinSeven = makeEmailLog(now()->subDays(3), 'within-7');

    $this->artisan('nrapa:purge-email-logs', ['--days' => 7])
        ->assertSuccessful();

    expect(EmailLog::find($olderThanSeven->id))->toBeNull();
    expect(EmailLog::find($withinSeven->id))->not->toBeNull();
});

test('dry-run reports the count without deleting rows', function () {
    $old = makeEmailLog(now()->subDays(60), 'old-dry-run');
    $recent = makeEmailLog(now()->subDays(5), 'recent-dry-run');

    $this->artisan('nrapa:purge-email-logs', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Would delete 1 email log(s) older than 30 days')
        ->assertSuccessful();

    expect(EmailLog::find($old->id))->not->toBeNull();
    expect(EmailLog::find($recent->id))->not->toBeNull();
});

test('reports zero-work cleanly when nothing is due', function () {
    makeEmailLog(now()->subDays(5), 'nothing-due');

    $this->artisan('nrapa:purge-email-logs')
        ->expectsOutputToContain('No email logs older than 30 days')
        ->assertSuccessful();

    expect(EmailLog::count())->toBe(1);
});

test('rejects a --days value below 1', function () {
    $this->artisan('nrapa:purge-email-logs', ['--days' => 0])
        ->assertFailed();
});
