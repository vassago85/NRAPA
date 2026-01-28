<?php

namespace App\Services;

use App\Models\Certificate;
use App\Services\MembershipStandingService;

class VerificationService
{
    public function __construct(
        protected MembershipStandingService $standingService
    ) {}

    /**
     * Verify a certificate by QR code.
     * 
     * Returns verification result with public-safe information.
     */
    public function verifyByQrCode(string $qrCode): array
    {
        $certificate = Certificate::where('qr_code', $qrCode)->first();

        if (!$certificate) {
            return [
                'valid' => false,
                'reason' => 'Certificate not found.',
                'document_type' => null,
                'member_info' => null,
                'issued_date' => null,
            ];
        }

        $user = $certificate->user;
        $membership = $certificate->membership;
        $certType = $certificate->certificateType;

        // Check if certificate is valid
        $isValid = $certificate->isValid();

        // For certificates tied to membership, check good standing
        if ($membership && $certType) {
            // Some certificates require good standing
            $requiresGoodStanding = in_array($certType->slug, [
                'dedicated-hunter-certificate',
                'dedicated-sport-certificate',
                'paid-up-certificate',
                'membership-card',
            ]);

            if ($requiresGoodStanding) {
                $isInGoodStanding = $this->standingService->isInGoodStanding($user, $membership);
                if (!$isInGoodStanding) {
                    $isValid = false;
                    $reason = 'Member is not in good standing.';
                }
            }
        }

        // Build public-safe member info
        $memberInfo = null;
        if ($user) {
            $nameParts = explode(' ', $user->name);
            $initials = '';
            $surname = '';
            
            if (count($nameParts) > 0) {
                // First letter of first name
                $initials = strtoupper(substr($nameParts[0], 0, 1));
                // Add middle initials if any
                for ($i = 1; $i < count($nameParts) - 1; $i++) {
                    $initials .= strtoupper(substr($nameParts[$i], 0, 1));
                }
                // Last name
                $surname = end($nameParts);
            }

            // Mask membership number (show last 4 digits)
            $membershipNumber = $membership?->membership_number ?? 'N/A';
            $maskedNumber = strlen($membershipNumber) > 4 
                ? '****' . substr($membershipNumber, -4)
                : '****';

            $memberInfo = [
                'initials' => $initials,
                'surname' => $surname,
                'membership_number' => $maskedNumber,
            ];
        }

        return [
            'valid' => $isValid,
            'reason' => $isValid ? null : ($reason ?? 'Certificate is expired or revoked.'),
            'document_type' => $certType?->name ?? 'Unknown',
            'member_info' => $memberInfo,
            'issued_date' => $certificate->issued_at?->format('d M Y'),
            'valid_from' => $certificate->valid_from?->format('d M Y'),
            'valid_until' => $certificate->valid_until?->format('d M Y'),
        ];
    }
}
