<?php

namespace App\Helpers;

use App\Models\Certificate;
use App\Models\EndorsementRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Membership;
use Illuminate\Support\Facades\Storage;

class DocumentDataHelper
{
    /**
     * Get FAR accreditation numbers from system settings.
     */
    public static function getFarNumbers(): array
    {
        // Try to get from system settings, with fallback to hardcoded values if not found
        $sport = SystemSetting::get('far_sport_number', null);
        $hunting = SystemSetting::get('far_hunting_number', null);
        
        // If settings don't exist or return null/empty, use hardcoded defaults
        if (empty($sport) || $sport === 'N/A') {
            $sport = '1300122'; // Default FAR Sport Shooting number
        }
        if (empty($hunting) || $hunting === 'N/A') {
            $hunting = '1300127'; // Default FAR Hunting number
        }
        
        return [
            'sport' => $sport,
            'hunting' => $hunting,
        ];
    }

    /**
     * Get logo URL for documents.
     */
    public static function getLogoUrl(): ?string
    {
        return DocumentHelper::getLogoUrl();
    }

    /**
     * Get QR code URL for a certificate.
     */
    public static function getQrCodeUrl(Certificate $certificate, int $size = 200): string
    {
        $verificationUrl = $certificate->getVerificationUrl();
        return QrCodeHelper::generateUrl($verificationUrl, $size);
    }

    /**
     * Get QR code URL for an endorsement request.
     */
    public static function getEndorsementQrCodeUrl(EndorsementRequest $request, int $size = 200): string
    {
        // Endorsements use letter_reference for verification, not qr_code
        if ($request->letter_reference) {
            $verificationUrl = url('/verify/endorsement/' . $request->letter_reference);
        } else {
            // Fallback if no letter reference yet
            $verificationUrl = url('/verify/endorsement/' . $request->uuid);
        }
        return QrCodeHelper::generateUrl($verificationUrl, $size);
    }

    /**
     * Get signature image HTML (safe, server-generated).
     */
    public static function getSignatureImageHtml(?string $signaturePath): string
    {
        if (!$signaturePath) {
            return 'Signature image (transparent PNG)';
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
        
        if (!Storage::disk($disk)->exists($signaturePath)) {
            return 'Signature image (transparent PNG)';
        }

        $url = StorageHelper::getUrl($signaturePath);
        if (!$url) {
            return 'Signature image (transparent PNG)';
        }

        return '<img src="' . e($url) . '" alt="Signature" />';
    }

    /**
     * Get commissioner of oaths scan HTML (safe, server-generated).
     */
    public static function getCommissionerScanHtml(?string $scanPath): string
    {
        if (!$scanPath) {
            return 'Commissioner of Oaths scan';
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'public' : 'r2_public';
        
        if (!Storage::disk($disk)->exists($scanPath)) {
            return 'Commissioner of Oaths scan';
        }

        $url = StorageHelper::getUrl($scanPath);
        if (!$url) {
            return 'Commissioner of Oaths scan';
        }

        // Check if it's a PDF
        $extension = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            return '<iframe src="' . e($url) . '" style="width:100%; height:100%; border:0;"></iframe>';
        }

        return '<img src="' . e($url) . '" alt="Commissioner of Oaths Scan" />';
    }

    /**
     * Get signatory name and title (with fallbacks).
     */
    public static function getSignatoryInfo(Certificate $certificate): array
    {
        return [
            'name' => $certificate->signatory_name ?? SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
            'title' => $certificate->signatory_title ?? SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
        ];
    }

    /**
     * Get default signatory signature path from system settings.
     */
    public static function getDefaultSignaturePath(): ?string
    {
        return SystemSetting::get('default_signatory_signature_path', null);
    }

    /**
     * Get default commissioner of oaths scan path from system settings.
     */
    public static function getDefaultCommissionerScanPath(): ?string
    {
        return SystemSetting::get('default_commissioner_oaths_scan_path', null);
    }

    /**
     * Get signatory info for endorsement requests.
     */
    public static function getEndorsementSignatoryInfo(EndorsementRequest $request): array
    {
        // Endorsements might have their own signatory or use system defaults
        return [
            'name' => SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
            'title' => SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
        ];
    }

    /**
     * Get NRAPA contact information.
     */
    public static function getContactInfo(): array
    {
        return [
            'website' => SystemSetting::get('nrapa_website', 'www.nrapa.co.za'),
            'email' => SystemSetting::get('nrapa_email', 'info@nrapa.co.za'),
            'tel' => SystemSetting::get('nrapa_tel', '+27 (0) 87 151 0988'),
            'fax' => SystemSetting::get('nrapa_fax', '+27 (0) 86 529 2791'),
            'postal_address' => SystemSetting::get('nrapa_postal_address', '1152 Meyer Street, Waverley, Pretoria'),
            'physical_address' => SystemSetting::get('nrapa_physical_address', '702 ESTHER STREET, GARSFONTEIN, PRETORIA'),
        ];
    }
}
