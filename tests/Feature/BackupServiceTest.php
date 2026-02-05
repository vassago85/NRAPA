<?php

use App\Models\User;
use App\Models\SystemSetting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up test environment
    Storage::fake('local');
    Storage::fake('public');
    
    // Create test database entries
    User::factory()->count(5)->create();
});

test('backup service can create database backup for sqlite', function () {
    // Skip if ZipArchive is not available
    if (!class_exists('ZipArchive')) {
        $this->markTestSkipped('ZipArchive extension is not available');
    }
    
    $service = new BackupService();
    
    // For SQLite, password is not needed
    $result = $service->createBackup('', '');
    
    // Should succeed for SQLite (no password needed)
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('backup_path');
});

test('backup service exports users to csv', function () {
    $service = new BackupService();
    
    // Create a temporary directory
    $tempDir = storage_path('app/backups/temp/test_' . time());
    File::ensureDirectoryExists($tempDir);
    
    // Use reflection to call protected method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('exportUsersToCsv');
    $method->setAccessible(true);
    
    $csvPath = $method->invoke($service, $tempDir);
    
    expect(File::exists($csvPath))->toBeTrue();
    
    $content = File::get($csvPath);
    expect($content)->toContain('id,name,email');
    expect($content)->toContain(User::first()->email);
    
    // Cleanup
    File::deleteDirectory($tempDir);
});

test('backup service creates zip archive', function () {
    // Skip if ZipArchive is not available
    if (!class_exists('ZipArchive')) {
        $this->markTestSkipped('ZipArchive extension is not available');
    }
    
    $service = new BackupService();
    
    // Create test files
    $tempDir = storage_path('app/backups/temp/test_' . time());
    File::ensureDirectoryExists($tempDir);
    File::put($tempDir . '/test.txt', 'test content');
    
    // Use reflection to call protected method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('createZipArchive');
    $method->setAccessible(true);
    
    $zipPath = $method->invoke($service, $tempDir, 'test_backup');
    
    expect(File::exists($zipPath))->toBeTrue();
    expect(str_ends_with($zipPath, '.zip'))->toBeTrue();
    
    // Cleanup
    File::delete($zipPath);
    File::deleteDirectory($tempDir);
});

test('backup service formats bytes correctly', function () {
    $service = new BackupService();
    
    expect($service->formatBytes(1024))->toBe('1 KB');
    expect($service->formatBytes(1048576))->toBe('1 MB');
    expect($service->formatBytes(1024, 0))->toBe('1 KB');
});

test('backup service cleanup removes old backups', function () {
    $service = new BackupService();
    
    // Create old backup file
    $oldBackupPath = storage_path('app/backups/nrapa_db_backup_2025-01-01_00-00-00.sql.gz');
    File::ensureDirectoryExists(dirname($oldBackupPath));
    File::put($oldBackupPath, 'old backup');
    
    // Touch file to make it old (31 days ago)
    touch($oldBackupPath, now()->subDays(31)->timestamp);
    
    // Create recent backup
    $recentBackupPath = storage_path('app/backups/nrapa_db_backup_' . now()->format('Y-m-d_H-i-s') . '.sql.gz');
    File::put($recentBackupPath, 'recent backup');
    
    // Run cleanup
    $service->cleanupOldBackups();
    
    // Old backup should be deleted
    expect(File::exists($oldBackupPath))->toBeFalse();
    
    // Recent backup should still exist
    expect(File::exists($recentBackupPath))->toBeTrue();
    
    // Cleanup
    File::delete($recentBackupPath);
});

