<?php

use App\Models\Bullet;

test('inToMm converts correctly', function () {
    expect(Bullet::inToMm(0.264))->toBe(6.706);
    expect(Bullet::inToMm(0.308))->toBe(7.823);
    expect(Bullet::inToMm(0.224))->toBe(5.690);
    expect(Bullet::inToMm(1.0))->toBe(25.4);
});

test('mmToIn converts correctly', function () {
    expect(Bullet::mmToIn(6.706))->toBe(0.264);
    expect(Bullet::mmToIn(7.823))->toBe(0.308);
    expect(Bullet::mmToIn(25.4))->toBe(1.0);
});

test('round-trip conversion is stable', function () {
    $originalIn = 0.264;
    $mm = Bullet::inToMm($originalIn);
    $backIn = Bullet::mmToIn($mm);
    expect($backIn)->toBe($originalIn);
});

test('unitsMatch detects matching values', function () {
    expect(Bullet::unitsMatch(0.264, 6.706))->toBeTrue();
    expect(Bullet::unitsMatch(0.308, 7.823))->toBeTrue();
});

test('unitsMatch detects mismatched values', function () {
    expect(Bullet::unitsMatch(0.264, 7.000))->toBeFalse();
    expect(Bullet::unitsMatch(0.308, 6.706))->toBeFalse();
});

test('setDiameterFromIn auto-computes mm', function () {
    $bullet = new Bullet;
    $bullet->setDiameterFromIn(0.264);
    expect((float) $bullet->diameter_in)->toBe(0.264);
    expect((float) $bullet->diameter_mm)->toBe(6.706);
});

test('setDiameterFromMm auto-computes inches', function () {
    $bullet = new Bullet;
    $bullet->setDiameterFromMm(6.706);
    expect((float) $bullet->diameter_mm)->toBe(6.706);
    expect((float) $bullet->diameter_in)->toBe(0.264);
});

test('setLengthFromIn auto-computes mm', function () {
    $bullet = new Bullet;
    $bullet->setLengthFromIn(1.345);
    expect((float) $bullet->length_in)->toBe(1.345);
    expect((float) $bullet->length_mm)->toBe(34.163);
});

test('setLengthFromIn with null clears both', function () {
    $bullet = new Bullet;
    $bullet->length_in = 1.0;
    $bullet->length_mm = 25.4;
    $bullet->setLengthFromIn(null);
    expect($bullet->length_in)->toBeNull();
    expect($bullet->length_mm)->toBeNull();
});

test('caliberDiameters lookup returns correct values', function () {
    $dims = Bullet::diameterForCaliber('6.5mm');
    expect($dims)->toBe(['in' => 0.264, 'mm' => 6.706]);
});

test('caliberDiameters returns null for unknown caliber', function () {
    expect(Bullet::diameterForCaliber('99mm'))->toBeNull();
});
