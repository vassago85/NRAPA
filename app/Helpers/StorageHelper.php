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
     * Get the public storage disk name (for learning images, etc.).
     * Uses R2 public bucket if configured, otherwise falls back to 'public'.
     */
    public static function getPublicDisk(): string
    {
        // Always use public/local storage for local/development/testing environments
        if (app()->environment(['local', 'development', 'testing'])) {
            return 'public';
        }
        
        if (config('filesystems.disks.r2_public.key')) {
            return 'r2_public';
        }
        
        if (config('filesystems.disks.s3.key')) {
            return 's3';
        }
        
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
     * Get a URL for a public file (learning images, etc.).
     * Uses direct public URL for R2 public bucket.
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
            // For R2 public bucket, use the public URL directly
            if ($disk === 'r2_public') {
                $publicUrl = config('filesystems.disks.r2_public.url');
                if (!empty($publicUrl)) {
                    return rtrim($publicUrl, '/') . '/' . ltrim($path, '/');
                }
            }

            // For local/public disk, return regular URL
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            \Log::error('StorageHelper::getUrl failed', ['path' => $path, 'disk' => $disk, 'error' => $e->getMessage()]);
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
