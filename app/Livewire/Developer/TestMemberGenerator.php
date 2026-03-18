<?php

namespace App\Livewire\Developer;

use App\Models\ActivityType;
use App\Models\Calibre;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Country;
use App\Models\DedicatedStatusApplication;
use App\Models\DocumentType;
use App\Models\FirearmType;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Province;
use App\Models\ShootingActivity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class TestMemberGenerator extends Component
{
    public string $stage = 'new';

    public int $count = 1;

    public bool $withFirearms = false;

    public bool $withEndorsements = false;

    public array $stages = [
        'new' => 'New Member (Just Registered)',
        'applied' => 'Applied (Membership Application Submitted)',
        'approved' => 'Approved (Membership Approved, No Documents)',
        'active' => 'Active (Membership Active, Basic Documents)',
        'dedicated' => 'With Dedicated Status (Includes Certificates)',
        'full' => 'Fully Qualified (Includes Certificates)',
    ];

    public function generate()
    {
        $this->validate([
            'stage' => 'required|in:'.implode(',', array_keys($this->stages)),
            'count' => 'required|integer|min:1|max:10',
        ]);

        $developer = User::where('role', User::ROLE_DEVELOPER)->first()
            ?? User::where('email', 'paul@charsley.co.za')->first()
            ?? auth()->user();

        $generated = [];

        for ($i = 0; $i < $this->count; $i++) {
            $user = $this->createTestUser($i);
            $generated[] = $user;

            switch ($this->stage) {
                case 'new':
                    // Just the user, nothing else
                    break;

                case 'applied':
                    $this->createMembershipApplication($user);
                    break;

                case 'approved':
                    $membership = $this->createApprovedMembership($user, $developer);
                    break;

                case 'active':
                    $membership = $this->createActiveMembership($user, $developer);
                    $this->createBasicDocuments($user, $developer);
                    break;

                case 'dedicated':
                    $membership = $this->createActiveMembership($user, $developer);
                    $this->createBasicDocuments($user, $developer);
                    $this->createPassedKnowledgeTest($user);
                    $this->createApprovedActivities($user, $developer);
                    $dedicatedStatus = $this->createDedicatedStatus($user, $membership, $developer);
                    // Always create certificates for dedicated status members
                    $this->createCertificates($user, $membership, $developer);
                    break;

                case 'full':
                    $membership = $this->createActiveMembership($user, $developer);
                    $this->createBasicDocuments($user, $developer);
                    $this->createPassedKnowledgeTest($user);
                    $this->createApprovedActivities($user, $developer);
                    $dedicatedStatus = $this->createDedicatedStatus($user, $membership, $developer);
                    // Always create certificates for full members
                    $this->createCertificates($user, $membership, $developer);
                    if ($this->withFirearms) {
                        $this->createTestFirearms($user);
                    }
                    if ($this->withEndorsements) {
                        $this->createEndorsementRequests($user);
                    }
                    break;
            }
        }

        session()->flash('success', "Generated {$this->count} test member(s) in '{$this->stages[$this->stage]}' stage.");

        // Store generated members in session for display
        $recentMembers = session('recent_test_members', []);
        foreach ($generated as $user) {
            array_unshift($recentMembers, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'stage' => $this->stage,
                'stage_label' => $this->stages[$this->stage],
                'created_at' => now()->toDateTimeString(),
            ]);
        }
        // Keep only last 20
        $recentMembers = array_slice($recentMembers, 0, 20);
        session(['recent_test_members' => $recentMembers]);

        if (count($generated) === 1) {
            return $this->redirect(route('admin.members.show', $generated[0]), navigate: true);
        }

        $this->dispatch('members-generated', count: count($generated));
    }

    public function getRecentMembersProperty()
    {
        return collect(session('recent_test_members', []));
    }

    public function clearRecentMembers()
    {
        session()->forget('recent_test_members');
        session()->flash('success', 'Recent test members list cleared.');
    }

    protected function createTestUser(int $index): User
    {
        $baseEmail = 'testmember'.($index > 0 ? $index + 1 : '').'@nrapa.test';

        // Check if user already exists by email
        $existingUser = User::where('email', $baseEmail)->first();

        if ($existingUser) {
            // Update existing user but keep their ID number if it exists
            $existingUser->update([
                'name' => 'Test Member '.($index + 1),
                'password' => Hash::make('TestMember2026!'),
                'email_verified_at' => now(),
                'phone' => '+27 82 '.str_pad(1234567 + $index, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1985-07-02',
                'physical_address' => '123 Test Street '.($index + 1).', Garsfontein, Pretoria, 0042',
                'postal_address' => 'PO Box '.(12345 + $index).', Garsfontein, 0042',
                'is_admin' => false,
                'role' => User::ROLE_MEMBER,
            ]);

            return $existingUser;
        }

        // Generate unique ID number - use a large offset to avoid conflicts with seeder
        // Start from 9000000 to avoid conflicts with TestMemberSeeder (which uses 5800086)
        $idNumberSuffix = 9000000 + ($index * 100) + (time() % 100);
        $idNumber = '850702'.str_pad((string) $idNumberSuffix, 7, '0', STR_PAD_LEFT);

        // Ensure ID number is unique - if it exists, increment
        while (User::where('id_number', $idNumber)->exists()) {
            $idNumberSuffix++;
            $idNumber = '850702'.str_pad((string) $idNumberSuffix, 7, '0', STR_PAD_LEFT);
        }

        return User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Test Member '.($index + 1),
            'email' => $baseEmail,
            'password' => Hash::make('TestMember2026!'),
            'email_verified_at' => now(),
            'id_number' => $idNumber,
            'phone' => '+27 82 '.str_pad(1234567 + $index, 7, '0', STR_PAD_LEFT),
            'date_of_birth' => '1985-07-02',
            'physical_address' => '123 Test Street '.($index + 1).', Garsfontein, Pretoria, 0042',
            'postal_address' => 'PO Box '.(12345 + $index).', Garsfontein, 0042',
            'is_admin' => false,
            'role' => User::ROLE_MEMBER,
        ]);
    }

    protected function createMembershipApplication(User $user): void
    {
        $membershipType = MembershipType::where('is_active', true)->first();

        if (! $membershipType) {
            return;
        }

        Membership::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'status' => 'applied',
            'applied_at' => now(),
            'source' => 'web', // Test members simulate web applications
        ]);
    }

    protected function createApprovedMembership(User $user, User $developer): Membership
    {
        $membershipType = MembershipType::where('is_active', true)->first();

        if (! $membershipType) {
            throw new \RuntimeException('No active membership type found.');
        }

        return Membership::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'status' => 'approved',
            'applied_at' => now()->subDays(5),
            'approved_at' => now()->subDays(3),
            'approved_by' => $developer->id,
            'source' => 'web', // Test members simulate web applications
        ]);
    }

    protected function createActiveMembership(User $user, User $developer): Membership
    {
        $membershipType = MembershipType::where('is_active', true)
            ->where('allows_dedicated_status', true)
            ->first()
            ?? MembershipType::where('is_active', true)->first();

        if (! $membershipType) {
            throw new \RuntimeException('No active membership type found.');
        }

        return Membership::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_type_id' => $membershipType->id,
            'status' => 'active',
            'applied_at' => now()->subDays(30),
            'approved_at' => now()->subDays(28),
            'approved_by' => $developer->id,
            'activated_at' => now()->subDays(28),
            'expires_at' => now()->addYear(),
            'source' => 'web', // Test members simulate web applications
        ]);
    }

    protected function createBasicDocuments(User $user, User $developer): void
    {
        $documents = [
            'identity-document' => ['expiry_months' => null],
            'proof-of-address' => ['expiry_months' => 3],
        ];

        foreach ($documents as $slug => $config) {
            $documentType = DocumentType::where('slug', $slug)->first();

            if (! $documentType) {
                continue;
            }

            MemberDocument::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'document_type_id' => $documentType->id,
                'file_path' => 'documents/test/'.$slug.'_test.pdf',
                'original_filename' => $slug.'_test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 102400,
                'status' => 'verified',
                'uploaded_at' => now()->subDays(25),
                'verified_at' => now()->subDays(24),
                'verified_by' => $developer->id,
                'expires_at' => $config['expiry_months']
                    ? now()->addMonths($config['expiry_months'])
                    : null,
            ]);
        }
    }

    protected function createPassedKnowledgeTest(User $user): void
    {
        $knowledgeTest = KnowledgeTest::where('is_active', true)->first();

        if (! $knowledgeTest) {
            return;
        }

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

    protected function createApprovedActivities(User $user, User $developer): void
    {
        // Get sport shooting activity type
        $activityType = ActivityType::where('track', 'sport')->first()
            ?? ActivityType::where('slug', 'dedicated-sport-shooting')->first()
            ?? ActivityType::active()->first();

        $firearmType = FirearmType::active()->first();
        $calibre = Calibre::where('category', 'rifle')->first();
        $country = Country::where('code', 'ZA')->first();
        $province = Province::where('code', 'GP')->first();

        for ($i = 0; $i < 3; $i++) {
            $activity = ShootingActivity::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'track' => $activityType?->track ?? 'sport',
                'activity_type_id' => $activityType?->id,
                'activity_date' => now()->subMonths(2 + ($i * 3)),
                'description' => 'Test shooting activity '.($i + 1),
                'firearm_type_id' => $firearmType?->id,
                'calibre_id' => $calibre?->id,
                'location' => 'Test Range '.($i + 1),
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'closest_town_city' => 'Pretoria',
                'status' => 'approved',
                'verified_at' => now()->subMonths(2 + ($i * 3))->addDays(3),
                'verified_by' => $developer->id,
            ]);
        }
    }

    protected function createDedicatedStatus(User $user, Membership $membership, User $developer): DedicatedStatusApplication
    {
        return DedicatedStatusApplication::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'dedicated_type' => 'sport_shooter',
            'status' => 'approved',
            'applied_at' => now()->subDays(15),
            'reviewed_at' => now()->subDays(14),
            'reviewed_by' => $developer->id,
            'approved_at' => now()->subDays(14),
            'approved_by' => $developer->id,
            'valid_from' => now()->subDays(14),
            'valid_until' => now()->addYear(),
        ]);
    }

    protected function createCertificates(User $user, Membership $membership, User $developer): void
    {
        $certificateTypes = CertificateType::whereIn('slug', [
            'membership-certificate',
            'dedicated-status-certificate',
        ])->get();

        if ($certificateTypes->isEmpty()) {
            // If certificate types don't exist, log warning and skip
            \Log::warning('Certificate types not found for test member generation', [
                'user_id' => $user->id,
                'slugs' => ['membership-certificate', 'dedicated-status-certificate'],
            ]);

            return;
        }

        foreach ($certificateTypes as $certificateType) {
            try {
                // Calculate valid_until - let the model boot method handle it if not set
                $validUntil = null;
                if ($certificateType->validity_months) {
                    $issueDate = now()->subDays(10);
                    $validUntil = $issueDate->copy()->addMonths($certificateType->validity_months)->toDateString();
                } elseif ($membership->expires_at) {
                    $validUntil = $membership->expires_at->toDateString();
                }

                Certificate::create([
                    'uuid' => Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                    'certificate_type_id' => $certificateType->id,
                    'issued_at' => now()->subDays(10),
                    'issued_by' => $developer->id,
                    'valid_from' => now()->subDays(10)->toDateString(),
                    'valid_until' => $validUntil,
                ]);
            } catch (\Exception $e) {
                // Log error but continue with other certificates
                \Log::error('Failed to create certificate for test member', [
                    'user_id' => $user->id,
                    'certificate_type_id' => $certificateType->id,
                    'certificate_type_slug' => $certificateType->slug,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't throw - continue with other certificates
            }
        }
    }

    protected function createTestFirearms(User $user): void
    {
        // Create a test firearm in the virtual safe
        $calibre = \App\Models\Calibre::where('category', 'rifle')->first();

        if (! $calibre) {
            return;
        }

        $firearm = \App\Models\UserFirearm::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'firearm_type' => 'rifle',
            'action' => 'manual',
            'calibre_id' => $calibre->id,
            'calibre_code' => $calibre->code ?? null,
            'make' => 'Test Make',
            'model' => 'Test Model '.rand(1000, 9999),
        ]);

        // Add receiver component with serial
        \App\Models\FirearmComponent::create([
            'user_firearm_id' => $firearm->id,
            'type' => 'receiver',
            'serial' => 'TEST-'.strtoupper(Str::random(8)),
        ]);
    }

    protected function createEndorsementRequests(User $user): void
    {
        // Create a test endorsement request
        $endorsementRequest = \App\Models\EndorsementRequest::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'request_type' => 'new',
            'status' => 'submitted',
            'purpose' => 'section_16_application',
            'declaration_accepted_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2),
        ]);

        // Add firearm details to the endorsement
        $firearm = $user->firearms()->first();
        if ($firearm) {
            \App\Models\EndorsementFirearm::create([
                'endorsement_request_id' => $endorsementRequest->id,
                'user_firearm_id' => $firearm->id,
                'firearm_type' => $firearm->firearm_type,
                'action' => $firearm->action,
                'calibre_id' => $firearm->calibre_id,
                'make' => $firearm->make,
                'model' => $firearm->model,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.developer.test-member-generator');
    }
}
