<?php

declare(strict_types=1);

use App\Actions\Memberships\ApproveMembership;
use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Services\CertificateIssueService;
use App\Support\Members\MemberCompliance;
use App\Support\Members\PriorYearComplianceResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Prior-year dedicated-renewal compliance:
 *
 *  - Prior-year 2/2 approved activities → auto-pass.
 *  - Previous membership was imported → first-cycle auto-pass.
 *  - Neither → admin must tick "verify prior-year compliance".
 *  - Non-dedicated types → rule does not apply.
 */

function pyMockCerts(): Mockery\MockInterface
{
    $mock = Mockery::mock(CertificateIssueService::class);
    $mock->shouldReceive('issueMembershipCertificate')->andReturnNull();
    $mock->shouldReceive('issueWelcomeLetter')->andReturnNull();
    $mock->shouldReceive('issueMembershipCard')->andReturnNull();
    $mock->shouldReceive('issueDedicatedHunterCertificate')->andReturnNull();
    $mock->shouldReceive('issueDedicatedSportCertificate')->andReturnNull();
    $mock->shouldReceive('issueDedicatedBothCertificate')->andReturnNull();
    app()->instance(CertificateIssueService::class, $mock);
    return $mock;
}

function pyDedicatedType(): MembershipType
{
    return MembershipType::firstOrCreate(
        ['slug' => 'py-dedicated-annual'],
        [
            'name' => 'PY Dedicated Annual',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => true,
            'pricing_model' => 'annual',
            'initial_price' => 1000,
            'renewal_price' => 900,
            'allows_dedicated_status' => true,
            'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
            'requires_knowledge_test' => true,
            'is_active' => true,
        ],
    );
}

function pyBasicType(): MembershipType
{
    return MembershipType::firstOrCreate(
        ['slug' => 'py-basic-annual'],
        [
            'name' => 'PY Basic Annual',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => true,
            'pricing_model' => 'annual',
            'initial_price' => 400,
            'renewal_price' => 350,
            'allows_dedicated_status' => false,
            'dedicated_type' => null,
            'requires_knowledge_test' => false,
            'is_active' => true,
        ],
    );
}

/** @return array{member: User, previous: Membership, renewal: Membership} */
function pyBuildRenewal(string $previousSource = 'web', ?MembershipType $type = null): array
{
    $type ??= pyDedicatedType();
    $member = User::factory()->create(['role' => 'member']);

    $previous = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => $previousSource,
        'applied_at' => now()->subYear()->subDays(5),
        'approved_at' => now()->subYear()->subDays(5),
        'activated_at' => now()->subYear()->subDays(5),
        'expires_at' => now()->subDays(5),
    ]);

    $renewal = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $type->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
        'previous_membership_id' => $previous->id,
    ]);

    return ['member' => $member, 'previous' => $previous, 'renewal' => $renewal];
}

function pyLogApprovedActivities(User $member, int $count, int $year): void
{
    for ($i = 0; $i < $count; $i++) {
        ShootingActivity::create([
            'user_id' => $member->id,
            'activity_date' => Carbon::create($year, 3, 1)->addDays($i * 7),
            'location' => 'Range '.$i,
            'status' => 'approved',
            'verified_at' => Carbon::create($year, 3, 10)->addDays($i * 7),
        ]);
    }
}

it('passes the resolver when the member has 2 approved prior-year activities', function () {
    ['member' => $member, 'renewal' => $renewal] = pyBuildRenewal();
    pyLogApprovedActivities($member, 2, now()->year - 1);

    $status = PriorYearComplianceResolver::for($renewal);

    expect($status->applicable)->toBeTrue();
    expect($status->isActivityCompliant)->toBeTrue();
    expect($status->isImportedFirstCycle)->toBeFalse();
    expect($status->isReady())->toBeTrue();
});

it('passes via imported-first-cycle when the previous membership source is import', function () {
    ['renewal' => $renewal, 'previous' => $previous] = pyBuildRenewal(previousSource: 'import');

    $status = PriorYearComplianceResolver::for($renewal);

    expect($previous->source)->toBe('import');
    expect($status->applicable)->toBeTrue();
    expect($status->isImportedFirstCycle)->toBeTrue();
    expect($status->isActivityCompliant)->toBeFalse();
    expect($status->isReady())->toBeTrue();
});

it('blocks approval when the member has no prior-year activities and no import history', function () {
    ['renewal' => $renewal] = pyBuildRenewal();

    $status = PriorYearComplianceResolver::for($renewal);

    expect($status->applicable)->toBeTrue();
    expect($status->isActivityCompliant)->toBeFalse();
    expect($status->isImportedFirstCycle)->toBeFalse();
    expect($status->isReady())->toBeFalse();
});

it('unblocks approval after an admin manually verifies prior-year compliance', function () {
    ['renewal' => $renewal] = pyBuildRenewal();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    PriorYearComplianceResolver::markVerified($renewal, $admin, 'Phoned member and confirmed');

    $status = PriorYearComplianceResolver::for($renewal->fresh());

    expect($status->isManuallyVerified)->toBeTrue();
    expect($status->verifiedBy?->id)->toBe($admin->id);
    expect($status->verifiedNote)->toBe('Phoned member and confirmed');
    expect($status->isReady())->toBeTrue();
});

it('is not applicable for a renewal of a non-dedicated type', function () {
    ['renewal' => $renewal] = pyBuildRenewal(type: pyBasicType());

    $status = PriorYearComplianceResolver::for($renewal);

    expect($status->applicable)->toBeFalse();
    expect($status->isReady())->toBeTrue();
});

it('is not applicable for a new application without a previous membership', function () {
    $member = User::factory()->create(['role' => 'member']);
    $renewal = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => pyDedicatedType()->id,
        'status' => 'applied',
        'source' => 'web',
        'applied_at' => now(),
    ]);

    expect(PriorYearComplianceResolver::for($renewal)->applicable)->toBeFalse();
});

it('ApproveMembership auto-stamps verified_at when the previous membership was imported', function () {
    pyMockCerts();
    ['renewal' => $renewal] = pyBuildRenewal(previousSource: 'import');
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    expect($renewal->prior_year_compliance_verified_at)->toBeNull();

    app(ApproveMembership::class)->approveApplication($renewal, $admin);

    $renewal->refresh();
    expect($renewal->prior_year_compliance_verified_at)->not->toBeNull();
    expect($renewal->prior_year_compliance_verified_note)
        ->toContain('Auto-verified')
        ->toContain('bulk import');
});

it('ApproveMembership does not auto-stamp when the previous membership was not imported', function () {
    pyMockCerts();
    ['renewal' => $renewal] = pyBuildRenewal(previousSource: 'web');
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    app(ApproveMembership::class)->approveApplication($renewal, $admin);

    expect($renewal->fresh()->prior_year_compliance_verified_at)->toBeNull();
});

it('MemberCompliance honours the manual prior-year verification', function () {
    // Seed required ID doc type so compliance doesn't short-circuit on it.
    DocumentType::firstOrCreate(['slug' => 'identity-document'], ['name' => 'ID', 'is_active' => true, 'sort_order' => 1]);

    ['member' => $member, 'renewal' => $renewal] = pyBuildRenewal();
    // Activate the renewal so it's the user's active membership.
    $renewal->update([
        'status' => 'active',
        'approved_at' => now(),
        'activated_at' => now(),
        'expires_at' => now()->addYear(),
    ]);
    // Clear the previous row so activeMembership returns the renewal.
    $renewal->fresh();
    Membership::query()->where('id', '!=', $renewal->id)->update(['status' => 'expired']);

    // No activities logged anywhere.
    $before = MemberCompliance::for($member)->requirement('activities');
    expect($before->isSatisfied())->toBeFalse();

    $renewal->update(['prior_year_compliance_verified_at' => now()]);
    MemberCompliance::flush();
    // Simulate a fresh request: the User's eager-loaded activeMembership
    // relation is stale after a direct DB mutation.
    $member = $member->fresh();

    $after = MemberCompliance::for($member)->requirement('activities');
    expect($after->isSatisfied())->toBeTrue();
    expect($after->meta['prior_year_verified'] ?? false)->toBeTrue();
});
