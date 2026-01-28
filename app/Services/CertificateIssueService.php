<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Models\Membership;
use App\Contracts\DocumentRenderer;
use App\Services\MembershipStandingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateIssueService
{
    public function __construct(
        protected DocumentRenderer $renderer,
        protected MembershipStandingService $standingService
    ) {}

    /**
     * Issue a Dedicated Hunter Certificate.
     */
    public function issueDedicatedHunterCertificate(User $user, User $issuer): ?Certificate
    {
        // Check if user has dedicated hunter status
        $hasHunterStatus = $user->dedicatedStatusApplications()
            ->where('dedicated_type', 'hunter')
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->exists();

        if (!$hasHunterStatus) {
            throw new \Exception('User does not have approved dedicated hunter status.');
        }

        // Check good standing
        if (!$this->standingService->isInGoodStanding($user)) {
            throw new \Exception('User is not in good standing.');
        }

        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'dedicated-hunter-certificate'],
            [
                'name' => 'Dedicated Hunter Certificate',
                'description' => 'Official certificate confirming Dedicated Hunter Status',
                'template' => 'documents.dedicated-hunter',
                'validity_months' => null, // Valid as long as membership is active
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null, // Valid as long as membership is active
        ]);

        // Refresh to ensure all relationships are loaded
        $certificate->refresh();
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Generate document
        try {
            $filePath = $this->renderer->renderCertificate($certificate, $certType->template);
            
            // Calculate checksum (if file exists)
            $checksum = null;
            try {
                $fullPath = Storage::disk('local')->path($filePath);
                if (file_exists($fullPath)) {
                    $checksum = hash_file('sha256', $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to calculate certificate checksum', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }

            // Update certificate with file path and checksum
            $certificate->update([
                'file_path' => $filePath,
                'checksum' => $checksum,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate document', [
                'certificate_id' => $certificate->id,
                'template' => $certType->template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Still return certificate even if document generation fails
            // The certificate record is created, document can be regenerated later
        }

        return $certificate;
    }

    /**
     * Issue a Dedicated Sport Shooter Certificate.
     */
    public function issueDedicatedSportCertificate(User $user, User $issuer): ?Certificate
    {
        // Check if user has dedicated sport shooter status
        $hasSportStatus = $user->dedicatedStatusApplications()
            ->where('dedicated_type', 'sport_shooter')
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->exists();

        if (!$hasSportStatus) {
            throw new \Exception('User does not have approved dedicated sport shooter status.');
        }

        // Check good standing
        if (!$this->standingService->isInGoodStanding($user)) {
            throw new \Exception('User is not in good standing.');
        }

        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'dedicated-sport-certificate'],
            [
                'name' => 'Dedicated Sport Shooter Certificate',
                'description' => 'Official certificate confirming Dedicated Sport Shooter Status',
                'template' => 'documents.dedicated-sport',
                'validity_months' => null,
                'is_active' => true,
                'sort_order' => 11,
            ]
        );

        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null,
        ]);

        // Refresh to ensure all relationships are loaded
        $certificate->refresh();
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Generate document
        try {
            $filePath = $this->renderer->renderCertificate($certificate, $certType->template);
            
            // Calculate checksum (if file exists)
            $checksum = null;
            try {
                $fullPath = Storage::disk('local')->path($filePath);
                if (file_exists($fullPath)) {
                    $checksum = hash_file('sha256', $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to calculate certificate checksum', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }

            // Update certificate with file path and checksum
            $certificate->update([
                'file_path' => $filePath,
                'checksum' => $checksum,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate document', [
                'certificate_id' => $certificate->id,
                'template' => $certType->template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $certificate;
    }

    /**
     * Issue a Proof of Paid-Up Membership Certificate.
     */
    public function issuePaidUpCertificate(User $user, User $issuer): ?Certificate
    {
        // Check good standing
        if (!$this->standingService->isInGoodStanding($user)) {
            throw new \Exception('User is not in good standing.');
        }

        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'paid-up-certificate'],
            [
                'name' => 'Proof of Paid-Up Membership Certificate',
                'description' => 'Certificate confirming member is in good standing and paid-up',
                'template' => 'documents.paid-up',
                'validity_months' => 12, // Valid for 12 months or until membership expires
                'is_active' => true,
                'sort_order' => 12,
            ]
        );

        // Calculate validity
        $validUntil = $membership->expires_at 
            ? min(now()->addMonths(12), $membership->expires_at)
            : now()->addMonths(12);

        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => $validUntil,
        ]);

        // Refresh to ensure all relationships are loaded
        $certificate->refresh();
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Generate document
        try {
            $filePath = $this->renderer->renderCertificate($certificate, $certType->template);
            
            // Calculate checksum (if file exists)
            $checksum = null;
            try {
                $fullPath = Storage::disk('local')->path($filePath);
                if (file_exists($fullPath)) {
                    $checksum = hash_file('sha256', $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to calculate certificate checksum', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }

            // Update certificate with file path and checksum
            $certificate->update([
                'file_path' => $filePath,
                'checksum' => $checksum,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate document', [
                'certificate_id' => $certificate->id,
                'template' => $certType->template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $certificate;
    }

    /**
     * Issue a Membership Card.
     */
    public function issueMembershipCard(User $user, User $issuer): ?Certificate
    {
        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'membership-card'],
            [
                'name' => 'Membership Card',
                'description' => 'NRAPA membership identification card',
                'template' => 'documents.membership-card',
                'validity_months' => null, // Valid as long as membership is active
                'is_active' => true,
                'sort_order' => 13,
            ]
        );

        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => $membership->expires_at,
        ]);

        // Refresh to ensure all relationships are loaded
        $certificate->refresh();
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Generate document
        try {
            $filePath = $this->renderer->renderCertificate($certificate, $certType->template);
            
            // Calculate checksum (if file exists)
            $checksum = null;
            try {
                $fullPath = Storage::disk('local')->path($filePath);
                if (file_exists($fullPath)) {
                    $checksum = hash_file('sha256', $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to calculate certificate checksum', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }

            $certificate->update([
                'file_path' => $filePath,
                'checksum' => $checksum,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate document', [
                'certificate_id' => $certificate->id,
                'template' => $certType->template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $certificate;
    }

    /**
     * Issue a Welcome Letter.
     */
    public function issueWelcomeLetter(User $user, User $issuer): ?Certificate
    {
        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'welcome-letter'],
            [
                'name' => 'Welcome Letter',
                'description' => 'Welcome letter for new members',
                'template' => 'documents.welcome-letter',
                'validity_months' => null, // Informational, no expiry
                'is_active' => true,
                'sort_order' => 14,
            ]
        );

        // Create certificate (using Certificate model for consistency)
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null,
        ]);

        // Refresh to ensure all relationships are loaded
        $certificate->refresh();
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Generate document
        try {
            $filePath = $this->renderer->renderWelcomeLetter($user, $certType->template);
            
            // Calculate checksum (if file exists)
            $checksum = null;
            try {
                $fullPath = Storage::disk('local')->path($filePath);
                if (file_exists($fullPath)) {
                    $checksum = hash_file('sha256', $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to calculate welcome letter checksum', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }

            $certificate->update([
                'file_path' => $filePath,
                'checksum' => $checksum,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate welcome letter document', [
                'certificate_id' => $certificate->id,
                'user_id' => $user->id,
                'template' => $certType->template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Still return certificate even if document generation fails
        }

        return $certificate;
    }
}
