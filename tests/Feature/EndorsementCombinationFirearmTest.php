<?php

use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\FirearmCalibre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('combination is a selectable firearm category', function () {
    $options = EndorsementFirearm::getCategoryOptions();

    expect($options)->toHaveKey(EndorsementFirearm::CATEGORY_COMBINATION);
    expect($options[EndorsementFirearm::CATEGORY_COMBINATION])->toContain('Combination');
});

test('combination firearm persists two calibres and exposes both via display accessors', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $request = EndorsementRequest::create([
        'user_id' => $member->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_DRAFT,
    ]);

    $rifleCalibre = FirearmCalibre::create([
        'name' => '.308 Winchester',
        'normalized_name' => '308winchester',
        'category' => 'rifle',
        'ignition' => 'centerfire',
        'is_active' => true,
    ]);

    $shotgunCalibre = FirearmCalibre::create([
        'name' => '12 Gauge',
        'normalized_name' => '12gauge',
        'category' => 'shotgun',
        'is_active' => true,
    ]);

    $firearm = EndorsementFirearm::create([
        'endorsement_request_id' => $request->id,
        'firearm_category' => EndorsementFirearm::CATEGORY_COMBINATION,
        // Combination firearms don't record an action.
        'action_type' => null,
        'make' => 'Krieghoff',
        'model' => 'Classic',
        'barrel_serial_number' => 'KG-123',
        'firearm_calibre_id' => $rifleCalibre->id,
        'firearm_calibre_id_2' => $shotgunCalibre->id,
    ]);

    $firearm->refresh()->load('firearmCalibre', 'firearmCalibre2');

    expect($firearm->isCombination())->toBeTrue();
    expect($firearm->firearm_calibre_id)->toBe($rifleCalibre->id);
    expect($firearm->firearm_calibre_id_2)->toBe($shotgunCalibre->id);
    expect($firearm->calibre_display)->toBe('.308 Winchester');
    expect($firearm->calibre_display_2)->toBe('12 Gauge');
});

test('second calibre falls back to text override when no FK is set', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $request = EndorsementRequest::create([
        'user_id' => $member->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_DRAFT,
    ]);

    $firearm = EndorsementFirearm::create([
        'endorsement_request_id' => $request->id,
        'firearm_category' => EndorsementFirearm::CATEGORY_COMBINATION,
        'make' => 'Custom',
        'barrel_serial_number' => 'CB-001',
        'calibre_text_override' => '.22 LR',
        'calibre_text_override_2' => '.410 Bore',
    ]);

    expect($firearm->calibre_display)->toBe('.22 LR');
    expect($firearm->calibre_display_2)->toBe('.410 Bore');
});

test('non-combination firearms have no second calibre display', function () {
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $request = EndorsementRequest::create([
        'user_id' => $member->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_DRAFT,
    ]);

    $firearm = EndorsementFirearm::create([
        'endorsement_request_id' => $request->id,
        'firearm_category' => EndorsementFirearm::CATEGORY_RIFLE,
        'action_type' => EndorsementFirearm::ACTION_BOLT_ACTION,
        'make' => 'Tikka',
        'model' => 'T3x',
        'barrel_serial_number' => 'TK-500',
        'calibre_text_override' => '.308 Winchester',
    ]);

    expect($firearm->isCombination())->toBeFalse();
    expect($firearm->calibre_display_2)->toBeNull();
});
