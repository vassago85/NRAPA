<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DailyDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nrapa:daily-database-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a daily database backup and upload to cloud storage';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        // Check if daily backups are enabled (default: off)
        if (! SystemSetting::get('daily_backup_enabled', false)) {
            $this->info('Daily database backup is disabled. Enable it in Developer Dashboard.');
            Log::info('Daily database backup skipped: disabled via system setting');

            return Command::SUCCESS;
        }

        $this->info('Starting daily database backup...');

        // Use the live database connection password from config rather than a
        // separately-stored, manually-entered SystemSetting. The previous design
        // required an admin to paste the DB password into Owner Settings →
        // System Backup, which silently rotted whenever DB_PASSWORD changed
        // (e.g. server move) and produced daily ERROR logs reading
        // "Database connection failed. Please verify the password." If Laravel
        // can connect to the DB to run this command at all, that same
        // connection's password is by definition correct — there's no value
        // in maintaining a duplicate.
        //
        // We still allow an explicit override via the system setting for the
        // edge case where backups should run as a different DB user, but we
        // fall back to the live connection password automatically.
        $dbPassword = $this->resolveDatabasePassword();

        if ($dbPassword === null) {
            $this->error('Database backup failed: no password could be resolved from config or system setting.');
            Log::error('Daily database backup failed: unable to resolve database password', [
                'connection' => config('database.default'),
            ]);

            return Command::FAILURE;
        }

        try {
            $result = $backupService->createDatabaseBackup($dbPassword);

            if ($result['success']) {
                $this->info('✓ Database backup created successfully: '.($result['backup_name'] ?? 'backup.sql.gz'));
                $this->info('  Location: '.($result['backup_path'] ?? 'local storage'));
                Log::info('Daily database backup completed successfully', [
                    'backup_name' => $result['backup_name'] ?? null,
                    'backup_path' => $result['backup_path'] ?? null,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('✗ Database backup failed: '.($result['message'] ?? 'Unknown error'));
                Log::error('Daily database backup failed', [
                    'message' => $result['message'] ?? 'Unknown error',
                ]);

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('✗ Database backup exception: '.$e->getMessage());
            Log::error('Daily database backup exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Resolve the database password to use for the backup.
     *
     * Priority:
     *   1. Explicit override via the encrypted `database_backup_password`
     *      system setting (only used if it decrypts cleanly — drift-prone
     *      values are silently ignored and we fall back to the live config).
     *   2. The live database connection password from `config()`. This is
     *      always correct because it's what the running app uses to connect.
     *
     * Returns null only if neither source yields a non-empty string.
     */
    protected function resolveDatabasePassword(): ?string
    {
        $encrypted = SystemSetting::get('database_backup_password');

        if (! empty($encrypted)) {
            try {
                $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($encrypted);

                if (is_string($decrypted) && $decrypted !== '') {
                    return $decrypted;
                }
            } catch (\Exception $e) {
                Log::warning('Daily database backup: stored password could not be decrypted, falling back to live config', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $connection = config('database.default');
        $password = config("database.connections.{$connection}.password");

        if (is_string($password) && $password !== '') {
            return $password;
        }

        return null;
    }
}
