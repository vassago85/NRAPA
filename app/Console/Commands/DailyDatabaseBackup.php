<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Models\SystemSetting;
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
        $this->info('Starting daily database backup...');
        
        // Get stored database password (encrypted)
        $encryptedPassword = SystemSetting::get('database_backup_password');
        
        if (empty($encryptedPassword)) {
            $this->error('Database backup password is not configured. Please set it in Owner Settings → System Backup.');
            Log::warning('Daily database backup skipped: password not configured');
            return Command::FAILURE;
        }
        
        try {
            // Decrypt the password
            $dbPassword = \Illuminate\Support\Facades\Crypt::decryptString($encryptedPassword);
        } catch (\Exception $e) {
            $this->error('Failed to decrypt database backup password. Please reconfigure it in Owner Settings → System Backup.');
            Log::error('Daily database backup failed: password decryption error', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
        
        try {
            $result = $backupService->createDatabaseBackup($dbPassword);
            
            if ($result['success']) {
                $this->info('✓ Database backup created successfully: ' . ($result['backup_name'] ?? 'backup.sql.gz'));
                $this->info('  Location: ' . ($result['backup_path'] ?? 'local storage'));
                Log::info('Daily database backup completed successfully', [
                    'backup_name' => $result['backup_name'] ?? null,
                    'backup_path' => $result['backup_path'] ?? null,
                ]);
                return Command::SUCCESS;
            } else {
                $this->error('✗ Database backup failed: ' . ($result['message'] ?? 'Unknown error'));
                Log::error('Daily database backup failed', [
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('✗ Database backup exception: ' . $e->getMessage());
            Log::error('Daily database backup exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
