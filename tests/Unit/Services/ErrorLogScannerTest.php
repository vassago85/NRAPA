<?php

use App\Services\ErrorLogScanner;
use Carbon\Carbon;

beforeEach(function () {
    $this->logDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nrapa-error-scan-'.uniqid();
    mkdir($this->logDir, 0777, true);
});

afterEach(function () {
    foreach (glob($this->logDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($this->logDir);
});

function writeLog(string $dir, string $contents, string $filename = 'laravel.log'): string
{
    $path = $dir.DIRECTORY_SEPARATOR.$filename;
    file_put_contents($path, $contents);

    return $path;
}

test('counts and groups error-level entries from the last 24 hours', function () {
    $now = Carbon::parse('2026-08-26 08:00:00');
    writeLog($this->logDir, <<<'LOG'
[2026-08-25 07:00:00] production.ERROR: too old to include
[2026-08-25 10:15:00] production.INFO: not an error
[2026-08-25 10:16:00] production.WARNING: also not an error
[2026-08-25 11:00:00] production.ERROR: Sage sync job failed permanently {"membership_id":12}
[2026-08-25 12:00:00] production.ERROR: Sage sync job failed permanently {"membership_id":19}
[2026-08-26 07:30:00] production.CRITICAL: Allowed memory size of 134217728 bytes exhausted
[2026-08-26 07:45:00] production.ERROR: [LIVEWIRE_EXCEPTION] TypeError: App\Foo::bar()
Stack trace:
#0 /var/www/html/app/Foo.php(10): bar()
#1 {main}
LOG);

    $report = (new ErrorLogScanner($this->logDir))->scan(
        since: $now->copy()->subDay(),
        until: $now,
    );

    expect($report->total)->toBe(4)
        ->and($report->uniqueCount)->toBe(3)
        ->and($report->hasErrors())->toBeTrue();

    $fingerprints = collect($report->groups)->pluck('count', 'fingerprint');
    expect($fingerprints->get('Sage sync job failed permanently'))->toBe(2)
        ->and($fingerprints->values()->sum())->toBe(4);
});

test('returns an empty report when the log has no recent errors', function () {
    $now = Carbon::parse('2026-08-26 08:00:00');
    writeLog($this->logDir, <<<'LOG'
[2026-08-26 07:00:00] production.INFO: Daily database backup completed successfully
[2026-08-26 07:10:00] production.WARNING: NTFY send returned non-200
LOG);

    $report = (new ErrorLogScanner($this->logDir))->scan(
        since: $now->copy()->subDay(),
        until: $now,
    );

    expect($report->hasErrors())->toBeFalse()
        ->and($report->total)->toBe(0)
        ->and($report->groups)->toBe([]);
});

test('formats a short ntfy digest of the top groups', function () {
    $now = Carbon::parse('2026-08-26 08:00:00');
    writeLog($this->logDir, <<<'LOG'
[2026-08-26 07:00:00] production.ERROR: Sage sync job failed permanently
[2026-08-26 07:01:00] production.ERROR: Sage sync job failed permanently
[2026-08-26 07:02:00] production.CRITICAL: Allowed memory size exhausted
LOG);

    $report = (new ErrorLogScanner($this->logDir))->scan(
        since: $now->copy()->subDay(),
        until: $now,
    );

    $digest = $report->digest(hours: 24);

    expect($digest)->toContain('3 error(s) in the last 24h (2 unique)')
        ->and($digest)->toContain('Sage sync job failed permanently ×2')
        ->and($digest)->toContain('CRITICAL')
        ->and($digest)->toContain('Allowed memory size exhausted ×1');
});
