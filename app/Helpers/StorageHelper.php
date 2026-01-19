<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Get the configured storage disk name.
     * Returns 'r2' if R2 is configured, otherwise the default disk.
     */
    public static function getDisk(): string
    {
        return config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
    }

    /**
     * Get the public storage disk name.
     * This is used for publicly accessible files like learning images.
     * Returns 'r2' if R2 is configured, otherwise 'public'.
     */
    public static function getPublicDisk(): string
    {
        return config('filesystems.disks.r2.key') ? 'r2' : 'public';
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
     * Uses temporary URLs for R2/S3, regular URLs for local storage.
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
            // Check if disk supports temporary URLs (S3/R2 do)
            if (in_array($disk, ['s3', 'r2'])) {
                return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
            }

            // For local/public disk, return regular URL
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            // If temporaryUrl fails, try regular URL
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Exception $e) {
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
