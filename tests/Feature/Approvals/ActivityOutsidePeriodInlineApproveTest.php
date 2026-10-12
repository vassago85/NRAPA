<?php

use App\Enums\ApprovalState;
use App\Models\Approval;
use App\Models\Membership;
use App\Models\ShootingActivity;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ApprovalFixtures;

uses(RefreshDatabase::class);

/**
 * Activities outside the membership period used to fail the preflight so an
 * admin could not approve them from the queue. The queue is the member's
 * compliance ledger, so an admin must be able to approve historical and
 * gap-period activities inline; the "outside the period" marker is still
 * shown on the row and in the evidence block for context.
 */
it('lets an admin inline-approve a shooting activity dated outside any membership period', function () {
    $member = ApprovalFixtures::member();

    // Member's only membership covers 2024-01 through 2024-12.
    Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => ApprovalFixtures::membershipType()->id,
        'status' => 'active',
        'source' => 'import',
        'applied_at' => now()->subMonths(6),
        'approved_at' => now()->subMonths(6),
        'activated_at' => now()->subMonths(6),
        'expires_at' => now()->addYear(),
    ]);

    // Activity logged 2 years before the membership started: clearly outside.
    $activity = ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => now()->subYears(3),
        'location' => 'Hennenman',
        'status' => 'pending',
    ]);

    $approval = Approval::forApprovable($activity)->firstOrFail();

    expect($activity->detailWarning())->toBe('Falls outside the membership period');
    expect(PreflightCheck::allPass($activity->preflightChecks()))->toBeTrue();
    expect($activity->isInlineApprovable())->toBeTrue();

    expect($approval->state)->toBe(ApprovalState::WaitingOnAdmin);
});
