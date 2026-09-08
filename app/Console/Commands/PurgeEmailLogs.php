<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeEmailLogs extends Command
{
    protected $signature = 'nrapa:purge-email-logs {--days=30 : Retain email logs newer than this many days} {--dry-run : Report what would be deleted without touching the database}';

    protected $description = 'Hard-delete email_logs rows older than N days (default 30). Email logs store full HTML bodies as longText and grow indefinitely; ops value beyond 30 days is negligible. Business-audit trail lives separately in audit_logs.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $query = EmailLog::where('created_at', '<', $cutoff);
        $toDelete = (clone $query)->count();

        if ($toDelete === 0) {
            $this->info("No email logs older than {$days} days ({$cutoff->toDateTimeString()}) to purge.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[dry-run] Would delete {$toDelete} email log(s) older than {$days} days (cutoff: {$cutoff->toDateTimeString()}).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Purged {$deleted} email log(s) older than {$days} days.");
        Log::info('Email log purge completed', [
            'deleted' => $deleted,
            'days' => $days,
            'cutoff' => $cutoff->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
