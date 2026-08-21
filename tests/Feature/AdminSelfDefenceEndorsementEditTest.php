<?php

use App\Models\AuditLog;
use App\Models\EndorsementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSelfDefenceEndorsement(User $user, string $status = EndorsementRequest::STATUS_SUBMITTED): EndorsementRequest
{
    return EndorsementRequest::create([
        'user_id' => $user->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE,
        'status' => $status,
        'submitted_at' => now()->subHours(2),
        'firearm_make' => 'Glock',
        'firearm_model' => '19',
        'firearm_calibre' => '9mm Parabellum',
        'firearm_type' => 'handgun',
        'firearm_serial' => 'Barrel: ABC12345',
        'declaration_accepted_at' => now()->subHours(2),
    ]);
}

test('parseSelfDefenceSerials splits labelled barrel frame and receiver values', function () {
    expect(EndorsementRequest::parseSelfDefenceSerials('Barrel: ABC, Frame: DEF, Receiver: GHI'))
        ->toBe(['barrel' => 'ABC', 'frame' => 'DEF', 'receiver' => 'GHI']);
});

test('parseSelfDefenceSerials keeps an unlabelled legacy serial so it is not dropped', function () {
    expect(EndorsementRequest::parseSelfDefenceSerials('XYZ999'))
        ->toBe(['barrel' => 'XYZ999', 'frame' => '', 'receiver' => '']);
});

test('formatSelfDefenceSerials omits empty parts', function () {
    expect(EndorsementRequest::formatSelfDefenceSerials([
        'barrel' => '',
        'frame' => 'ABC12345',
        'receiver' => '',
    ]))->toBe('Frame: ABC12345');
});

test('admin can move a self-defence serial from barrel to frame and add action and ignition', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $request = makeSelfDefenceEndorsement($member);

    $this->actingAs($admin);

    Livewire::test('pages::admin.endorsements.show', ['request' => $request])
        ->call('openEditSelfDefenceModal')
        ->assertSet('showEditSelfDefenceModal', true)
        ->assertSet('editSdBarrelSerial', 'ABC12345')
        ->assertSet('editSdFrameSerial', '')
        ->set('editSdBarrelSerial', '')
        ->set('editSdFrameSerial', 'ABC12345')
        ->set('editSdFirearmActionType', 'semi_auto')
        ->set('editSdFirearmIgnitionType', 'centerfire')
        ->call('saveSelfDefenceFirearmDetails')
        ->assertHasNoErrors()
        ->assertSet('showEditSelfDefenceModal', false);

    $request->refresh();

    expect($request->firearm_serial)->toBe('Frame: ABC12345');
    expect($request->firearm_action_type)->toBe('semi_auto');
    expect($request->firearm_ignition_type)->toBe('centerfire');
    expect($request->firearm_action_type_label)->toBe('Semi-Automatic');
    expect($request->firearm_ignition_type_label)->toBe('Centerfire');

    expect(AuditLog::where('event', 'endorsement_self_defence_firearm_edited')
        ->where('auditable_id', $request->id)
        ->exists())->toBeTrue();
});

test('admin cannot save a self-defence firearm with no serial numbers', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
    $request = makeSelfDefenceEndorsement($member);

    $this->actingAs($admin);

    Livewire::test('pages::admin.endorsements.show', ['request' => $request])
        ->call('openEditSelfDefenceModal')
        ->set('editSdBarrelSerial', '')
        ->set('editSdFrameSerial', '')
        ->set('editSdReceiverSerial', '')
        ->call('saveSelfDefenceFirearmDetails')
        ->assertHasErrors(['editSdBarrelSerial']);

    $request->refresh();
    expect($request->firearm_serial)->toBe('Barrel: ABC12345');
});

test('reissueAsNewLetter copies self-defence action ignition and serial', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

    $source = makeSelfDefenceEndorsement($member, EndorsementRequest::STATUS_ISSUED);
    $source->update([
        'firearm_action_type' => 'semi_auto',
        'firearm_ignition_type' => 'centerfire',
        'firearm_serial' => 'Frame: ABC12345',
        'issued_at' => now()->subDay(),
        'letter_reference' => 'END-2026-00113',
    ]);

    $clone = $source->reissueAsNewLetter($admin);

    expect($clone->endorsement_type)->toBe(EndorsementRequest::ENDORSEMENT_TYPE_SELF_DEFENCE);
    expect($clone->firearm_serial)->toBe('Frame: ABC12345');
    expect($clone->firearm_action_type)->toBe('semi_auto');
    expect($clone->firearm_ignition_type)->toBe('centerfire');
    expect($clone->letter_reference)->toBeNull();
});
