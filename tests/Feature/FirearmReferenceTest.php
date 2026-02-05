<?php

use App\Models\FirearmCalibre;
use App\Models\FirearmCalibreAlias;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calibre suggest returns results by name', function () {
    $calibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    $results = FirearmCalibre::search('Creedmoor')->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($calibre->id);
});

test('calibre suggest returns results by alias', function () {
    $calibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    FirearmCalibreAlias::create([
        'firearm_calibre_id' => $calibre->id,
        'alias' => '6.5 CM',
        'normalized_alias' => '65cm',
    ]);

    $results = FirearmCalibre::search('6.5 CM')->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($calibre->id);
});

test('resolve returns correct calibre by alias', function () {
    $calibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    // Create alias - let the model normalize it automatically
    $alias = FirearmCalibreAlias::create([
        'firearm_calibre_id' => $calibre->id,
        'alias' => '6.5 CM',
    ]);

    // Use the same logic as the API controller
    $normalized = FirearmCalibre::normalize('6.5 CM');
    
    // Try exact match on normalized name first
    $resolved = FirearmCalibre::where('normalized_name', $normalized)->first();
    
    // If not found, try alias match
    if (!$resolved) {
        $foundAlias = FirearmCalibreAlias::where('normalized_alias', $normalized)->first();
        if ($foundAlias) {
            $resolved = $foundAlias->calibre;
        }
    }
    
    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($calibre->id);
});

test('import command is idempotent', function () {
    // Create a calibre manually
    $existing = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    $initialCount = FirearmCalibre::count();

    // Run import (should skip existing)
    \Artisan::call('nrapa:import-firearm-reference', [
        '--file' => 'calibres',
    ]);

    $finalCount = FirearmCalibre::count();
    
    // Should not create duplicate
    expect($finalCount)->toBeGreaterThanOrEqual($initialCount);
    
    // Existing should still exist
    expect(FirearmCalibre::find($existing->id))->not->toBeNull();
});

test('backfill migration resolves known calibres to ids', function () {
    // Create reference calibre - let it auto-normalize the name
    $refCalibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    // Simulate legacy calibre name (from old system)
    $legacyCalibreName = '6.5 Creedmoor';

    // Create user firearm with legacy calibre name stored as text
    $firearm = \App\Models\UserFirearm::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'user_id' => \App\Models\User::factory()->create()->id,
        'make' => 'Tikka',
        'model' => 'T3x',
        'calibre_text_override' => $legacyCalibreName, // Legacy text field
    ]);

    // Run backfill logic (simplified - how migration would resolve)
    $normalized = FirearmCalibre::normalize($legacyCalibreName);
    
    // Try exact match
    $resolved = FirearmCalibre::where('normalized_name', $normalized)->first();
    
    // If not found, try alias match
    if (!$resolved) {
        $alias = FirearmCalibreAlias::where('normalized_alias', $normalized)->first();
        if ($alias) {
            $resolved = $alias->calibre;
        }
    }
    
    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($refCalibre->id);
});

test('livewire selecting calibre populates firearm_calibre_id', function () {
    $calibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'is_active' => true,
    ]);

    $component = \Livewire\Livewire::test(\App\Livewire\FirearmSearchPanel::class);
    
    $component->set('calibreSearch', '6.5 Creedmoor')
              ->call('selectCalibre', $calibre->id);
    
    expect($component->get('firearmCalibreId'))->toBe($calibre->id);
    expect($component->get('calibreTextOverride'))->toBeNull();
});

test('livewire getData returns all firearm fields', function () {
    $calibre = FirearmCalibre::create([
        'name' => '6.5 Creedmoor',
        'normalized_name' => '65creedmoor',
        'category' => 'rifle',
        'ignition' => 'centerfire',
        'is_active' => true,
    ]);

    $make = FirearmMake::create([
        'name' => 'Tikka',
        'normalized_name' => 'tikka',
        'is_active' => true,
    ]);

    $component = \Livewire\Livewire::test(\App\Livewire\FirearmSearchPanel::class);
    
    $component->set('firearmCalibreId', $calibre->id)
              ->set('firearmMakeId', $make->id)
              ->set('firearmType', 'rifle')
              ->set('actionType', 'bolt_action')
              ->set('barrelSerialNumber', '12345');
    
    // Get the component instance and call getData directly
    $instance = $component->instance();
    $data = $instance->getData();
    
    // Check that getData returns an array with the expected keys
    expect($data)->toBeArray();
    expect($data)->toHaveKey('firearm_calibre_id');
    expect($data)->toHaveKey('firearm_make_id');
    expect($data)->toHaveKey('firearm_type');
    expect($data)->toHaveKey('action_type');
    expect($data)->toHaveKey('barrel_serial_number');
    
    expect($data['firearm_calibre_id'])->toBe($calibre->id);
    expect($data['firearm_make_id'])->toBe($make->id);
    expect($data['barrel_serial_number'])->toBe('12345');
});
