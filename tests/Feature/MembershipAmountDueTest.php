<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;

test('amount due for type change uses change_amount when set', function () {
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $basic = MembershipType::create([
        'slug' => 'basic-test',
        'name' => 'Basic Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 700,
        'renewal_price' => 550,
        'upgrade_price' => null,
        'is_active' => true,
    ]);

    $dedicated = MembershipType::create([
        'slug' => 'dedicated-both-test',
        'name' => 'Dedicated Hunter & Sport Shooter',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 0,
        'renewal_price' => 550,
        'upgrade_price' => 1200,
        'is_active' => true,
    ]);

    $previous = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $basic->id,
        'status' => 'active',
        'applied_at' => now()->subYear(),
        'approved_at' => now()->subYear(),
        'activated_at' => now()->subYear(),
        'expires_at' => now()->addMonth(),
    ]);

    $upgrade = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $dedicated->id,
        'previous_membership_id' => $previous->id,
        'status' => 'pending_payment',
        'applied_at' => now(),
        'change_amount' => 1200,
    ]);

    expect($upgrade->isTypeChange())->toBeTrue()
        ->and($upgrade->isRenewal())->toBeFalse()
        ->and($upgrade->amount_due)->toBe(1200.0);
});

test('amount due for renewal uses renewal price not upgrade price', function () {
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $basic = MembershipType::create([
        'slug' => 'basic-renewal-test',
        'name' => 'Basic Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 700,
        'renewal_price' => 550,
        'upgrade_price' => null,
        'is_active' => true,
    ]);

    $previous = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $basic->id,
        'status' => 'active',
        'applied_at' => now()->subYear(),
        'approved_at' => now()->subYear(),
        'activated_at' => now()->subYear(),
        'expires_at' => now()->subMonth(),
    ]);

    $renewal = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $basic->id,
        'previous_membership_id' => $previous->id,
        'status' => 'applied',
        'applied_at' => now(),
        'source' => 'web',
    ]);

    expect($renewal->isRenewal())->toBeTrue()
        ->and($renewal->isTypeChange())->toBeFalse()
        ->and($renewal->amount_due)->toBe(550.0);
});

test('amount due for type change without change_amount uses upgrade price', function () {
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $basic = MembershipType::create([
        'slug' => 'basic-upgrade-fallback',
        'name' => 'Basic Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 700,
        'renewal_price' => 550,
        'upgrade_price' => null,
        'is_active' => true,
    ]);

    $dedicated = MembershipType::create([
        'slug' => 'dedicated-upgrade-fallback',
        'name' => 'Dedicated Hunter & Sport Shooter',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 0,
        'renewal_price' => 550,
        'upgrade_price' => 1200,
        'is_active' => true,
    ]);

    $previous = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $basic->id,
        'status' => 'active',
        'applied_at' => now()->subYear(),
        'approved_at' => now()->subYear(),
        'activated_at' => now()->subYear(),
        'expires_at' => now()->addMonth(),
    ]);

    $upgrade = Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $dedicated->id,
        'previous_membership_id' => $previous->id,
        'status' => 'pending_change',
        'applied_at' => now(),
    ]);

    expect($upgrade->amount_due)->toBe(1200.0);
});
