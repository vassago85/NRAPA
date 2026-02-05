<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use App\Models\SystemSetting;

class StorageHelper
{
    /**
     * Get the private storage disk name (for sensitive documents).
     * Uses R2 private bucket if configured, otherwise falls back to default.
     */
    public static function getPrivateDisk(): string
    {
        // Always use local storage for local/development/testing environments
        if (app()->environment(['local', 'development', 'testing'])) {
            return 'local';
        }
        
        if (config('filesystems.disks.r2.key')) {
            return 'r2';
        }
        
        if (config('filesystems.disks.s3.key')) {
            return 's3';
        }
        
        return config('filesystems.default');
    }

    /**
     * Get the public storage disk name (for non-sensitive content).
     * Always uses local 'public' disk.
     */
    public static function getPublicDisk(): string
    {
        return 'public';
    }

    /**
     * Get the local storage disk name for learning center content.
     * Always uses local 'public' disk regardless of environment.
     * Learning center images should be stored locally on the server.
     */
    public static function getLearningCenterDisk(): string
    {
        return 'public';
    }

    /**
     * Store a file to the public disk (for learning images, etc.).
     *
     * @param \Illuminate\Http\UploadedFile|\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file
     * @param string $path The directory path to store the file
     * @return string The stored file path
     */
    public static function storeFile($file, string $path): string
    {
        $disk = static::getPublicDisk();
        return $file->store($path, $disk);
    }

    /**
     * Store a file for learning center (always uses local storage).
     * Learning center images are always stored locally on the server.
     *
     * @param \Illuminate\Http\UploadedFile|\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file
     * @param string $path The directory path to store the file
     * @return string The stored file path
     */
    public static function storeLearningCenterFile($file, string $path): string
    {
        $disk = static::getLearningCenterDisk();
        return $file->store($path, $disk);
    }

    /**
     * Delete a file from the public disk.
     *
     * @param string $path The file path to delete
     * @return bool
     */
    public static function deleteFile(string $path): bool
    {
        $disk = static::getPublicDisk();
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete a learning center file (always uses local storage).
     *
     * @param string $path The file path to delete
     * @return bool
     */
    public static function deleteLearningCenterFile(string $path): bool
    {
        $disk = static::getLearningCenterDisk();
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Get a URL for a public file (non-sensitive content).
     * Uses local public disk.
     *
     * @param string|null $path The file path
     * @return string|null
     */
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $disk = static::getPublicDisk();

        try {
            // Return regular URL for local/public disk
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            \Log::error('StorageHelper::getUrl failed', ['path' => $path, 'disk' => $disk, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get a URL for a learning center file (always uses local storage).
     * Learning center images are always served from local public disk.
     *
     * @param string|null $path The file path
     * @return string|null
     */
    public static function getLearningCenterUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $disk = static::getLearningCenterDisk();

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            \Log::error('StorageHelper::getLearningCenterUrl failed', ['path' => $path, 'disk' => $disk, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if a file exists on the public disk.
     *
     * @param string|null $path The file path
     * @return bool
     */
    public static function fileExists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $disk = static::getPublicDisk();
        return Storage::disk($disk)->exists($path);
    }
}
