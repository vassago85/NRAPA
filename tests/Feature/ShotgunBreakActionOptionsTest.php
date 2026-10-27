<?php

use App\Models\EndorsementFirearm;
use App\Models\FirearmType;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Livewire\Livewire;

test('shotgun break action offers over and under and side by side', function () {
    $options = EndorsementFirearm::getActionTypeOptions(EndorsementFirearm::CATEGORY_SHOTGUN);
    $keys = array_keys($options);
    $break = array_search(EndorsementFirearm::ACTION_BREAK_ACTION, $keys, true);

    expect($break)->not->toBeFalse()
        ->and($keys[$break + 1])->toBe(EndorsementFirearm::ACTION_OVER_UNDER)
        ->and($keys[$break + 2])->toBe(EndorsementFirearm::ACTION_SIDE_BY_SIDE)
        ->and($options[EndorsementFirearm::ACTION_OVER_UNDER])->toBe('Break Neck - Under Over')
        ->and($options[EndorsementFirearm::ACTION_SIDE_BY_SIDE])->toBe('Break Neck - Side by Side');

    expect(EndorsementFirearm::getActionTypeOptions(EndorsementFirearm::CATEGORY_RIFLE))
        ->not->toHaveKey(EndorsementFirearm::ACTION_OVER_UNDER)
        ->and(EndorsementFirearm::getActionTypeOptions(EndorsementFirearm::CATEGORY_HANDGUN))
        ->not->toHaveKey(EndorsementFirearm::ACTION_SIDE_BY_SIDE);

    $firearm = new EndorsementFirearm([
        'firearm_category' => EndorsementFirearm::CATEGORY_SHOTGUN,
        'action_type' => EndorsementFirearm::ACTION_SIDE_BY_SIDE,
    ]);

    expect($firearm->action_type_label)->toBe('Break Neck - Side by Side');
});

test('activity firearm types include over and under and side by side shotguns', function () {
    $overUnder = FirearmType::query()->where('slug', 'break-action-shotgun-over-under')->first();
    $sideBySide = FirearmType::query()->where('slug', 'break-action-shotgun-side-by-side')->first();

    expect($overUnder)->not->toBeNull()
        ->and($overUnder->name)->toBe('Break Neck - Under Over')
        ->and($overUnder->category)->toBe(FirearmType::CATEGORY_SHOTGUN)
        ->and($overUnder->action_type)->toBe(FirearmType::ACTION_OVER_UNDER)
        ->and($overUnder->is_active)->toBeTrue()
        ->and($sideBySide)->not->toBeNull()
        ->and($sideBySide->name)->toBe('Break Neck - Side by Side')
        ->and($sideBySide->action_type)->toBe(FirearmType::ACTION_SIDE_BY_SIDE)
        ->and($sideBySide->category)->toBe(FirearmType::CATEGORY_SHOTGUN)
        ->and($sideBySide->is_active)->toBeTrue()
        ->and($sideBySide->sort_order)->toBeGreaterThan($overUnder->sort_order);

    $options = FirearmType::getActionTypeOptions(FirearmType::CATEGORY_SHOTGUN);
    $keys = array_keys($options);
    $break = array_search(FirearmType::ACTION_BREAK_ACTION, $keys, true);

    expect($keys[$break + 1])->toBe(FirearmType::ACTION_OVER_UNDER)
        ->and($keys[$break + 2])->toBe(FirearmType::ACTION_SIDE_BY_SIDE)
        ->and($options[FirearmType::ACTION_OVER_UNDER])->toBe('Break Neck - Under Over');
});

test('occasional letter form lists the shotgun break action configurations', function () {
    $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $type = MembershipType::create([
        'slug' => 'shotgun-action-basic-'.uniqid(),
        'name' => 'Basic Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'expiry_rule' => 'rolling',
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 400.00,
        'is_active' => true,
        'dedicated_type' => null,
    ]);
    Membership::create([
        'user_id' => $user->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'source' => 'import',
        'applied_at' => now()->subMonths(2),
        'approved_at' => now()->subMonths(2),
        'activated_at' => now()->subMonths(2),
        'expires_at' => now()->addMonths(10),
        'membership_number' => 'NRAPA-SG-'.random_int(1000, 9999),
    ]);

    Livewire::actingAs($user)
        ->test('pages::member.endorsements.occasional')
        ->set('firearmType', 'shotgun')
        ->assertSee('Break Neck - Under Over')
        ->assertSee('Break Neck - Side by Side');
});
