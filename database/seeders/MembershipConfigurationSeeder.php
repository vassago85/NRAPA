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
     */
    protected function seedDocumentTypes(): void
    {
        $documentTypes = [
            [
                'slug' => 'identity-document',
                'name' => 'Identity Document',
                'description' => 'South African ID document or passport',
                'expiry_months' => null, // Permanent
                'archive_months' => 12,
                'sort_order' => 1,
            ],
            [
                'slug' => 'proof-of-address',
                'name' => 'Proof of Address',
                'description' => 'Utility bill or bank statement not older than 3 months',
                'expiry_months' => 3,
                'archive_months' => 12,
                'sort_order' => 2,
            ],
            [
                'slug' => 'firearm-competency',
                'name' => 'Firearm Competency Certificate',
                'description' => 'Valid SAPS firearm competency certificate',
                'expiry_months' => 120, // 10 years
                'archive_months' => 12,
                'sort_order' => 3,
            ],
            [
                'slug' => 'shooting-activity-evidence',
                'name' => 'Shooting Activity Evidence',
                'description' => 'Evidence of shooting activity (range attendance, competition results)',
                'expiry_months' => 12, // Per membership year
                'archive_months' => 12,
                'sort_order' => 4,
            ],
            [
                'slug' => 'dedicated-activity-log',
                'name' => 'Dedicated Status Activity Log',
                'description' => 'Activity log for dedicated status application',
                'expiry_months' => 12,
                'archive_months' => 24,
                'sort_order' => 5,
            ],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    /**
     * Seed certificate types.
     */
    protected function seedCertificateTypes(): void
    {
        $certificateTypes = [
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
            // Standard Annual Membership
            [
                'slug' => 'standard-annual',
                'name' => 'Standard Annual Membership',
                'description' => 'Standard annual membership with full benefits',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling', // 12 months from activation
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 350.00,
                'allows_dedicated_status' => true,
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'sort_order' => 1,
            ],
            // Dedicated Annual Membership
            [
                'slug' => 'dedicated-annual',
                'name' => 'Dedicated Annual Membership',
                'description' => 'Annual membership for dedicated hunters/sport shooters with enhanced benefits',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'expiry_rule' => 'rolling',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'annual',
                'price' => 500.00,
                'allows_dedicated_status' => true, // Already has dedicated status
                'requires_knowledge_test' => true,
                'discount_eligible' => true,
                'sort_order' => 2,
            ],
            // Lifetime Membership
            [
                'slug' => 'lifetime',
                'name' => 'Lifetime Membership',
                'description' => 'One-time payment for lifetime membership with all benefits',
                'duration_type' => 'lifetime',
                'duration_months' => null, // No expiry
                'requires_renewal' => false,
                'expiry_rule' => 'none',
                'expiry_month' => null,
                'expiry_day' => null,
                'pricing_model' => 'once_off',
                'price' => 5000.00,
                'allows_dedicated_status' => true,
                'requires_knowledge_test' => true,
                'discount_eligible' => false, // Already heavily discounted
                'sort_order' => 3,
            ],
            // Junior Membership (example of future type)
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
                'price' => 175.00, // 50% discount
                'allows_dedicated_status' => false, // Juniors cannot apply
                'requires_knowledge_test' => true,
                'discount_eligible' => false, // Already discounted
                'sort_order' => 4,
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
