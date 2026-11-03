<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Support\Members\MemberCompliance;
use Livewire\Livewire;

beforeEach(function () {
    MemberCompliance::flush();
});

it('shows a lifetime member as up to date on the activities page with no target count', function () {
    $user = memberWithActivityType(lifetime: true);

    $html = Livewire::actingAs($user)
        ->test('pages::member.activities.index')
        ->html();

    expect($html)->toContain('Up to date')
        ->and($html)->toContain('You do not need to upload targets')
        ->and($html)->not->toMatch('/(?<![0-9])0\s*\/\s*2(?![0-9])/');
});

it('still shows the activity ratio to a dedicated member who must log targets', function () {
    $user = memberWithActivityType(lifetime: false);

    $html = Livewire::actingAs($user)
        ->test('pages::member.activities.index')
        ->html();

    expect($html)->toMatch('/(?<![0-9])0\s*\/\s*2(?![0-9])/')
        ->and($html)->not->toContain('Up to date');
});

it('shows up to date on the member dashboard without an activity ratio', function () {
    $user = memberWithActivityType(lifetime: true);

    $html = Livewire::actingAs($user)
        ->test('pages::member.dashboard')
        ->html();

    expect($html)->toContain('Up to date')
        ->and($html)->not->toMatch('/(?<![0-9])0\s*\/\s*2(?![0-9])/');
});

function memberWithActivityType(bool $lifetime): User
{
    $type = MembershipType::create([
        'slug' => $lifetime ? 'page-lifetime' : 'page-annual-dedicated',
        'name' => $lifetime ? 'Lifetime' : 'Annual Dedicated',
        'duration_type' => $lifetime ? 'lifetime' : 'annual',
        'duration_months' => $lifetime ? null : 12,
        'requires_renewal' => ! $lifetime,
        'expiry_rule' => $lifetime ? 'none' : 'rolling',
        'pricing_model' => $lifetime ? 'once_off' : 'annual',
        'initial_price' => 100,
        'renewal_price' => $lifetime ? 0 : 100,
        'allows_dedicated_status' => true,
        'dedicated_type' => MembershipType::DEDICATED_TYPE_BOTH,
        'requires_knowledge_test' => false,
        'is_active' => true,
    ]);

    $user = User::factory()->create(['role' => 'member']);

    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now()->subYears(2),
        'approved_at' => now()->subYears(2),
        'activated_at' => now()->subYears(2),
        'expires_at' => $lifetime ? null : now()->addMonths(6),
    ]);

    return $user;
}
