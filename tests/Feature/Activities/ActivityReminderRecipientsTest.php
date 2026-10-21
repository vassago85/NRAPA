<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Support\Members\ActivityReminderRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Covers who the year-end activity-reminder recipient query includes and
 * excludes. The resolver and the query must agree on the rules — see
 * MemberComplianceTest for the compliance side; this file is the sender side.
 */

/** Shared type seeds matching the shape MemberCompliance expects. */
function reminderSeed(): array
{
    return [
        'annualDedicated' => MembershipType::create([
            'slug' => 'test-annual-dedicated',
            'name' => 'Annual Dedicated',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => true,
            'pricing_model' => 'annual',
            'initial_price' => 900,
            'renewal_price' => 800,
            'allows_dedicated_status' => true,
            'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
            'is_active' => true,
        ]),
        'lifetime' => MembershipType::create([
            'slug' => 'test-lifetime',
            'name' => 'Lifetime',
            'duration_type' => 'lifetime',
            'requires_renewal' => false,
            'expiry_rule' => 'none',
            'pricing_model' => 'once_off',
            'initial_price' => 15000,
            'allows_dedicated_status' => true,
            'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
            'is_active' => true,
        ]),
        'honorary' => MembershipType::create([
            'slug' => 'test-honorary',
            'name' => 'Honorary',
            'duration_type' => 'annual',
            'duration_months' => 12,
            'requires_renewal' => false,
            'pricing_model' => 'none',
            'initial_price' => 0,
            'allows_dedicated_status' => true,
            'is_honorary' => true,
            'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
            'is_active' => true,
        ]),
    ];
}

/**
 * Build a current-year non-join-year member on $type. Backdates applied_at so
 * the user is not in their join-year for the current calendar year.
 */
function longStandingMemberOn(MembershipType $type): User
{
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => $type->isLifetime() ? null : now()->addMonths(6),
    ]);
    return $user;
}

/** Approve N shooting activities for the user inside the Jan–Oct window of $year. */
function approveActivities(User $user, int $year, int $count): void
{
    $date = \Carbon\Carbon::create($year, 5, 1);
    for ($i = 0; $i < $count; $i++) {
        ShootingActivity::create([
            'user_id' => $user->id,
            'activity_date' => $date->copy()->addDays($i),
            'activity_type' => 'range_session',
            'track' => 'sport',
            'status' => 'approved',
            'approved_at' => now(),
            'location' => 'Test range',
            'description' => 'test',
        ]);
    }
}

it('includes a current annual-dedicated member who is short on activities', function () {
    $types = reminderSeed();
    $user = longStandingMemberOn($types['annualDedicated']);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->toContain($user->id);
});

it('excludes a member who already has the minimum approved activities this year', function () {
    $types = reminderSeed();
    $user = longStandingMemberOn($types['annualDedicated']);
    approveActivities($user, now()->year, ActivityReminderRecipients::requiredCount());

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes lifetime members', function () {
    $types = reminderSeed();
    $user = longStandingMemberOn($types['lifetime']);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes honorary members', function () {
    $types = reminderSeed();
    $user = longStandingMemberOn($types['honorary']);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes members whose membership type does not allow dedicated status', function () {
    // The clarification letter feature: Basic / transfer-only types that do
    // not carry the activity requirement must not land in the chase.
    $basic = MembershipType::create([
        'slug' => 'test-basic',
        'name' => 'Basic',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 300,
        'renewal_price' => 300,
        'allows_dedicated_status' => false,
        'dedicated_type' => null,
        'is_active' => true,
    ]);

    $user = longStandingMemberOn($basic);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes a member in their join-year', function () {
    $types = reminderSeed();
    $user = User::factory()->create(['role' => User::ROLE_MEMBER, 'created_at' => now()->subMonths(2)]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $types['annualDedicated']->id,
        'status' => 'active',
        'applied_at' => now()->subMonths(2), // this calendar year
        'approved_at' => now()->subMonths(2),
        'activated_at' => now()->subMonths(2),
        'expires_at' => now()->addMonths(10),
    ]);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes members with no current membership (expired non-lifetime)', function () {
    $types = reminderSeed();
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $types['annualDedicated']->id,
        'status' => 'expired',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->subMonths(2),
    ]);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($user->id);
});

it('excludes admin users even if they would otherwise match', function () {
    $types = reminderSeed();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Membership::create([
        'user_id' => $admin->id,
        'membership_type_id' => $types['annualDedicated']->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => now()->addMonths(6),
    ]);

    $recipients = ActivityReminderRecipients::query()->get();

    expect($recipients->pluck('id')->all())->not->toContain($admin->id);
});

it('counts only approved activities toward the minimum', function () {
    $types = reminderSeed();
    $user = longStandingMemberOn($types['annualDedicated']);

    // Pending activities should not count — user remains in the recipient list.
    ShootingActivity::create([
        'user_id' => $user->id,
        'activity_date' => \Carbon\Carbon::create(now()->year, 5, 1),
        'activity_type' => 'range_session',
        'track' => 'sport',
        'status' => 'pending',
        'location' => 'Test range',
        'description' => 'test',
    ]);
    ShootingActivity::create([
        'user_id' => $user->id,
        'activity_date' => \Carbon\Carbon::create(now()->year, 5, 2),
        'activity_type' => 'range_session',
        'track' => 'sport',
        'status' => 'rejected',
        'location' => 'Test range',
        'description' => 'test',
    ]);

    expect(ActivityReminderRecipients::query()->pluck('id')->all())->toContain($user->id);
});
