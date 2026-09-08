<?php

use App\Mail\LicenseExpiry;
use App\Models\FirearmComponent;
use App\Models\User;
use App\Models\UserFirearm;

test('display name falls back to type and primary serial when make/model/nickname are empty', function () {
    $user = User::factory()->create();

    $firearm = UserFirearm::create([
        'user_id' => $user->id,
        'firearm_type' => 'shotgun',
        'action' => 'pump_action',
        'receiver_serial_number' => 'L1584364',
        'license_expiry_date' => '2027-10-01',
    ]);

    FirearmComponent::create([
        'firearm_id' => $firearm->id,
        'type' => 'receiver',
        'serial' => 'L1584364',
    ]);

    expect($firearm->fresh()->display_name)->toBe('Shotgun · S/N L1584364');
});

test('display name prefers nickname over type and serial', function () {
    $user = User::factory()->create();

    $firearm = UserFirearm::create([
        'user_id' => $user->id,
        'nickname' => 'Bush Gun',
        'firearm_type' => 'shotgun',
        'receiver_serial_number' => 'L1584364',
    ]);

    expect($firearm->display_name)->toBe('Bush Gun');
});

test('license expiry email includes primary serial when legacy serial_number is empty', function () {
    $user = User::factory()->create(['name' => 'T.D. Elrick']);

    $firearm = UserFirearm::create([
        'user_id' => $user->id,
        'firearm_type' => 'shotgun',
        'action' => 'pump_action',
        'receiver_serial_number' => 'L1584364',
        'license_expiry_date' => '2027-10-01',
    ]);

    FirearmComponent::create([
        'firearm_id' => $firearm->id,
        'type' => 'receiver',
        'serial' => 'L1584364',
    ]);

    $html = (new LicenseExpiry($user, $firearm->fresh(), 388))->render();

    expect($html)
        ->toContain('Shotgun · S/N L1584364')
        ->toContain('L1584364')
        ->not->toContain('Unnamed Firearm');
});
