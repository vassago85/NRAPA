<?php

declare(strict_types=1);

use App\Actions\Memberships\ApproveMembership;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\CertificateIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * On approval, the Dedicated Status Certificate is issued automatically
 * alongside the Membership Card and Membership Certificate, driven purely
 * by the type's `dedicated_type` attribute — no slug matching, no code
 * change required when a new type is added.
 *
 * We mock {@see CertificateIssueService} because the real path renders
 * PDFs via Gotenberg/DomPDF and we only care which dispatch happens.
 */

function approvalCertSvcMock(): Mockery\MockInterface
{
    $mock = Mockery::mock(CertificateIssueService::class);
    // Membership cert / welcome / card are always attempted; let them no-op
    // so the test focuses on the dedicated dispatch.
    $mock->shouldReceive('issueMembershipCertificate')->andReturnNull();
    $mock->shouldReceive('issueWelcomeLetter')->andReturnNull();
    $mock->shouldReceive('issueMembershipCard')->andReturnNull();

    app()->instance(CertificateIssueService::class, $mock);

    return $mock;
}

function approvalDedicatedType(string $dedicatedType, bool $allows = true): MembershipType
{
    return MembershipType::create([
        'slug' => 'approval-ded-'.$dedicatedType,
        'name' => 'Approval '.ucfirst($dedicatedType),
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 1000,
        'renewal_price' => 900,
        'allows_dedicated_status' => $allows,
        'dedicated_type' => $dedicatedType,
        'requires_knowledge_test' => true,
        'is_active' => true,
    ]);
}

function approvalApply(MembershipType $type): Membership
{
    $member = User::factory()->create(['role' => 'member']);

    return Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $type->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
    ]);
}

it('issues the dedicated hunter certificate when the type is hunter', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldReceive('issueDedicatedHunterCertificate')->once()->andReturnNull();
    $mock->shouldNotReceive('issueDedicatedSportCertificate');
    $mock->shouldNotReceive('issueDedicatedBothCertificate');

    $membership = approvalApply(approvalDedicatedType(MembershipType::DEDICATED_TYPE_HUNTER));
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});

it('issues the dedicated sport certificate when the type is sport', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldReceive('issueDedicatedSportCertificate')->once()->andReturnNull();
    $mock->shouldNotReceive('issueDedicatedHunterCertificate');
    $mock->shouldNotReceive('issueDedicatedBothCertificate');

    $membership = approvalApply(approvalDedicatedType(MembershipType::DEDICATED_TYPE_SPORT));
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});

it('issues the dedicated both certificate when the type is both', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldReceive('issueDedicatedBothCertificate')->once()->andReturnNull();
    $mock->shouldNotReceive('issueDedicatedHunterCertificate');
    $mock->shouldNotReceive('issueDedicatedSportCertificate');

    $membership = approvalApply(approvalDedicatedType(MembershipType::DEDICATED_TYPE_BOTH));
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});

it('does not attempt a dedicated certificate when the type does not allow dedicated status', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldNotReceive('issueDedicatedHunterCertificate');
    $mock->shouldNotReceive('issueDedicatedSportCertificate');
    $mock->shouldNotReceive('issueDedicatedBothCertificate');

    $type = approvalDedicatedType('hunter', allows: false);
    $membership = approvalApply($type);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});

it('does not attempt a dedicated certificate when dedicated_type is null', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldNotReceive('issueDedicatedHunterCertificate');
    $mock->shouldNotReceive('issueDedicatedSportCertificate');
    $mock->shouldNotReceive('issueDedicatedBothCertificate');

    $type = MembershipType::create([
        'slug' => 'approval-basic',
        'name' => 'Approval Basic',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 500,
        'renewal_price' => 400,
        'allows_dedicated_status' => true,
        'dedicated_type' => null,
        'requires_knowledge_test' => false,
        'is_active' => true,
    ]);
    $membership = approvalApply($type);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});

it('still activates the membership when dedicated certificate issuance throws', function () {
    $mock = approvalCertSvcMock();
    $mock->shouldReceive('issueDedicatedBothCertificate')
        ->once()
        ->andThrow(new RuntimeException('activities short'));

    $membership = approvalApply(approvalDedicatedType(MembershipType::DEDICATED_TYPE_BOTH));
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($membership, $admin);

    expect($membership->fresh()->status)->toBe('active');
});
