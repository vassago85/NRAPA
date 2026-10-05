<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function earlyRenewalType(): MembershipType
{
    return MembershipType::create([
        'slug' => 'early-renewal-annual-'.uniqid(),
        'name' => 'Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'expiry_rule' => 'rolling',
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'renewal_price' => 400.00,
        'is_active' => true,
    ]);
}

function earlyRenewalMembership(User $user, MembershipType $type, \Carbon\CarbonInterface $activatedAt, \Carbon\CarbonInterface $expiresAt): Membership
{
    return Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => $activatedAt,
        'approved_at' => $activatedAt,
        'activated_at' => $activatedAt,
        'expires_at' => $expiresAt,
    ]);
}

test('active member can renew months before expiry', function () {
    $user = User::factory()->create();
    $type = earlyRenewalType();

    $membership = earlyRenewalMembership($user, $type, now()->subMonths(8), now()->addMonths(4));

    expect($membership->isRenewable())->toBeTrue();
});

test('early renewal keeps the original expiry anniversary instead of resetting to the renewal date', function () {
    $user = User::factory()->create();
    $type = earlyRenewalType();

    $originalExpiry = now()->addMonths(2)->startOfDay();
    $current = earlyRenewalMembership($user, $type, $originalExpiry->copy()->subYear(), $originalExpiry);

    $renewal = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'applied',
        'applied_at' => now(),
        'previous_membership_id' => $current->id,
    ]);

    $newExpiry = \Carbon\Carbon::parse($renewal->calculateRenewalAwareExpiry());

    expect($newExpiry->toDateString())->toBe($originalExpiry->copy()->addYear()->toDateString());
});

test('join date survives an early renewal', function () {
    $user = User::factory()->create();
    $type = earlyRenewalType();

    $joinedAt = now()->subMonths(10)->startOfDay();
    $current = earlyRenewalMembership($user, $type, $joinedAt, $joinedAt->copy()->addYear());

    $renewal = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'applied',
        'applied_at' => now(),
        'previous_membership_id' => $current->id,
    ]);
    $renewal->activate();
    $renewal->retireOtherActiveMemberships();

    expect($user->fresh()->joinedOn()->toDateString())->toBe($joinedAt->toDateString());
});

test('member already paid up more than one cycle ahead cannot renew again', function () {
    $user = User::factory()->create();
    $type = earlyRenewalType();

    $membership = earlyRenewalMembership($user, $type, now()->subMonth(), now()->addMonths(13));

    expect($membership->isRenewable())->toBeFalse()
        ->and($membership->isRenewalUpcoming())->toBeTrue()
        ->and($membership->renewal_window_opens_at->toDateString())
        ->toBe(now()->addMonth()->toDateString());
});
