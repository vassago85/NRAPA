<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// Regression: production hit `SQLSTATE Data truncated for column 'firearm_type'`
// when a member added a firearm from the armoury create page. Migration
// 2026_01_30_300000_update_firearm_types_to_saps271_compliant removed
// `hand_machine_carbine` from the ENUM (new set: rifle|shotgun|handgun|combination|other),
// but the Volt form still offered it and still validated `in:...,hand_machine_carbine,...`.
// These tests lock the form's validation contract to the DB enum so the two
// can't drift apart again.

/**
 * The canonical SAPS 271 enum values that user_firearms.firearm_type currently accepts.
 * Kept in sync with database/migrations/2026_01_30_300000_update_firearm_types_to_saps271_compliant.php.
 */
const SAPS271_FIREARM_TYPES = ['rifle', 'shotgun', 'handgun', 'combination', 'other'];

beforeEach(function () {
    $this->member = User::factory()->create();
    actingAs($this->member);
});

test('armoury create rejects hand_machine_carbine (removed from SAPS 271 enum)', function () {
    Livewire::test('pages::member.armoury.create')
        ->set('firearm_type', 'hand_machine_carbine')
        ->set('action', 'bolt_action')
        ->set('serial_number', 'TEST-HMC-001')
        ->call('save')
        ->assertHasErrors(['firearm_type']);
});

test('armoury edit rejects hand_machine_carbine (removed from SAPS 271 enum)', function () {
    $firearm = \App\Models\UserFirearm::create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $this->member->id,
        'firearm_type' => 'rifle',
        'action' => 'bolt_action',
        'receiver_serial_number' => 'EDIT-BASE-001',
    ]);

    Livewire::test('pages::member.armoury.edit', ['firearm' => $firearm])
        ->set('firearm_type', 'hand_machine_carbine')
        ->call('save')
        ->assertHasErrors(['firearm_type']);
});

test('armoury create accepts all canonical SAPS 271 firearm types', function () {
    foreach (SAPS271_FIREARM_TYPES as $type) {
        $component = Livewire::test('pages::member.armoury.create')
            ->set('firearm_type', $type)
            ->set('action', 'bolt_action')
            ->set('serial_number', 'OK-'.strtoupper($type).'-'.uniqid());

        // Trigger validation only (avoid the full save path which needs more setup)
        $component->call('save');

        // If `firearm_type` is one of the canonical values, it must not appear
        // in the validator errors. Other fields may still fail — we only care
        // that firearm_type is accepted.
        expect($component->errors()->has('firearm_type'))
            ->toBeFalse("firearm_type '{$type}' should be accepted by the form validator");
    }
});
