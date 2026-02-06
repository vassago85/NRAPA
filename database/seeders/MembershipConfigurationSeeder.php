<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use App\Models\DocumentType;
use App\Models\MembershipType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MembershipConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedDocumentTypes();
        $this->seedCertificateTypes();
        $this->seedMembershipTypes();
        $this->linkMembershipTypesToDocuments();
        $this->linkMembershipTypesToCertificates();
    }

    /**
     * Seed roles.
     */
    protected function seedRoles(): void
    {
        $roles = [
            [
                'slug' => 'super-admin',
                'name' => 'Super Administrator',
                'description' => 'Full system access',
                'permissions' => ['*'],
                'is_system' => true,
            ],
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'description' => 'Administrative access for membership management',
                'permissions' => [
                    'members.view', 'members.create', 'members.edit', 'members.approve',
                    'documents.view', 'documents.verify',
                    'certificates.view', 'certificates.issue',
                    'tests.view', 'tests.mark',
                    'motivations.view', 'motivations.process',
                    'reports.view',
                ],
                'is_system' => true,
            ],
            [
                'slug' => 'moderator',
                'name' => 'Moderator',
                'description' => 'Limited administrative access',
                'permissions' => [
                    'members.view',
                    'documents.view', 'documents.verify',
                    'tests.view', 'tests.mark',
                ],
                'is_system' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }

    /**
     * Seed document types.
     * 
     * Simplified to only 3 core member documents:
     * - ID (Identity Document)
     * - Proof of Address
     * - Competency (Firearm Competency Certificate with issue date)
     * 
     * Other document types are handled elsewhere:
     * - Firearm Licence → Virtual Safe
     * - Activity Evidence → Activities page
     * - Safe photos, character references, SAPS forms → Endorsement requests
     */
    protected function seedDocumentTypes(): void
    {
        $documentTypes = [
            // Core Member Documents
            [
                'slug' => 'identity-document',
                'name' => 'ID',
                'description' => 'South African ID document or passport (certified copy)',
                'expiry_months' => null, // Permanent
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'proof-of-address',
                'name' => 'Proof of Address',
                'description' => 'Utility bill or bank statement not older than 3 months',
                'expiry_months' => 3,
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'firearm-competency',
                'name' => 'Competency',
                'description' => 'Valid SAPS firearm competency certificate',
                'expiry_months' => 120, // 10 years
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 3,
            ],
            // Firearm Licence is handled via Virtual Safe, not general documents
            // [
            //     'slug' => 'firearm-licence',
            //     'name' => 'Firearm Licence',
            //     'description' => 'Current SAPS firearm licence card (for Virtual Safe)',
            //     'expiry_months' => 60, // 5 years typical
            //     'archive_months' => 24,
            //     'is_active' => false,
            //     'sort_order' => 4,
            // ],
        ];

        $activeSlugs = array_column($documentTypes, 'slug');

        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        // Deactivate ALL document types except the 3 core ones (ID, POA, Competency)
        // Firearm licences are handled via Virtual Safe, not general documents
        // Activities evidence is uploaded through the Activities feature
        DocumentType::whereNotIn('slug', $activeSlugs)->update(['is_active' => false]);
    }

    /**
     * Seed certificate types.
     */
    protected function seedCertificateTypes(): void
    {
        $certificateTypes = [
            // Existing types
            [
                'slug' => 'membership-certificate',
                'name' => 'Membership Certificate',
                'description' => 'Certificate confirming active NRAPA membership',
                'template' => 'certificates.membership',
                'validity_months' => 12, // Typically valid for membership period
                'sort_order' => 1,
            ],
            [
                'slug' => 'dedicated-status-certificate',
                'name' => 'Dedicated Status Certificate',
                'description' => 'Certificate confirming dedicated hunter/sport shooter status',
                'template' => 'certificates.dedicated',
                'validity_months' => 12,
                'sort_order' => 2,
            ],
            [
                'slug' => 'endorsement-letter',
                'name' => 'Endorsement Letter',
                'description' => 'Letter of endorsement for firearm applications',
                'template' => 'certificates.endorsement',
                'validity_months' => 6,
                'sort_order' => 3,
            ],
            [
                'slug' => 'confirmation-letter',
                'name' => 'Confirmation Letter',
                'description' => 'Letter confirming membership status',
                'template' => 'certificates.confirmation',
                'validity_months' => 3,
                'sort_order' => 4,
            ],
            // New official document types
            [
                'slug' => 'dedicated-hunter-certificate',
                'name' => 'Dedicated Hunter Certificate',
                'description' => 'Official certificate confirming Dedicated Hunter Status with valid documents and activities up to date',
                'template' => 'documents.certificates.dedicated-status',
                'validity_months' => null, // Valid as long as membership is active and requirements met
                'sort_order' => 10,
            ],
            [
                'slug' => 'dedicated-sport-certificate',
                'name' => 'Dedicated Sport Shooter Certificate',
                'description' => 'Official certificate confirming Dedicated Sport Shooter Status with valid documents and activities up to date',
                'template' => 'documents.certificates.dedicated-status',
                'validity_months' => null, // Valid as long as membership is active and requirements met
                'sort_order' => 11,
            ],
            [
                'slug' => 'membership-certificate',
                'name' => 'Membership Certificate',
                'description' => 'Certificate confirming member is paid-up, active, and in good standing',
                'template' => 'documents.certificates.good-standing',
                'validity_months' => 12,
                'sort_order' => 12,
            ],
            [
                'slug' => 'membership-card',
                'name' => 'Membership Card',
                'description' => 'NRAPA membership identification card (simple card format, wallet compatible)',
                'template' => 'documents.membership-card',
                'validity_months' => null, // Valid as long as membership is active
                'sort_order' => 13,
            ],
            [
                'slug' => 'welcome-letter',
                'name' => 'Welcome Letter',
                'description' => 'Welcome letter for new members',
                'template' => 'documents.welcome-letter',
                'validity_months' => null, // Informational, no expiry
                'sort_order' => 14,
            ],
        ];

        foreach ($certificateTypes as $type) {
            CertificateType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    /**
     * Seed membership types with attribute-driven configuration.
     */
    protected function seedMembershipTypes(): void
    {
        $membershipTypes = [
            // Dedicated Sport Shooter
            [
                'slug' => 'dedicated-sport',
                'name' => 'Dedicated Sport Shooter',
                'description' => 'Annual membership for dedicated sport shooters. Includes full platform access, Virtual Safe, Virtual Loading Bench, and sport shooting learning content.',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 750.00,
                'allows_dedicated_status' => true,
                'dedicated_type' => MembershipType::DEDICATED_TYPE_SPORT,
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'is_active' => true,
                'is_featured' => false,
                'display_on_landing' => true,
                'sort_order' => 1,
            ],
            // Dedicated Hunter
            [
                'slug' => 'dedicated-hunter',
                'name' => 'Dedicated Hunter',
                'description' => 'Annual membership for dedicated hunters. Includes full platform access, Virtual Safe, Virtual Loading Bench, and hunting learning content.',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 750.00,
                'allows_dedicated_status' => true,
                'dedicated_type' => MembershipType::DEDICATED_TYPE_HUNTER,
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'is_active' => true,
                'is_featured' => false,
                'display_on_landing' => true,
                'sort_order' => 2,
            ],
            // Dedicated Hunter & Sport Shooter (Both)
            [
                'slug' => 'dedicated-both',
                'name' => 'Dedicated Hunter & Sport Shooter',
                'description' => 'Annual membership for both dedicated hunters and sport shooters. Full access to all platform features, learning content, and knowledge tests.',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 1150.00,
                'allows_dedicated_status' => true,
                'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'is_active' => true,
                'is_featured' => true, // Featured membership
                'display_on_landing' => true,
                'sort_order' => 3,
            ],
            // Standard Annual Membership (kept for existing users)
            [
                'slug' => 'standard-annual',
                'name' => 'Standard Annual Membership',
                'description' => 'Standard annual membership with full benefits',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 350.00,
                'allows_dedicated_status' => true,
                'dedicated_type' => null,
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'is_active' => false, // Disabled - kept for legacy
                'is_featured' => false,
                'display_on_landing' => false,
                'sort_order' => 10,
            ],
            // Lifetime Membership
            [
                'slug' => 'lifetime',
                'name' => 'Lifetime Membership',
                'description' => 'One-time payment for lifetime membership with all benefits',
                'duration_type' => 'lifetime',
                'duration_months' => null,
                'requires_renewal' => false,
                'expiry_rule' => 'none',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'once_off',
                'price' => 5000.00,
                'allows_dedicated_status' => true,
                'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
                'requires_knowledge_test' => true,
                'discount_eligible' => false,
                'is_active' => false, // Disabled - kept for legacy
                'is_featured' => false,
                'display_on_landing' => false,
                'sort_order' => 11,
            ],
            // Junior Membership
            [
                'slug' => 'junior-annual',
                'name' => 'Junior Annual Membership',
                'description' => 'Annual membership for members under 21',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 175.00,
                'allows_dedicated_status' => false,
                'dedicated_type' => null,
                'requires_knowledge_test' => true,
                'discount_eligible' => false,
                'is_active' => false, // Disabled - kept for legacy
                'is_featured' => false,
                'display_on_landing' => false,
                'sort_order' => 12,
            ],
        ];

        foreach ($membershipTypes as $type) {
            MembershipType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    /**
     * Link membership types to required document types.
     */
    protected function linkMembershipTypesToDocuments(): void
    {
        $links = [
            'standard-annual' => [
                'identity-document' => true,
                'proof-of-address' => true,
                'firearm-competency' => false, // Optional
            ],
            'dedicated-annual' => [
                'identity-document' => true,
                'proof-of-address' => true,
                'firearm-competency' => true, // Required for dedicated
                'dedicated-activity-log' => true,
            ],
            'lifetime' => [
                'identity-document' => true,
                'proof-of-address' => true,
                'firearm-competency' => false,
            ],
            'junior-annual' => [
                'identity-document' => true,
                'proof-of-address' => true,
            ],
        ];

        foreach ($links as $membershipSlug => $documents) {
            $membershipType = MembershipType::where('slug', $membershipSlug)->first();
            if (! $membershipType) {
                continue;
            }

            $syncData = [];
            foreach ($documents as $documentSlug => $isRequired) {
                $documentType = DocumentType::where('slug', $documentSlug)->first();
                if ($documentType) {
                    $syncData[$documentType->id] = ['is_required' => $isRequired];
                }
            }

            $membershipType->documentTypes()->sync($syncData);
        }
    }

    /**
     * Link membership types to certificate entitlements.
     */
    protected function linkMembershipTypesToCertificates(): void
    {
        $links = [
            'standard-annual' => [
                'membership-certificate' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'endorsement-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'confirmation-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'dedicated-status-certificate' => ['requires_dedicated_status' => true, 'requires_active_membership' => true],
            ],
            'dedicated-annual' => [
                'membership-certificate' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'dedicated-status-certificate' => ['requires_dedicated_status' => false, 'requires_active_membership' => true], // Auto-included
                'endorsement-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'confirmation-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
            ],
            'lifetime' => [
                'membership-certificate' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'endorsement-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'confirmation-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'dedicated-status-certificate' => ['requires_dedicated_status' => true, 'requires_active_membership' => true],
            ],
            'junior-annual' => [
                'membership-certificate' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
                'confirmation-letter' => ['requires_dedicated_status' => false, 'requires_active_membership' => true],
            ],
        ];

        foreach ($links as $membershipSlug => $certificates) {
            $membershipType = MembershipType::where('slug', $membershipSlug)->first();
            if (! $membershipType) {
                continue;
            }

            $syncData = [];
            foreach ($certificates as $certificateSlug => $pivot) {
                $certificateType = CertificateType::where('slug', $certificateSlug)->first();
                if ($certificateType) {
                    $syncData[$certificateType->id] = $pivot;
                }
            }

            $membershipType->certificateTypes()->sync($syncData);
        }
    }
}
