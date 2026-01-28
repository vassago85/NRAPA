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
            $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
            if (Storage::disk($disk)->exists($logoPath)) {
                return StorageHelper::getUrl($logoPath);
            }
        }
        
        // Fallback to public logo if exists (check common locations)
        $logoFiles = ['logo.png', 'nrapa-logo.png', 'logo.svg', 'nrapa-logo.svg', 'images/logo.png', 'images/nrapa-logo.png'];
        foreach ($logoFiles as $logoFile) {
            if (file_exists(public_path($logoFile))) {
                return asset($logoFile);
            }
        }
        
        return null;
    }
}
