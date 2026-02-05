<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\FirearmCalibre;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Country;
use App\Models\DedicatedStatusApplication;
use App\Models\DocumentType;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\EventCategory;
use App\Models\FirearmType;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Province;
use App\Models\ShootingActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * TestMemberSeeder - Creates a fully-qualified test member for development/staging.
 * 
 * This seeder creates a test member with ALL requirements completed:
 * - Active membership (Dedicated Sport Shooter)
 * - Passed knowledge test
 * - Verified documents (ID, proof of address)
 * - Approved shooting activities
 * - Approved dedicated status
 * - Valid membership certificate
 * 
 * Test Credentials:
 * Email: testmember@nrapa.test
 * Password: TestMember2026!
 * 
 * WARNING: Only run in development/staging environments!
 */
class TestMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safety check - don't run in production
        if (app()->environment('production')) {
            $this->command->warn('TestMemberSeeder skipped - production environment detected.');
            return;
        }

        $this->command->info('Creating fully-qualified test member...');

        // 1. Create the test user
        $user = $this->createTestUser();
        $this->command->info("  ✓ User created: {$user->email}");

        // 2. Create active membership
        $membership = $this->createMembership($user);
        $this->command->info("  ✓ Membership created: {$membership->membership_number}");

        // 3. Create verified documents
        $this->createVerifiedDocuments($user);
        $this->command->info("  ✓ Verified documents created");

        // 4. Create passed knowledge test
        $this->createPassedKnowledgeTest($user);
        $this->command->info("  ✓ Knowledge test passed");

        // 5. Create approved shooting activities
        $this->createApprovedActivities($user);
        $this->command->info("  ✓ Approved activities created");

        // 6. Create approved dedicated status
        $dedicatedStatus = $this->createApprovedDedicatedStatus($user, $membership);
        $this->command->info("  ✓ Dedicated status approved");

        // 7. Create membership certificate
        $this->createCertificates($user, $membership);
        $this->command->info("  ✓ Certificates issued");

        // 8. Create approved endorsement letter
        $endorsementRequest = $this->createApprovedEndorsementLetter($user, $membership);
        if ($endorsementRequest && $endorsementRequest->letter_reference) {
            $this->command->info("  ✓ Approved endorsement letter created: {$endorsementRequest->letter_reference}");
        } else {
            $letterRef = $endorsementRequest?->letter_reference ?? 'pending';
            $this->command->info("  ✓ Approved endorsement request created (letter reference: {$letterRef})");
        }

        $this->command->newLine();
        $this->command->info('Test member created successfully!');
        $this->command->table(
            ['Field', 'Value'],
            [
                ['Email', 'testmember@nrapa.test'],
                ['Password', 'TestMember2026!'],
                ['Membership #', $membership->membership_number],
                ['Status', 'Active - Dedicated Sport Shooter'],
                ['Valid Until', $membership->expires_at?->format('Y-m-d') ?? 'N/A'],
                ['Endorsement Letter', $endorsementRequest?->letter_reference ?? 'N/A'],
            ]
        );
    }

    /**
     * Create the test user account.
     */
    protected function createTestUser(): User
    {
        return User::updateOrCreate(
            ['email' => 'testmember@nrapa.test'],
            [
                'uuid' => Str::uuid()->toString(),
                'name' => 'John Test Member',
                'email' => 'testmember@nrapa.test',
                'password' => Hash::make('TestMember2026!'),
                'email_verified_at' => now(),
                'id_number' => '8507025800086', // Valid SA ID format (male, DOB: 1985-07-02)
                'phone' => '+27 82 123 4567',
                'date_of_birth' => '1985-07-02',
                'physical_address' => '123 Test Street, Garsfontein, Pretoria, 0042',
                'postal_address' => 'PO Box 12345, Garsfontein, 0042',
                'is_admin' => false,
                'role' => User::ROLE_MEMBER,
            ]
        );
    }

    /**
     * Create an active membership for the test user.
     */
    protected function createMembership(User $user): Membership
    {
        // Get the Dedicated Sport Shooter membership type
        $membershipType = MembershipType::where('slug', 'dedicated-sport')->first();
        
        if (!$membershipType) {
            // Fallback to any active membership type that allows dedicated status
            $membershipType = MembershipType::where('is_active', true)
                ->where('allows_dedicated_status', true)
                ->first();
        }

        if (!$membershipType) {
            throw new \RuntimeException('No suitable membership type found. Run MembershipConfigurationSeeder first.');
        }

        // Get the developer user for approvals
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() 
            ?? User::where('email', 'paul@charsley.co.za')->first()
            ?? User::first();

        // Check for existing membership
        $existingMembership = Membership::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingMembership) {
            return $existingMembership;
        }

        // Create the membership
        $membership = Membership::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'status' => 'active',
            'applied_at' => now()->subDays(30),
            'approved_at' => now()->subDays(28),
            'approved_by' => $developer?->id,
            'activated_at' => now()->subDays(28),
            'expires_at' => now()->addYear(),
            'notes' => 'Test member - fully qualified for endorsement testing',
        ]);

        return $membership;
    }

    /**
     * Create verified documents for the test user.
     */
    protected function createVerifiedDocuments(User $user): void
    {
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() ?? User::first();

        // Document types to create
        $documentsToCreate = [
            'identity-document' => [
                'original_filename' => 'id_document_test.pdf',
                'metadata' => [
                    'id_number' => $user->id_number,
                    'full_name' => $user->name,
                ],
            ],
            'proof-of-address' => [
                'original_filename' => 'proof_of_address_test.pdf',
                'metadata' => [
                    'address' => $user->physical_address,
                    'date_issued' => now()->subDays(30)->format('Y-m-d'),
                ],
            ],
        ];

        foreach ($documentsToCreate as $slug => $data) {
            $documentType = DocumentType::where('slug', $slug)->first();
            
            if (!$documentType) {
                continue;
            }

            // Check if document already exists
            $existingDoc = MemberDocument::where('user_id', $user->id)
                ->where('document_type_id', $documentType->id)
                ->where('status', 'verified')
                ->first();

            if ($existingDoc) {
                continue;
            }

            MemberDocument::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'document_type_id' => $documentType->id,
                'file_path' => 'documents/test/' . $data['original_filename'],
                'original_filename' => $data['original_filename'],
                'mime_type' => 'application/pdf',
                'file_size' => 102400, // 100KB
                'metadata' => $data['metadata'],
                'status' => 'verified',
                'uploaded_at' => now()->subDays(25),
                'verified_at' => now()->subDays(24),
                'verified_by' => $developer?->id,
                'expires_at' => $documentType->expiry_months 
                    ? now()->addMonths($documentType->expiry_months) 
                    : null,
            ]);
        }
    }

    /**
     * Create a passed knowledge test for the test user.
     */
    protected function createPassedKnowledgeTest(User $user): void
    {
        // Check if user already has a passed test
        $existingPassed = KnowledgeTestAttempt::where('user_id', $user->id)
            ->where('passed', true)
            ->first();

        if ($existingPassed) {
            return;
        }

        // Try to find a knowledge test, or create a minimal one
        $knowledgeTest = KnowledgeTest::where('is_active', true)->first();
        
        if (!$knowledgeTest) {
            // Create a basic knowledge test for testing purposes
            $knowledgeTest = KnowledgeTest::create([
                'name' => 'Dedicated Sport Shooter Knowledge Test',
                'slug' => 'dedicated-sport-test',
                'description' => 'Knowledge test for dedicated sport shooter status',
                'dedicated_type' => 'sport',
                'time_limit_minutes' => 60,
                'passing_score' => 70,
                'max_attempts' => 3,
                'is_active' => true,
            ]);
        }

        // Create a passed attempt
        KnowledgeTestAttempt::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'knowledge_test_id' => $knowledgeTest->id,
            'started_at' => now()->subDays(20),
            'submitted_at' => now()->subDays(20),
            'auto_score' => 85,
            'manual_score' => 0,
            'total_score' => 85,
            'passed' => true,
            'marked_at' => now()->subDays(20),
        ]);
    }

    /**
     * Create approved shooting activities for the test user.
     */
    protected function createApprovedActivities(User $user): void
    {
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() ?? User::first();

        // Check if user already has approved activities
        $existingCount = ShootingActivity::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        if ($existingCount >= 2) {
            return;
        }

        // Get activity configuration
        $activityType = ActivityType::where('slug', 'dedicated-sport-shooting')->first();
        $eventCategory = EventCategory::where('dedicated_type', 'sport')
            ->orWhere('dedicated_type', 'both')
            ->first();
        $firearmType = FirearmType::where('dedicated_type', 'sport')
            ->orWhere('dedicated_type', 'both')
            ->first();
        $calibre = FirearmCalibre::where('category', 'rifle')->where('is_active', true)->first();
        $country = Country::where('code', 'ZA')->first();
        $province = Province::where('code', 'GP')->first();

        // Create 3 approved activities within the last 12 months
        $activities = [
            [
                'activity_date' => now()->subMonths(2),
                'description' => 'Long range precision shooting practice at club range',
                'location' => 'NRAPA Range',
                'closest_town_city' => 'Pretoria',
            ],
            [
                'activity_date' => now()->subMonths(5),
                'description' => 'Sport shooting competition - Provincial Championships',
                'location' => 'Gauteng Shooting Range',
                'closest_town_city' => 'Johannesburg',
            ],
            [
                'activity_date' => now()->subMonths(8),
                'description' => 'Training session - Practical shooting fundamentals',
                'location' => 'Local Gun Club',
                'closest_town_city' => 'Centurion',
            ],
        ];

        foreach ($activities as $activityData) {
            ShootingActivity::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'activity_type_id' => $activityType?->id,
                'event_category_id' => $eventCategory?->id,
                'activity_date' => $activityData['activity_date'],
                'description' => $activityData['description'],
                'firearm_type_id' => $firearmType?->id,
                // Note: shooting_activities still uses calibre_id (old system) - can be null
                'location' => $activityData['location'],
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'closest_town_city' => $activityData['closest_town_city'],
                'status' => 'approved',
                'verified_at' => $activityData['activity_date']->copy()->addDays(3),
                'verified_by' => $developer?->id,
            ]);
        }
    }

    /**
     * Create an approved dedicated status application.
     */
    protected function createApprovedDedicatedStatus(User $user, Membership $membership): DedicatedStatusApplication
    {
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() ?? User::first();

        // Check if user already has approved dedicated status
        $existing = DedicatedStatusApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DedicatedStatusApplication::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'dedicated_type' => 'sport',
            'status' => 'approved',
            'applied_at' => now()->subDays(15),
            'reviewed_at' => now()->subDays(14),
            'reviewed_by' => $developer?->id,
            'approved_at' => now()->subDays(14),
            'approved_by' => $developer?->id,
            'valid_from' => now()->subDays(14),
            'valid_until' => now()->addYear(),
            'notes' => 'Test member - auto-approved for testing',
        ]);
    }

    /**
     * Create certificates for the test user.
     */
    protected function createCertificates(User $user, Membership $membership): void
    {
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() ?? User::first();

        // Get certificate types to issue
        $certificateTypes = [
            'membership-certificate',
            'dedicated-status-certificate',
        ];

        foreach ($certificateTypes as $slug) {
            $certificateType = CertificateType::where('slug', $slug)->first();
            
            if (!$certificateType) {
                continue;
            }

            // Check if certificate already exists
            $existing = Certificate::where('user_id', $user->id)
                ->where('certificate_type_id', $certificateType->id)
                ->whereNull('revoked_at')
                ->first();

            if ($existing) {
                continue;
            }

            // Calculate validity
            $validUntil = $certificateType->validity_months
                ? now()->addMonths($certificateType->validity_months)
                : ($membership->expires_at ?? null);

            Certificate::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'membership_id' => $membership->id,
                'certificate_type_id' => $certificateType->id,
                'issued_at' => now()->subDays(10),
                'issued_by' => $developer?->id,
                'valid_from' => now()->subDays(10),
                'valid_until' => $validUntil,
                'metadata' => [
                    'auto_generated' => true,
                    'seeder' => 'TestMemberSeeder',
                ],
            ]);
        }
    }

    /**
     * Create an approved and issued endorsement letter for the test user.
     */
    protected function createApprovedEndorsementLetter(User $user, Membership $membership): EndorsementRequest
    {
        $developer = User::where('role', User::ROLE_DEVELOPER)->first() ?? User::first();

        // Check if user already has an issued endorsement letter
        $existing = EndorsementRequest::where('user_id', $user->id)
            ->where('status', EndorsementRequest::STATUS_ISSUED)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Get firearm type and calibre for the endorsement
        $firearmType = FirearmType::where('dedicated_type', 'sport')
            ->orWhere('dedicated_type', 'both')
            ->first();
        $calibre = FirearmCalibre::where('category', 'rifle')->where('is_active', true)->first();

        // Create the endorsement request
        $request = EndorsementRequest::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'request_type' => EndorsementRequest::TYPE_NEW,
            'status' => EndorsementRequest::STATUS_APPROVED, // Start as approved
            'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
            'declaration_accepted_at' => now()->subDays(5),
            'declaration_text' => 'I declare that the information provided is true and correct.',
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(4),
            'reviewer_id' => $developer?->id,
            'member_notes' => 'Test endorsement request - auto-generated for testing',
            'admin_notes' => 'Test member - fully compliant, auto-approved',
        ]);

        // Approve the request
        $request->approve($developer, 'Test member - fully compliant');

        // Create endorsement firearm if firearm type and calibre exist
        if ($firearmType && $calibre) {
            EndorsementFirearm::create([
                'uuid' => Str::uuid()->toString(),
                'endorsement_request_id' => $request->id,
                'firearm_category' => EndorsementFirearm::CATEGORY_RIFLE,
                'ignition_type' => EndorsementFirearm::IGNITION_CENTERFIRE,
                'action_type' => EndorsementFirearm::ACTION_BOLT_ACTION,
                'firearm_calibre_id' => $calibre->id,
                'licence_section' => EndorsementFirearm::LICENCE_SECTION_16,
            ]);
        }

        // Generate letter reference
        $letterReference = EndorsementRequest::generateLetterReference();

        // Generate the endorsement letter using DocumentRenderer
        try {
            $renderer = app(\App\Contracts\DocumentRenderer::class);
            $letterPath = $renderer->renderEndorsementLetter($request, 'documents.letters.endorsement');
            
            // Issue the letter
            $request->issue($developer, $letterReference, $letterPath);
        } catch (\Exception $e) {
            // If letter generation fails, still mark as issued with reference
            // The letter can be regenerated later via admin UI
            $request->update([
                'status' => EndorsementRequest::STATUS_ISSUED,
                'issued_at' => now(),
                'issued_by' => $developer?->id,
                'letter_reference' => $letterReference,
            ]);
        }

        return $request->fresh();
    }
}
