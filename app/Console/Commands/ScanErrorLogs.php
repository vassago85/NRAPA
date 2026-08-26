<?php

namespace App\Console\Commands;

use App\Services\ErrorLogScanner;
use App\Services\NtfyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanErrorLogs extends Command
{
    protected $signature = 'nrapa:scan-error-logs
                            {--hours=24 : How many hours of log history to scan}
                            {--path= : Log directory (defaults to storage/logs)}
                            {--dry-run : Print the digest without sending ntfy}';

    protected $description = 'Scan Laravel logs for recent errors and notify developers via ntfy';

    public function handle(NtfyService $ntfy): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $path = $this->option('path') ?: storage_path('logs');
        $until = now();
        $since = $until->copy()->subHours($hours);

        $report = (new ErrorLogScanner($path))->scan($since, $until);

        if (! $report->hasErrors()) {
            $this->info("No errors in the last {$hours}h.");

            return self::SUCCESS;
        }

        $digest = $report->digest($hours);
        $this->warn($digest);

        if ($this->option('dry-run')) {
            $this->comment('[dry-run] Ntfy not sent.');

            return self::SUCCESS;
        }

        $title = "NRAPA: {$report->total} error(s) in last {$hours}h";
        $priority = $report->highestLevel() === 'ERROR' ? 'high' : 'urgent';

        $ntfy->notifyAdmins('system_errors', $title, $digest, $priority);

        Log::info('Daily error log scan notified developers', [
            'total' => $report->total,
            'unique' => $report->uniqueCount,
        ]);

        $this->info('Developers notified via ntfy.');

        return self::SUCCESS;
    }
}
