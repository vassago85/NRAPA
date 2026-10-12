<?php

use App\Models\FirearmMake;
use App\Models\FirearmModel;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Models\UserFirearm;
use Livewire\Livewire;

/**
 * Regression: firearms that only have the plain make/model text (e.g. imported before
 * the firearm catalogue existed) lost their make and model when edited and saved.
 */
beforeEach(function () {
    $type = MembershipType::create([
        'slug' => 'armoury-legacy-annual',
        'name' => 'Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500,
        'is_active' => true,
    ]);

    $this->owner = User::factory()->create(['role' => User::ROLE_MEMBER, 'email_verified_at' => now()]);
    Membership::create([
        'user_id' => $this->owner->id,
        'membership_type_id' => $type->id,
        'status' => 'active',
        'applied_at' => now(),
        'activated_at' => now(),
        'expires_at' => now()->addYear(),
    ]);
});

function legacyFirearm(User $owner, array $attributes = []): UserFirearm
{
    return UserFirearm::create(array_merge([
        'user_id' => $owner->id,
        'firearm_type' => 'rifle',
        'action' => 'bolt_action',
        'make' => 'Tikka',
        'model' => 'T3x Varmint',
        'receiver_serial_number' => 'LEGACY-001',
        'license_status' => 'valid',
    ], $attributes));
}

test('edit form shows the plain make and model of a legacy firearm', function () {
    $firearm = legacyFirearm($this->owner);

    Livewire::actingAs($this->owner)
        ->test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->assertSet('make_custom', 'Tikka')
        ->assertSet('model_custom', 'T3x Varmint');
});

test('saving a legacy firearm without touching make or model keeps them', function () {
    $firearm = legacyFirearm($this->owner);

    Livewire::actingAs($this->owner)
        ->test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->set('nickname', 'Varmint rig')
        ->call('save')
        ->assertHasNoErrors();

    $firearm->refresh();
    expect($firearm->make)->toBe('Tikka')
        ->and($firearm->model)->toBe('T3x Varmint')
        ->and($firearm->nickname)->toBe('Varmint rig');
});

test('member can still clear or change a legacy make and model', function () {
    $firearm = legacyFirearm($this->owner);

    Livewire::actingAs($this->owner)
        ->test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->set('make_custom', 'Sako')
        ->set('model_custom', '')
        ->call('save')
        ->assertHasNoErrors();

    $firearm->refresh();
    expect($firearm->make)->toBe('Sako')
        ->and($firearm->model)->toBeNull();
});

test('catalogue-linked firearms do not pick up stale plain text as a custom value', function () {
    $make = FirearmMake::create(['name' => 'Howa', 'slug' => 'howa']);
    $model = FirearmModel::create(['firearm_make_id' => $make->id, 'name' => '1500', 'slug' => 'howa-1500']);
    $firearm = legacyFirearm($this->owner, [
        'firearm_make_id' => $make->id,
        'firearm_model_id' => $model->id,
        'make' => 'Howa',
        'model' => '1500',
    ]);

    Livewire::actingAs($this->owner)
        ->test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->assertSet('make_custom', '')
        ->assertSet('model_custom', '')
        ->call('save')
        ->assertHasNoErrors();

    $firearm->refresh();
    expect($firearm->firearm_make_id)->toBe($make->id)
        ->and($firearm->make)->toBe('Howa')
        ->and($firearm->model)->toBe('1500')
        ->and($firearm->make_text_override)->toBeNull();
});
