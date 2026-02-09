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
     * Falls back to the system default signature if no specific path is provided.
     * Uses base64 data URI for reliable rendering in both browsers and PDF generators.
     */
    public static function getSignatureImageHtml(?string $signaturePath): string
    {
        // Fall back to system default signature path
        if (!$signaturePath) {
            $signaturePath = static::getDefaultSignaturePath();
        }

        if (!$signaturePath) {
            return 'Signature image (transparent PNG)';
        }

        // Try multiple disks: document assets disk first, then public
        $disks = array_unique(array_filter([
            SystemSetting::get('document_assets_disk'),
            StorageHelper::getPublicDisk(),
        ]));

        foreach ($disks as $disk) {
            try {
                if (!Storage::disk($disk)->exists($signaturePath)) {
                    continue;
                }

                $contents = Storage::disk($disk)->get($signaturePath);
                $mimeType = Storage::disk($disk)->mimeType($signaturePath) ?: 'image/png';
                $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($contents);

                return '<img src="' . $dataUri . '" alt="Signature" />';
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
        }

        return 'Signature image (transparent PNG)';
    }

    /**
     * Get commissioner of oaths scan HTML (safe, server-generated).
     * Falls back to the system default commissioner scan if no specific path is provided.
     * Uses base64 data URI for reliable rendering in both browsers and PDF generators.
     */
    public static function getCommissionerScanHtml(?string $scanPath): string
    {
        // Fall back to system default commissioner scan path
        if (!$scanPath) {
            $scanPath = static::getDefaultCommissionerScanPath();
        }

        if (!$scanPath) {
            return 'Commissioner of Oaths scan';
        }

        // Try multiple disks: document assets disk first, then public
        $disks = array_unique(array_filter([
            SystemSetting::get('document_assets_disk'),
            StorageHelper::getPublicDisk(),
        ]));

        foreach ($disks as $disk) {
            try {
                if (!Storage::disk($disk)->exists($scanPath)) {
                    continue;
                }

                // Check if it's a PDF — PDFs can't be base64-embedded as images
                $extension = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
                if ($extension === 'pdf') {
                    $url = Storage::disk($disk)->url($scanPath);
                    return '<iframe src="' . e($url) . '" style="width:100%; height:100%; border:0;"></iframe>';
                }

                $contents = Storage::disk($disk)->get($scanPath);
                $mimeType = Storage::disk($disk)->mimeType($scanPath) ?: 'image/png';
                $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($contents);

                return '<img src="' . e($dataUri) . '" alt="Commissioner of Oaths Scan" />';
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
        }

        return 'Commissioner of Oaths scan';
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
