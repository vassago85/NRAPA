<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Membership;
use App\Models\User;

class VerificationService
{
    public function __construct(
        protected MembershipStandingService $standingService
    ) {}

    /**
     * Mask an identity / ID number for public verification pages (POPIA minimisation).
     * Longer values: first 4 + masked middle + last 4 (e.g. SA ID style).
     */
    public static function maskIdentityNumberForPublicDisplay(?string $id): ?string
    {
        if ($id === null || trim($id) === '') {
            return null;
        }

        $clean = preg_replace('/\s+/', '', trim($id));
        $len = strlen($clean);

        if ($len < 4) {
            return str_repeat('*', $len);
        }

        if ($len <= 8) {
            $keep = 2;

            return substr($clean, 0, $keep).str_repeat('*', max(3, $len - 2 * $keep)).substr($clean, -$keep);
        }

        return substr($clean, 0, 4).str_repeat('*', $len - 8).substr($clean, -4);
    }

    /**
     * Public-safe member fields for certificate / endorsement verification UIs.
     *
     * @param  Membership|null  $membership  Membership linked to the document; if null, active membership is resolved when possible.
     * @return array{display_name: string, id_masked: ?string, membership_number: string}|null
     */
    public function memberPublicDisplay(?User $user, ?Membership $membership = null): ?array
    {
        if (! $user) {
            return null;
        }

        if ($membership === null) {
            $membership = $user->activeMembership;
        }

        $membershipNumber = $membership?->membership_number;

        return [
            'display_name' => $user->name,
            'id_masked' => static::maskIdentityNumberForPublicDisplay($user->getIdNumber()),
            'membership_number' => $membershipNumber ?? 'N/A',
        ];
    }

    /**
     * Verify a certificate by QR code.
     *
     * Returns verification result with public-safe information.
     */
    public function verifyByQrCode(string $qrCode): array
    {
        $certificate = Certificate::where('qr_code', $qrCode)->first();

        if (! $certificate) {
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
                'membership-certificate',
                'paid-up-certificate',
                'good-standing-certificate',
                'membership-card',
            ]);

            if ($requiresGoodStanding) {
                $isInGoodStanding = $this->standingService->isInGoodStanding($user, $membership);
                if (! $isInGoodStanding) {
                    $isValid = false;
                    $reason = 'Member is not in good standing.';
                }
            }
        }

        $memberInfo = ($isValid && $user) ? $this->memberPublicDisplay($user, $membership) : null;

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
