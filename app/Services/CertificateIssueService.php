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
     * Get default signatory details and assets from system settings.
     * Only includes fields that exist in the certificates table.
     */
    protected function getDefaultSignatoryData(): array
    {
        $data = [];
        
        // Check if columns exist before adding them
        if (\Illuminate\Support\Facades\Schema::hasColumn('certificates', 'signatory_name')) {
            $data['signatory_name'] = \App\Models\SystemSetting::get('default_signatory_name', null);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('certificates', 'signatory_title')) {
            $data['signatory_title'] = \App\Models\SystemSetting::get('default_signatory_title', null);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('certificates', 'signatory_signature_path')) {
            $data['signatory_signature_path'] = \App\Helpers\DocumentDataHelper::getDefaultSignaturePath();
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('certificates', 'commissioner_oaths_scan_path')) {
            $data['commissioner_oaths_scan_path'] = \App\Helpers\DocumentDataHelper::getDefaultCommissionerScanPath();
        }
        
        return $data;
    }

    /**
     * Issue a Dedicated Hunter Certificate.
     * Requires: approved dedicated status, good standing, valid documents, and activities up to date.
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

        // Check required documents are valid
        $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($user);
        if (count($missingDocs) > 0) {
            $docNames = implode(', ', array_column($missingDocs, 'name'));
            throw new \Exception("User is missing required valid documents: {$docNames}");
        }

        // Check activity requirements are met
        $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($user);
        if (!$activityCheck['met']) {
            throw new \Exception("User does not meet activity requirements: {$activityCheck['message']}");
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'dedicated-hunter-certificate'],
            [
                'name' => 'Dedicated Hunter Certificate',
                'description' => 'Official certificate confirming Dedicated Hunter Status with valid documents and activities up to date',
                'template' => 'documents.certificates.dedicated-status',
                'validity_months' => null, // Valid as long as membership is active and requirements met
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        // Create certificate
        $certificate = Certificate::create(array_merge([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null, // Valid as long as membership is active
        ], $this->getDefaultSignatoryData()));

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
     * Requires: approved dedicated status, good standing, valid documents, and activities up to date.
     */
    public function issueDedicatedSportCertificate(User $user, User $issuer): ?Certificate
    {
        // Check terms acceptance
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
            throw new \Exception('User must accept Terms & Conditions before certificates can be issued.');
        }

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

        // Check required documents are valid
        $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($user);
        if (count($missingDocs) > 0) {
            $docNames = implode(', ', array_column($missingDocs, 'name'));
            throw new \Exception("User is missing required valid documents: {$docNames}");
        }

        // Check activity requirements are met
        $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($user);
        if (!$activityCheck['met']) {
            throw new \Exception("User does not meet activity requirements: {$activityCheck['message']}");
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'dedicated-sport-certificate'],
            [
                'name' => 'Dedicated Sport Shooter Certificate',
                'description' => 'Official certificate confirming Dedicated Sport Shooter Status with valid documents and activities up to date',
                'template' => 'documents.certificates.dedicated-status',
                'validity_months' => null, // Valid as long as membership is active and requirements met
                'is_active' => true,
                'sort_order' => 11,
            ]
        );

        // Create certificate
        $certificate = Certificate::create(array_merge([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null,
        ], $this->getDefaultSignatoryData()));

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
     * Issue a Membership Certificate (proof of paid-up, active member in good standing).
     *
     * @param bool $skipChecks When true, skips terms and good-standing checks (used during admin approval).
     */
    public function issueMembershipCertificate(User $user, User $issuer, bool $skipChecks = false): ?Certificate
    {
        if (!$skipChecks) {
            // Check terms acceptance
            $activeTerms = \App\Models\TermsVersion::active();
            if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
                throw new \Exception('User must accept Terms & Conditions before certificates can be issued.');
            }

            // Check good standing
            if (!$this->standingService->isInGoodStanding($user)) {
                throw new \Exception('User is not in good standing.');
            }
        }

        $membership = $user->activeMembership;
        if (!$membership) {
            throw new \Exception('User does not have an active membership.');
        }

        // Get or create certificate type
        $certType = CertificateType::firstOrCreate(
            ['slug' => 'membership-certificate'],
            [
                'name' => 'Membership Certificate',
                'description' => 'Certificate confirming member is paid-up, active, and in good standing',
                'template' => 'documents.certificates.good-standing',
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
        $certificate = Certificate::create(array_merge([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => $validUntil,
        ], $this->getDefaultSignatoryData()));

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
        $certificate = Certificate::create(array_merge([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => $membership->expires_at,
        ], $this->getDefaultSignatoryData()));

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
        // Check terms acceptance
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
            throw new \Exception('User must accept Terms & Conditions before welcome letter can be issued.');
        }

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
        $certificate = Certificate::create(array_merge([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'certificate_type_id' => $certType->id,
            'issued_by' => $issuer->id,
            'valid_from' => now(),
            'valid_until' => null,
        ], $this->getDefaultSignatoryData()));

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
