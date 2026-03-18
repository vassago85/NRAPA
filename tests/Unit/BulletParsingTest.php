<?php

use App\Services\Bullets\Parsers\BarnesParser;
use App\Services\Bullets\Parsers\HornadyParser;

test('Barnes parser extracts weight from title', function () {
    $parser = new BarnesParser;
    $result = $parser->parseFromTitle('0.308" 30 CAL LRX 212 GR BT Bore Rider', 'https://barnesbullets.com/test');

    expect($result)->not->toBeNull();
    expect($result['weight_gr'])->toBe(212);
});

test('Barnes parser extracts caliber from title', function () {
    $parser = new BarnesParser;
    $result = $parser->parseFromTitle('0.264" 6.5 GRENDEL TAC-TX 115 GR BT', 'https://barnesbullets.com/test');

    expect($result)->not->toBeNull();
    expect($result['caliber_label'])->toBe('6.5mm');
});

test('Barnes parser extracts diameter from title', function () {
    $parser = new BarnesParser;
    $result = $parser->parseFromTitle('0.308" 30 CAL TTSX 110 GR FB', 'https://barnesbullets.com/test');

    expect($result)->not->toBeNull();
    expect($result['diameter_in'])->toBe(0.308);
});

test('Barnes parser maps brand line to construction', function () {
    $parser = new BarnesParser;

    $lrx = $parser->parseFromTitle('0.308" 30 CAL LRX 212 GR BT', 'https://test.com');
    expect($lrx['construction'])->toBe('monolithic_copper');
    expect($lrx['intended_use'])->toBe('hunting');

    $mb = $parser->parseFromTitle('0.264" 6.5MM Match Burner BT 140 GR', 'https://test.com');
    expect($mb['construction'])->toBe('cup_and_core');
    expect($mb['intended_use'])->toBe('match');
});

test('Barnes parser detects twist note from HTML', function () {
    $parser = new BarnesParser;

    $html = 'specs: Requires 1:8 twist or faster for optimal performance';
    expect($parser->detectTwistNote($html))->toBe('Requires 1:8 twist or faster');

    $html = 'no twist info here';
    expect($parser->detectTwistNote($html))->toBeNull();
});

test('Hornady parser builds BC row correctly', function () {
    $parser = new HornadyParser;

    $data = $parser->buildFromBcRow('6.5mm', 140, 'ELD Match', 0.646, 0.326);

    expect($data['manufacturer'])->toBe('Hornady');
    expect($data['brand_line'])->toBe('ELD Match');
    expect($data['caliber_label'])->toBe('6.5mm');
    expect($data['weight_gr'])->toBe(140);
    expect($data['bc_g1'])->toBe(0.646);
    expect($data['bc_g7'])->toBe(0.326);
    expect($data['bc_reference'])->toBe('Mach 2.25');
    expect($data['diameter_in'])->toBe(0.264);
    expect($data['diameter_mm'])->toBe(6.706);
    expect($data['construction'])->toBe('cup_and_core');
    expect($data['intended_use'])->toBe('match');
    expect($data['bullet_label'])->toBe('6.5mm 140 gr ELD Match');
});

test('Hornady parser maps lines correctly', function () {
    $parser = new HornadyParser;

    expect($parser->mapLine('ELD Match'))->toBe(['use' => 'match', 'construction' => 'cup_and_core']);
    expect($parser->mapLine('ELD-X'))->toBe(['use' => 'hunting', 'construction' => 'cup_and_core']);
    expect($parser->mapLine('CX'))->toBe(['use' => 'hunting', 'construction' => 'monolithic_copper']);
    expect($parser->mapLine('FMJ'))->toBe(['use' => 'fmj', 'construction' => 'fmj']);
    expect($parser->mapLine('UnknownLine'))->toBe(['use' => 'other', 'construction' => 'other']);
});
