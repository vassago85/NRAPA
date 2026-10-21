<?php

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use App\Models\FirearmMake;
use App\Models\User;
use App\Models\UserFirearm;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->member = User::factory()->create();
    actingAs($this->member);

    $this->calibre = FirearmCalibre::create([
        'name' => '.375 H&H Magnum',
        'category' => 'rifle',
        'is_active' => true,
    ]);
    FirearmCalibreAlias::create([
        'firearm_calibre_id' => $this->calibre->id,
        'alias' => '.375 H&H',
    ]);
    FirearmCalibreAlias::create([
        'firearm_calibre_id' => $this->calibre->id,
        'alias' => '.375 Holland & Holland',
    ]);

    $this->make = FirearmMake::query()->firstOrCreate(
        ['name' => 'Sauer & Sohn'],
        ['country' => 'Germany', 'is_active' => true],
    );
});

test('virtual safe links a typed .375 H&H and Sauer & Sohn to the catalogue', function () {
    Livewire::test('pages::member.armoury.create')
        ->set('firearm_type', 'rifle')
        ->set('action', 'bolt_action')
        ->set('serial_number', 'K8730')
        ->set('nickname', '375')
        ->set('calibre_custom', '.375 H&H')
        ->set('make_custom', 'Sauer & Sohn')
        ->call('save')
        ->assertHasNoErrors();

    $firearm = UserFirearm::query()->where('receiver_serial_number', 'K8730')->first();

    expect($firearm)->not->toBeNull()
        ->and($firearm->firearm_calibre_id)->toBe($this->calibre->id)
        ->and($firearm->firearm_make_id)->toBe($this->make->id)
        ->and($firearm->calibre_display)->toBe('.375 H&H Magnum')
        ->and($firearm->make_display)->toBe('Sauer & Sohn')
        ->and($firearm->calibre_text_override)->toBeNull()
        ->and($firearm->make_text_override)->toBeNull();
});

test('virtual safe still stores a make and calibre that are not in the catalogue', function () {
    Livewire::test('pages::member.armoury.create')
        ->set('firearm_type', 'rifle')
        ->set('action', 'bolt_action')
        ->set('serial_number', 'CUSTOM-1')
        ->set('calibre_custom', '.375 H and H wildcat')
        ->set('make_custom', 'Sauer and Sohn custom shop')
        ->call('save')
        ->assertHasNoErrors();

    $firearm = UserFirearm::query()->where('receiver_serial_number', 'CUSTOM-1')->first();

    expect($firearm->firearm_calibre_id)->toBeNull()
        ->and($firearm->firearm_make_id)->toBeNull()
        ->and($firearm->calibre_display)->toBe('.375 H and H wildcat')
        ->and($firearm->make_display)->toBe('Sauer and Sohn custom shop');
});

test('editing a saved rifle accepts the .375 H&H alias and Sauer and Sohn wording', function () {
    $firearm = UserFirearm::create([
        'user_id' => $this->member->id,
        'firearm_type' => 'rifle',
        'action' => 'bolt_action',
        'nickname' => '375',
        'receiver_serial_number' => 'K8730',
        'license_status' => 'valid',
    ]);

    Livewire::test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->set('calibre_custom', '.375 H and H')
        ->set('make_custom', 'Sauer and Sohn')
        ->call('save')
        ->assertHasNoErrors();

    $firearm->refresh();

    expect($firearm->firearm_calibre_id)->toBe($this->calibre->id)
        ->and($firearm->firearm_make_id)->toBe($this->make->id)
        ->and($firearm->calibre_display)->toBe('.375 H&H Magnum')
        ->and($firearm->make_display)->toBe('Sauer & Sohn');
});

test('the calibre and make dropdown syncs through the Livewire $wire proxy', function () {
    $source = file_get_contents(resource_path('views/components/searchable-select.blade.php'));

    expect($source)->toContain('el.__livewire.$wire')
        ->and($source)->not->toContain('if (el.__livewire) return el.__livewire;');
});
