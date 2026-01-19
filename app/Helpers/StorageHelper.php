<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Get the configured storage disk name.
     * Checks for R2, S3, or falls back to default disk.
     */
    public static function getDisk(): string
    {
        // Check R2 first (user-configured cloud storage)
        if (config('filesystems.disks.r2.key')) {
            return 'r2';
        }
        
        // Check S3/Minio (docker environment)
        if (config('filesystems.disks.s3.key')) {
            return 's3';
        }
        
        return config('filesystems.default');
    }

    /**
     * Get the public storage disk name.
     * This is used for publicly accessible files like learning images.
     * Checks for R2, S3, or falls back to 'public'.
     */
    public static function getPublicDisk(): string
    {
        // Check R2 first (user-configured cloud storage)
        if (config('filesystems.disks.r2.key')) {
            return 'r2';
        }
        
        // Check S3/Minio (docker environment)
        if (config('filesystems.disks.s3.key')) {
            return 's3';
        }
        
        return 'public';
    }

    /**
     * Store a file to the configured disk.
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
     * Delete a file from the configured disk.
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
     * Get a URL for a file.
     * Uses public URL for R2 if configured, otherwise falls back to signed URLs.
     *
     * @param string|null $path The file path
     * @param int $expirationMinutes Expiration time for temporary URLs (default: 60)
     * @return string|null
     */
    public static function getUrl(?string $path, int $expirationMinutes = 60): ?string
    {
        if (empty($path)) {
            return null;
        }

        $disk = static::getPublicDisk();

        try {
            // For R2, prefer the public URL if configured
            if ($disk === 'r2') {
                $publicUrl = config('filesystems.disks.r2.url');
                if (!empty($publicUrl)) {
                    return rtrim($publicUrl, '/') . '/' . ltrim($path, '/');
                }
            }
            
            // For S3/R2 without public URL, try signed temporary URL
            if (in_array($disk, ['s3', 'r2'])) {
                try {
                    return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
                } catch (\Exception $e) {
                    return Storage::disk($disk)->url($path);
                }
            }

            // For local/public disk, return regular URL
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            // Last resort: try regular URL
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Exception $e) {
                \Log::error('StorageHelper::getUrl failed', ['path' => $path, 'disk' => $disk, 'error' => $e->getMessage()]);
                return null;
            }
        }
    }

    /**
     * Check if a file exists on the configured disk.
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
