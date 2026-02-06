<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Exception;

class BackupService
{
    /**
     * Create a complete backup of the system.
     *
     * @param string $dbPassword Database password for backup
     * @param string $storagePassword Storage password (if needed)
     * @return array ['success' => bool, 'message' => string, 'backup_path' => string|null]
     */
    public function createBackup(string $dbPassword, string $storagePassword = ''): array
    {
        try {
            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                throw new Exception("ZipArchive extension is not available. Please install php-zip extension.");
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupName = "nrapa_backup_{$timestamp}";
            $tempDir = storage_path("app/backups/temp/{$backupName}");
            
            // Create temporary directory
            File::ensureDirectoryExists($tempDir);
            
            // 1. Backup database
            $dbBackupPath = $this->backupDatabase($tempDir, $dbPassword);
            
            // 2. Export user data to CSV
            $csvPath = $this->exportUsersToCsv($tempDir);
            
            // 3. Backup all files
            $filesBackupPath = $this->backupFiles($tempDir);
            
            // 4. Create zip archive
            $zipPath = $this->createZipArchive($tempDir, $backupName);
            
            // 5. Upload to cloud storage
            $uploadedPath = $this->uploadToStorage($zipPath, $backupName, $storagePassword);
            
            // 6. Cleanup temp files
            File::deleteDirectory($tempDir);
            File::delete($zipPath);
            
            return [
                'success' => true,
                'message' => 'Backup created and uploaded successfully.',
                'backup_path' => $uploadedPath,
                'backup_name' => "{$backupName}.zip",
            ];
        } catch (Exception $e) {
            // Cleanup on error
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            if (isset($zipPath) && File::exists($zipPath)) {
                File::delete($zipPath);
            }
            
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
                'backup_path' => null,
            ];
        }
    }
    
    /**
     * Backup the database.
     */
    protected function backupDatabase(string $tempDir, string $dbPassword): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        $dbBackupPath = "{$tempDir}/database.sql";
        
        if ($connection === 'sqlite') {
            // SQLite backup
            $dbPath = $config['database'];
            
            // Handle in-memory database (used in tests)
            if ($dbPath === ':memory:' || !File::exists($dbPath)) {
                // Export database schema and data as SQL
                $sql = "-- NRAPA SQLite Database Backup\n";
                $sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
                
                // Get all tables
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    
                    // Get table schema
                    $createTable = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
                    if (!empty($createTable)) {
                        $sql .= $createTable[0]->sql . ";\n\n";
                    }
                    
                    // Get table data
                    $rows = DB::table($tableName)->get();
                    if ($rows->count() > 0) {
                        $columns = array_keys((array) $rows->first());
                        $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                        
                        $values = [];
                        foreach ($rows as $row) {
                            $rowArray = (array) $row;
                            $rowValues = array_map(function ($value) {
                                if ($value === null) {
                                    return 'NULL';
                                } elseif (is_numeric($value)) {
                                    return $value;
                                } else {
                                    return "'" . str_replace("'", "''", $value) . "'";
                                }
                            }, array_values($rowArray));
                            $values[] = "(" . implode(', ', $rowValues) . ")";
                        }
                        $sql .= implode(",\n", $values) . ";\n\n";
                    }
                }
                
                File::put($dbBackupPath, $sql);
            } else {
                // Copy physical database file
                File::copy($dbPath, $dbBackupPath);
            }
        } elseif (in_array($connection, ['mysql', 'mariadb'])) {
            // MySQL/MariaDB backup using mysqldump or Laravel's database dump
            $host = $config['host'];
            $port = $config['port'] ?? 3306;
            $database = $config['database'];
            $username = $config['username'];
            
            // Temporarily update config with provided password to verify connection
            $originalPassword = $config['password'];
            config(["database.connections.{$connection}.password" => $dbPassword]);
            DB::purge($connection);
            
            // Verify password by testing connection
            try {
                DB::connection($connection)->getPdo();
            } catch (Exception $e) {
                // Restore original config
                config(["database.connections.{$connection}.password" => $originalPassword]);
                DB::purge($connection);
                throw new Exception("Database connection failed. Please verify the password.");
            }
            
            // Try using mysqldump command first (more reliable)
            $mysqldumpPath = $this->findMysqldumpPath();
            
            if ($mysqldumpPath) {
                // Use mysqldump command
                $command = sprintf(
                    '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --no-tablespaces %s > %s',
                    $mysqldumpPath,
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($dbPassword),
                    escapeshellarg($database),
                    escapeshellarg($dbBackupPath)
                );
                
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0 || !File::exists($dbBackupPath)) {
                    // Fall back to Laravel dump if mysqldump fails
                    $this->backupDatabaseUsingLaravel($connection, $dbBackupPath);
                } else {
                    // Verify backup file is not empty
                    if (File::size($dbBackupPath) === 0) {
                        $this->backupDatabaseUsingLaravel($connection, $dbBackupPath);
                    }
                }
            } else {
                // Use Laravel's database dump method
                $this->backupDatabaseUsingLaravel($connection, $dbBackupPath);
            }
            
            // Restore original config
            config(["database.connections.{$connection}.password" => $originalPassword]);
            DB::purge($connection);
        } else {
            throw new Exception("Unsupported database connection: {$connection}");
        }
        
        return $dbBackupPath;
    }
    
    /**
     * Export users table to CSV.
     */
    protected function exportUsersToCsv(string $tempDir): string
    {
        $csvPath = "{$tempDir}/users.csv";
        $file = fopen($csvPath, 'w');
        
        // Write CSV header
        $headers = [
            'id', 'name', 'email', 'role', 'email_verified_at', 'created_at', 'updated_at',
            'nominated_by', 'nominated_at', 'phone', 'id_number', 'date_of_birth',
            'address_line_1', 'address_line_2', 'city', 'province', 'postal_code', 'country'
        ];
        fputcsv($file, $headers);
        
        // Export user data (excluding sensitive fields like password)
        $users = DB::table('users')
            ->select($headers)
            ->orderBy('id')
            ->get();
        
        foreach ($users as $user) {
            fputcsv($file, (array) $user);
        }
        
        fclose($file);
        
        return $csvPath;
    }
    
    /**
     * Backup all files from storage directories.
     */
    protected function backupFiles(string $tempDir): string
    {
        $filesDir = "{$tempDir}/files";
        File::ensureDirectoryExists($filesDir);
        
        // Backup storage/app/public (learning center files)
        $publicPath = storage_path('app/public');
        if (File::exists($publicPath) && File::isDirectory($publicPath)) {
            $publicFiles = File::allFiles($publicPath);
            if (count($publicFiles) > 0) {
                File::copyDirectory($publicPath, "{$filesDir}/public");
            }
        }
        
        // Backup storage/app/private (sensitive documents)
        $privatePath = storage_path('app/private');
        if (File::exists($privatePath) && File::isDirectory($privatePath)) {
            $privateFiles = File::allFiles($privatePath);
            if (count($privateFiles) > 0) {
                File::copyDirectory($privatePath, "{$filesDir}/private");
            }
        }
        
        return $filesDir;
    }
    
    /**
     * Create a zip archive of all backup files.
     */
    protected function createZipArchive(string $tempDir, string $backupName): string
    {
        $zipPath = storage_path("app/backups/{$backupName}.zip");
        File::ensureDirectoryExists(dirname($zipPath));
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create zip archive.");
        }
        
        // Add all files from temp directory
        $files = File::allFiles($tempDir);
        foreach ($files as $file) {
            $relativePath = str_replace($tempDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }
        
        $zip->close();
        
        return $zipPath;
    }
    
    /**
     * Resolve the best available cloud disk for backups.
     *
     * Priority: r2_backup (dedicated bucket) > r2 > s3 > local
     * Returns the disk name string, or null if only local is available.
     */
    protected function getBackupDisk(): ?string
    {
        // Apply any database-stored backup bucket config at runtime
        $this->applyBackupBucketConfig();
        
        // 1. Try dedicated R2 backup bucket (preferred)
        if (config('filesystems.disks.r2_backup.key') && config('filesystems.disks.r2_backup.bucket')) {
            try {
                Storage::disk('r2_backup')->path('');
                return 'r2_backup';
            } catch (Exception $e) {
                Log::warning('Backup: R2 backup disk unavailable, trying fallback', ['error' => $e->getMessage()]);
            }
        }
        
        // 2. Try main R2 bucket
        if (config('filesystems.disks.r2.key') && config('filesystems.disks.r2.bucket')) {
            try {
                Storage::disk('r2')->path('');
                return 'r2';
            } catch (Exception $e) {
                Log::warning('Backup: R2 disk unavailable, trying fallback', ['error' => $e->getMessage()]);
            }
        }
        
        // 3. Try S3/MinIO
        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            try {
                Storage::disk('s3')->path('');
                return 's3';
            } catch (Exception $e) {
                Log::warning('Backup: S3 disk unavailable, falling back to local', ['error' => $e->getMessage()]);
            }
        }
        
        // 4. No cloud disk available
        return null;
    }
    
    /**
     * Apply backup bucket configuration from database SystemSettings.
     *
     * Allows the owner to configure a dedicated backup bucket via the UI.
     * Falls back to env vars if no database config exists.
     */
    protected function applyBackupBucketConfig(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                return;
            }
            
            $backupBucket = \App\Models\SystemSetting::get('backup_r2_bucket');
            
            if ($backupBucket) {
                // Use shared R2 credentials with the dedicated backup bucket
                $key = \App\Models\SystemSetting::get('r2_access_key_id') ?: config('filesystems.disks.r2.key');
                $secret = \App\Models\SystemSetting::get('r2_secret_access_key') ?: config('filesystems.disks.r2.secret');
                $endpoint = \App\Models\SystemSetting::get('r2_endpoint') ?: config('filesystems.disks.r2.endpoint');
                $region = \App\Models\SystemSetting::get('r2_region', 'auto') ?: config('filesystems.disks.r2.region');
                
                config([
                    'filesystems.disks.r2_backup.key' => $key,
                    'filesystems.disks.r2_backup.secret' => $secret,
                    'filesystems.disks.r2_backup.bucket' => $backupBucket,
                    'filesystems.disks.r2_backup.endpoint' => $endpoint,
                    'filesystems.disks.r2_backup.region' => $region,
                ]);
            }
        } catch (Exception $e) {
            // Silently fail - database might not be available
        }
    }
    
    /**
     * Upload backup to cloud storage (R2/S3).
     */
    protected function uploadToStorage(string $zipPath, string $backupName, string $storagePassword): string
    {
        $diskName = $this->getBackupDisk();
        
        // If no cloud disk available, keep the local file
        if ($diskName === null) {
            Log::info('Backup: No cloud disk configured, backup stored locally', ['path' => $zipPath]);
            return $zipPath;
        }
        
        // Upload to R2/S3
        $disk = Storage::disk($diskName);
        $remotePath = "backups/{$backupName}.zip";
        
        // Read file and upload
        $fileContents = File::get($zipPath);
        $disk->put($remotePath, $fileContents);
        
        Log::info("Backup: Full backup uploaded to {$diskName}", ['path' => $remotePath]);
        
        // Generate signed URL for download (valid for 1 hour)
        try {
            $url = $disk->temporaryUrl($remotePath, now()->addHour());
            return $url;
        } catch (Exception $e) {
            // R2 doesn't support temporaryUrl without custom domain — return the path
            return $remotePath;
        }
    }
    
    /**
     * Backup database using Laravel's built-in methods.
     */
    protected function backupDatabaseUsingLaravel(string $connection, string $backupPath): void
    {
        // Get all tables
        $tables = DB::connection($connection)->select('SHOW TABLES');
        $databaseName = DB::connection($connection)->getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";
        
        $sql = "-- NRAPA Database Backup\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Get table structure
            $createTable = DB::connection($connection)->select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
            
            // Get table data
            $rows = DB::connection($connection)->table($tableName)->get();
            if ($rows->count() > 0) {
                $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $escapedValues = array_map(function($value) use ($connection) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return DB::connection($connection)->getPdo()->quote($value);
                    }, array_values($rowArray));
                    $values[] = '(' . implode(',', $escapedValues) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        File::put($backupPath, $sql);
    }
    
    /**
     * Find mysqldump executable path.
     */
    protected function findMysqldumpPath(): ?string
    {
        // Common paths for mysqldump
        $paths = [
            'mysqldump', // In PATH
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30\\bin\\mysqldump.exe', // Laragon default
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // XAMPP
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe', // WAMP
            '/usr/bin/mysqldump', // Linux
            '/usr/local/bin/mysqldump', // macOS
        ];
        
        foreach ($paths as $path) {
            if (is_executable($path) || (PHP_OS_FAMILY === 'Windows' && file_exists($path))) {
                return $path;
            }
        }
        
        // Try to find in PATH
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        exec("{$which} mysqldump 2>&1", $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        return null;
    }
    
    /**
     * Create a database-only backup (for scheduled daily backups).
     *
     * @param string $dbPassword Database password
     * @return array ['success' => bool, 'message' => string, 'backup_path' => string|null]
     */
    public function createDatabaseBackup(string $dbPassword): array
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupName = "nrapa_db_backup_{$timestamp}";
            $tempDir = storage_path("app/backups/temp/{$backupName}");
            
            // Create temporary directory
            File::ensureDirectoryExists($tempDir);
            
            // Backup database only
            $dbBackupPath = $this->backupDatabase($tempDir, $dbPassword);
            
            // Compress the SQL file
            $compressedPath = $this->compressDatabaseBackup($dbBackupPath, $backupName);
            
            // Upload to cloud storage
            $uploadedPath = $this->uploadDatabaseBackupToStorage($compressedPath, $backupName);
            
            // Cleanup temp files
            File::deleteDirectory($tempDir);
            File::delete($compressedPath);
            
            // Cleanup old backups (keep only 30 days)
            $this->cleanupOldBackups();
            
            return [
                'success' => true,
                'message' => 'Database backup created and uploaded successfully.',
                'backup_path' => $uploadedPath,
                'backup_name' => "{$backupName}.sql.gz",
            ];
        } catch (Exception $e) {
            // Cleanup on error
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            if (isset($compressedPath) && File::exists($compressedPath)) {
                File::delete($compressedPath);
            }
            
            return [
                'success' => false,
                'message' => 'Database backup failed: ' . $e->getMessage(),
                'backup_path' => null,
            ];
        }
    }
    
    /**
     * Compress database backup file using gzip.
     */
    protected function compressDatabaseBackup(string $sqlPath, string $backupName): string
    {
        $compressedPath = storage_path("app/backups/{$backupName}.sql.gz");
        File::ensureDirectoryExists(dirname($compressedPath));
        
        // Read SQL file and compress
        $sqlContent = File::get($sqlPath);
        $compressed = gzencode($sqlContent, 9); // Level 9 compression
        
        File::put($compressedPath, $compressed);
        
        return $compressedPath;
    }
    
    /**
     * Upload database backup to cloud storage.
     */
    protected function uploadDatabaseBackupToStorage(string $backupPath, string $backupName): string
    {
        $diskName = $this->getBackupDisk();
        
        // If no cloud disk available, keep the local file
        if ($diskName === null) {
            Log::info('Backup: No cloud disk configured, DB backup stored locally', ['path' => $backupPath]);
            return $backupPath;
        }
        
        // Upload to R2/S3
        $disk = Storage::disk($diskName);
        $remotePath = "backups/database/{$backupName}.sql.gz";
        
        // Read file and upload
        $fileContents = File::get($backupPath);
        $disk->put($remotePath, $fileContents);
        
        Log::info("Backup: Database backup uploaded to {$diskName}", ['path' => $remotePath]);
        
        return $remotePath;
    }
    
    /**
     * Cleanup old backups, keeping only the last 30 days.
     */
    public function cleanupOldBackups(): void
    {
        $cutoffDate = now()->subDays(30);
        
        // Always cleanup local backups
        $backupDir = storage_path('app/backups');
        if (File::exists($backupDir)) {
            $files = File::glob("{$backupDir}/nrapa_db_backup_*.sql.gz");
            foreach ($files as $file) {
                $fileTime = File::lastModified($file);
                if ($fileTime < $cutoffDate->timestamp) {
                    File::delete($file);
                }
            }
        }
        
        // Also cleanup cloud storage backups
        $diskName = $this->getBackupDisk();
        if ($diskName !== null) {
            try {
                $disk = Storage::disk($diskName);
                $backupPath = 'backups/database/';
                
                if ($disk->exists($backupPath)) {
                    $files = $disk->files($backupPath);
                    foreach ($files as $file) {
                        if (strpos($file, 'nrapa_db_backup_') === false) {
                            continue; // Skip non-database backups
                        }
                        
                        $lastModified = $disk->lastModified($file);
                        if ($lastModified < $cutoffDate->timestamp) {
                            $disk->delete($file);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Backup cleanup: Failed to cleanup cloud backups', ['disk' => $diskName, 'error' => $e->getMessage()]);
            }
        }
    }
    
    /**
     * Get backup file size in human-readable format.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
