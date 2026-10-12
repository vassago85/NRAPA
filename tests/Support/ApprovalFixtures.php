<?php

namespace Tests\Support;

use App\Models\Approval;
use App\Models\CalibreRequest;
use App\Models\DocumentType;
use App\Models\EndorsementRequest;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\ShootingActivity;
use App\Models\User;

/**
 * Builds one pending record of each approvable type for the approval tests.
 */
class ApprovalFixtures
{
    /**
     * Rendering real certificate and letter PDFs exhausts the test memory limit,
     * so the issuers fail fast and the callers take their fallback paths.
     */
    public static function stubDocumentRendering(): void
    {
        $certificates = \Mockery::mock(\App\Services\CertificateIssueService::class);
        $certificates->shouldReceive('issueMembershipCertificate', 'issueWelcomeLetter', 'issueMembershipCard')
            ->andThrow(new \RuntimeException('PDF rendering disabled in tests'));
        app()->instance(\App\Services\CertificateIssueService::class, $certificates);

        $letters = \Mockery::mock(\App\Services\EndorsementLetterIssuer::class);
        $letters->shouldReceive('issueApprovedLetter')
            ->andThrow(new \RuntimeException('PDF rendering disabled in tests'));
        app()->instance(\App\Services\EndorsementLetterIssuer::class, $letters);
    }

    public static function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_ADMIN,
            'admin_type' => User::ADMIN_TYPE_STANDARD,
            'is_admin' => true,
        ], $attributes));
    }

    public static function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_MEMBER], $attributes));
    }

    public static function membershipType(array $attributes = []): MembershipType
    {
        return MembershipType::firstOrCreate(
            ['slug' => $attributes['slug'] ?? 'queue-annual'],
            array_merge([
                'name' => 'Queue Annual',
                'duration_type' => 'annual',
                'duration_months' => 12,
                'requires_renewal' => true,
                'pricing_model' => 'annual',
                'initial_price' => 500,
                'is_active' => true,
            ], $attributes),
        );
    }

    public static function activeMembership(User $member): Membership
    {
        return Membership::create([
            'user_id' => $member->id,
            'membership_type_id' => self::membershipType()->id,
            'status' => 'active',
            'source' => 'import',
            'applied_at' => now()->subYear(),
            'approved_at' => now()->subYear(),
            'activated_at' => now()->subYear(),
            'expires_at' => now()->addMonths(6),
        ]);
    }

    /**
     * A web application with proof of payment uploaded: its payment waits on an admin.
     */
    public static function paymentApproval(?User $member = null): Approval
    {
        $membership = Membership::create([
            'user_id' => ($member ?? self::member())->id,
            'membership_type_id' => self::membershipType()->id,
            'status' => 'applied',
            'source' => 'web',
            'applied_at' => now(),
            'payment_reference' => 'NRAPA-QUEUE-'.random_int(1000, 9999),
            'proof_of_payment_path' => 'proof-of-payment/test.pdf',
        ]);

        return Approval::forApprovable(Payment::where('membership_id', $membership->id)->firstOrFail())->firstOrFail();
    }

    /**
     * An imported application (not billable): the membership waits on an admin.
     */
    public static function membershipApproval(?User $member = null): Approval
    {
        $membership = Membership::create([
            'user_id' => ($member ?? self::member())->id,
            'membership_type_id' => self::membershipType()->id,
            'status' => 'applied',
            'source' => 'import',
            'applied_at' => now(),
        ]);

        return Approval::forApprovable($membership)->firstOrFail();
    }

    public static function documentApproval(?User $member = null): Approval
    {
        $type = DocumentType::firstOrCreate(
            ['slug' => 'proof-of-address'],
            ['name' => 'Proof of address', 'is_active' => true, 'sort_order' => 2],
        );

        $document = MemberDocument::create([
            'user_id' => ($member ?? self::member())->id,
            'document_type_id' => $type->id,
            'file_path' => 'member-documents/poa.pdf',
            'original_filename' => 'poa.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        return Approval::forApprovable($document)->firstOrFail();
    }

    public static function activityApproval(?User $member = null): Approval
    {
        $member ??= self::member();

        $activity = ShootingActivity::create([
            'user_id' => $member->id,
            'activity_date' => now()->subWeek(),
            'location' => 'Queue Range',
            'status' => 'pending',
        ]);

        return Approval::forApprovable($activity)->firstOrFail();
    }

    public static function endorsementApproval(?User $member = null): Approval
    {
        $request = EndorsementRequest::create([
            'user_id' => ($member ?? self::member())->id,
            'request_type' => EndorsementRequest::TYPE_NEW,
            'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
            'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
            'status' => EndorsementRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'firearm_make' => 'Tikka',
            'firearm_model' => 'T3x',
            'firearm_calibre' => '.308 Win',
        ]);

        return Approval::forApprovable($request)->firstOrFail();
    }

    public static function calibreApproval(?User $member = null): Approval
    {
        $request = CalibreRequest::create([
            'user_id' => ($member ?? self::member())->id,
            'name' => '6.5 Queue Magnum '.random_int(100, 999),
            'category' => 'rifle',
            'ignition_type' => 'centerfire',
            'status' => CalibreRequest::STATUS_PENDING,
        ]);

        return Approval::forApprovable($request)->firstOrFail();
    }

    /**
     * @return array<string, callable(?User): Approval>
     */
    public static function builders(): array
    {
        return [
            'payment' => self::paymentApproval(...),
            'document' => self::documentApproval(...),
            'membership' => self::membershipApproval(...),
            'activity' => self::activityApproval(...),
            'endorsement' => self::endorsementApproval(...),
            'calibre' => self::calibreApproval(...),
        ];
    }
}
