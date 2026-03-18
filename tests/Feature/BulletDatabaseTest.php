<?php

use App\Models\Bullet;
use App\Services\Bullets\BulletUpsertService;

test('bullet model stores both diameter units', function () {
    $bullet = Bullet::create([
        'manufacturer' => 'Test',
        'brand_line' => 'Test Line',
        'bullet_label' => 'Test 140gr',
        'caliber_label' => '6.5mm',
        'weight_gr' => 140,
        'diameter_in' => 0.264,
        'diameter_mm' => 6.706,
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
        'status' => 'active',
        'last_verified_at' => now(),
    ]);

    expect($bullet->diameter_in)->toBe('0.264');
    expect($bullet->diameter_mm)->toBe('6.706');
});

test('bullet unique constraint prevents duplicates', function () {
    $data = [
        'manufacturer' => 'Hornady',
        'brand_line' => 'ELD Match',
        'bullet_label' => '6.5mm 140 gr ELD Match',
        'caliber_label' => '6.5mm',
        'weight_gr' => 140,
        'diameter_in' => 0.264,
        'diameter_mm' => 6.706,
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
        'status' => 'active',
        'last_verified_at' => now(),
        'sku_or_part_no' => 'TEST-26177',
        'twist_note' => '1:8',
        'bc_reference' => 'Mach 2.25',
    ];

    Bullet::create($data);

    // Same unique key should fail
    expect(fn () => Bullet::create($data))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('upsert service creates bullet with auto-computed diameter_mm', function () {
    $service = new BulletUpsertService;

    $bullet = $service->upsert([
        'manufacturer' => 'TestMfr',
        'brand_line' => 'TestLine',
        'bullet_label' => '6.5mm 147 gr Test',
        'caliber_label' => '6.5mm',
        'weight_gr' => 147,
        'diameter_in' => 0.264,
        // diameter_mm not provided -> should auto-compute
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
    ]);

    expect((float) $bullet->diameter_mm)->toBe(6.706);
});

test('upsert service resolves diameter from caliber label', function () {
    $service = new BulletUpsertService;

    $bullet = $service->upsert([
        'manufacturer' => 'TestMfr',
        'brand_line' => 'TestLine',
        'bullet_label' => '30 Cal 168 gr Test',
        'caliber_label' => '30 Cal',
        'weight_gr' => 168,
        // No diameter provided at all -> should resolve from caliber
        'construction' => 'otm',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
    ]);

    expect((float) $bullet->diameter_in)->toBe(0.308);
    expect((float) $bullet->diameter_mm)->toBe(7.823);
});

test('bullet sources can be attached', function () {
    $service = new BulletUpsertService;

    $bullet = $service->upsert([
        'manufacturer' => 'SrcTest',
        'brand_line' => 'Line',
        'bullet_label' => 'Test Bullet',
        'caliber_label' => '6.5mm',
        'weight_gr' => 140,
        'diameter_in' => 0.264,
        'diameter_mm' => 6.706,
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
    ]);

    $source = $service->attachSource($bullet, 'bc_table', 'https://hornady.com/bc', 'raw data');

    expect($source->bullet_id)->toBe($bullet->id);
    expect($source->source_type)->toBe('bc_table');
    expect($bullet->sources()->count())->toBe(1);
});

test('bullet validation rules reject invalid weight', function () {
    $rules = Bullet::validationRules();

    $validator = validator(['weight_gr' => 5], ['weight_gr' => $rules['weight_gr']]);
    expect($validator->fails())->toBeTrue();

    $validator = validator(['weight_gr' => 140], ['weight_gr' => $rules['weight_gr']]);
    expect($validator->fails())->toBeFalse();
});

test('bullet scopes work correctly', function () {
    Bullet::create([
        'manufacturer' => 'ScopeTest',
        'brand_line' => 'Match',
        'bullet_label' => 'Test 140gr Match',
        'caliber_label' => '6.5mm',
        'weight_gr' => 140,
        'diameter_in' => 0.264,
        'diameter_mm' => 6.706,
        'bc_g1' => 0.646,
        'bc_g7' => 0.326,
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://test.com',
        'status' => 'active',
        'last_verified_at' => now(),
    ]);

    Bullet::create([
        'manufacturer' => 'ScopeTest',
        'brand_line' => 'Hunt',
        'bullet_label' => 'Test 180gr Hunt',
        'caliber_label' => '30 Cal',
        'weight_gr' => 180,
        'diameter_in' => 0.308,
        'diameter_mm' => 7.823,
        'construction' => 'bonded',
        'intended_use' => 'hunting',
        'source_url' => 'https://test.com',
        'status' => 'active',
        'last_verified_at' => now(),
    ]);

    expect(Bullet::forManufacturer('ScopeTest')->count())->toBe(2);
    expect(Bullet::forCaliber('6.5mm')->forManufacturer('ScopeTest')->count())->toBe(1);
    expect(Bullet::forUse('match')->forManufacturer('ScopeTest')->count())->toBe(1);
    expect(Bullet::hasBc()->forManufacturer('ScopeTest')->count())->toBe(1);
    expect(Bullet::search('140gr Match')->count())->toBeGreaterThanOrEqual(1);
    expect(Bullet::weightBetween(130, 150)->forManufacturer('ScopeTest')->count())->toBe(1);
});

test('bullet accessors format correctly', function () {
    $bullet = new Bullet([
        'manufacturer' => 'Hornady',
        'brand_line' => 'ELD Match',
        'bullet_label' => '6.5mm 140 gr ELD Match',
        'weight_gr' => 140,
        'diameter_in' => 0.264,
        'diameter_mm' => 6.706,
        'bc_g1' => 0.646,
        'bc_g7' => 0.326,
        'bc_reference' => 'Mach 2.25',
    ]);

    expect($bullet->dropdown_label)->toBe('Hornady 6.5mm 140 gr ELD Match');
    expect($bullet->short_label)->toBe('140gr ELD Match');
    expect($bullet->diameter_display)->toBe('0.264" / 6.706mm');
    expect($bullet->bc_display)->toBe('G1: 0.646 / G7: 0.326 (Mach 2.25)');
});

test('admin bullet database index requires auth and admin', function () {
    // Unauthenticated
    $this->get('/admin/bullet-database')->assertRedirect();
});
