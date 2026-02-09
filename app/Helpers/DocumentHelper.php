<?php

namespace App\Helpers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class DocumentHelper
{
    /**
     * Get the logo URL for documents.
     * 
     * Checks system settings first, then falls back to public assets.
     * 
     * @return string|null
     */
    public static function getLogoUrl(): ?string
    {
        // Check system settings for logo
        $logoPath = SystemSetting::get('document_logo_path');
        if ($logoPath) {
            // If it's a full URL, return it
            if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
                return $logoPath;
            }
            
            // If it's a storage path, get the URL
            $disk = StorageHelper::getPublicDisk();
            if (Storage::disk($disk)->exists($logoPath)) {
                return StorageHelper::getUrl($logoPath);
            }
        }
        
        // Fallback to public logo if exists (check common locations)
        // Check for exact filename first (case-insensitive, handle spaces)
        $publicDir = public_path();
        $logoFiles = [
            'NRAPA Logo.png',  // Exact match for existing file
            'NRAPA Logo.svg',
            'logo.png',
            'nrapa-logo.png',
            'logo.svg',
            'nrapa-logo.svg',
            'images/logo.png',
            'images/nrapa-logo.png',
            'images/NRAPA Logo.png',
        ];
        
        foreach ($logoFiles as $logoFile) {
            $fullPath = public_path($logoFile);
            if (file_exists($fullPath)) {
                return asset($logoFile);
            }
        }
        
        // Also check for any file starting with "logo" or "nrapa" (case-insensitive)
        if (is_dir($publicDir)) {
            $files = scandir($publicDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $lowerFile = strtolower($file);
                if (
                    (strpos($lowerFile, 'logo') !== false || strpos($lowerFile, 'nrapa') !== false) &&
                    in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'svg', 'jpg', 'jpeg', 'webp'])
                ) {
                    return asset($file);
                }
            }
        }
        
        return null;
    }
}
