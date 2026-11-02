<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\SapsYearSubmission;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\MemberCompliance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The SAPS year-submission lock is served from an in-memory static
    // cache that `RefreshDatabase` cannot see. If another test suite
    // (e.g. SapsYearSubmissionTest) leaves a lock cached, every activity
    // approved after `now()` in this suite would be silently excluded
    // from the compliance count. Reset it before every test.
    SapsYearSubmission::clearCache();
    MemberCompliance::flush();
});

/**
 * Late-submission approval flow:
 *
 *  - `is_late` is a derived flag: true when `created_at` is after the
 *    activity-year deadline for the row's `activity_date`.
 *  - `approve()` on a late row records `is_late_approved` based on the
 *    admin's call-time flag (defaults to true).
 *  - `reject()` on a late row records the decision timestamp so the audit
 *    trail is complete.
 *  - MemberCompliance excludes unapproved late submissions from the
 *    current-year count and includes approved ones.
 */

/** Make the activity look late: pretend it was submitted after the deadline. */
function laBackdateCreated(ShootingActivity $activity, Carbon $createdAt): void
{
    $activity->timestamps = false;
    $activity->forceFill(['created_at' => $createdAt])->save();
    $activity->timestamps = true;
    $activity->refresh();
}

function laActivity(User $member, Carbon $activityDate, ?Carbon $createdAt = null, string $status = 'pending'): ShootingActivity
{
    $activity = ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => $activityDate,
        'location' => 'Test range',
        'status' => $status,
    ]);

    if ($createdAt !== null) {
        laBackdateCreated($activity, $createdAt);
    }

    return $activity;
}

function laDedicatedMemberWithActive(): User
{
    $member = User::factory()->create(['role' => 'member']);
    $type = MembershipType::firstOrCreate(
        ['slug' => 'la-ded-annual'],
        [
            'name' => 'LA Dedicated Annual',
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

    Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => 'web',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ]);

    return $member;
}

afterEach(function () {
    ShootingActivity::$nextLateApprovalCountsToward = null;
    MemberCompliance::flush();
    SapsYearSubmission::clearCache();
});

it('is_late is true when the activity was submitted after the deadline for its year', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 5),
    );

    expect($activity->is_late)->toBeTrue();
});

it('is_late is false when the activity was submitted before the deadline', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 10, 15),
    );

    expect($activity->is_late)->toBeFalse();
});

it('approve on a non-late activity does not touch is_late_approved', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 7, 15),
    );
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $activity->approve($admin);

    expect($activity->fresh()->is_late_approved)->toBeFalse();
    expect($activity->fresh()->late_decided_at)->toBeNull();
});

it('approve on a late activity defaults is_late_approved to true', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 5),
    );
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $activity->approve($admin);

    $fresh = $activity->fresh();
    expect($fresh->is_late_approved)->toBeTrue();
    expect($fresh->late_decided_by)->toBe($admin->id);
    expect($fresh->late_decided_at)->not->toBeNull();
});

it('approve on a late activity can explicitly decline the compliance count', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 5),
    );
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $activity->approve($admin, countTowardCompliance: false);

    $fresh = $activity->fresh();
    expect($fresh->status)->toBe('approved');
    expect($fresh->is_late_approved)->toBeFalse();
    expect($fresh->late_decided_at)->not->toBeNull();
});

it('reject on a late activity records the late-decision metadata', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 5),
    );
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $activity->reject($admin, 'Missing evidence');

    $fresh = $activity->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->is_late_approved)->toBeFalse();
    expect($fresh->late_decided_by)->toBe($admin->id);
});

it('approveFromQueue honours the per-request static flag then clears it', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $activity = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 5),
    );
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    ShootingActivity::$nextLateApprovalCountsToward = false;
    $activity->approveFromQueue($admin);

    expect($activity->fresh()->is_late_approved)->toBeFalse();
    expect(ShootingActivity::$nextLateApprovalCountsToward)->toBeNull();
});

it('MemberCompliance excludes an approved late activity from the current year count by default', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Three on-time approved activities + one late one.
    foreach ([1, 2, 3] as $i) {
        $a = laActivity(
            $member,
            activityDate: Carbon::create($year, 3 + $i, 1),
            createdAt: Carbon::create($year, 3 + $i, 1)->addDays(1),
        );
        $a->approve($admin);
    }
    $late = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 11, 20),
    );
    // Approve the late one WITHOUT letting it count.
    $late->approve($admin, countTowardCompliance: false);

    MemberCompliance::flush();
    $member = $member->fresh();

    $meta = MemberCompliance::for($member)->requirement('activities')->meta;

    expect($meta['current_year_count'])->toBe(3);
});

it('MemberCompliance includes an approved late activity when the admin ticked count=true', function () {
    $member = laDedicatedMemberWithActive();
    $year = (int) now()->year;
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // One on-time activity + one late activity, approved WITH count=true.
    $a = laActivity(
        $member,
        activityDate: Carbon::create($year, 6, 1),
        createdAt: Carbon::create($year, 6, 2),
    );
    $a->approve($admin);

    $late = laActivity(
        $member,
        activityDate: Carbon::create($year, 7, 1),
        createdAt: Carbon::create($year, 11, 10),
    );
    $late->approve($admin, countTowardCompliance: true);

    MemberCompliance::flush();
    $member = $member->fresh();

    $meta = MemberCompliance::for($member)->requirement('activities')->meta;

    expect($meta['current_year_count'])->toBe(2);
});
