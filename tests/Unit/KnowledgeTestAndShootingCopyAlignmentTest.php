<?php

/**
 * Aligns with public copy: NRAPA does not supply targets; knowledge tests use lawful sport shooting (not postal as Q1 answer).
 * Source-level checks avoid duplicating PDF answer keys while keeping repo content consistent.
 */
test('shooting disciplines blade states NRAPA does not supply targets for download', function () {
    $path = dirname(__DIR__, 2).'/resources/views/pages/info/shooting-exercises.blade.php';
    expect(file_exists($path))->toBeTrue();
    $blade = file_get_contents($path);
    expect($blade)->toContain('NRAPA does not offer targets for download');
});

test('welcome page links use shooting disciplines reference label', function () {
    $path = dirname(__DIR__, 2).'/resources/views/welcome.blade.php';
    $blade = file_get_contents($path);
    expect($blade)->toContain('Shooting disciplines (reference)');
});

test('knowledge test questions seeder Q1 uses lawful sport shooting twice and drops postal as correct answer', function () {
    $path = dirname(__DIR__, 2).'/database/seeders/KnowledgeTestQuestionsSeeder.php';
    $src = file_get_contents($path);

    $q1 = 'Complete the sentence: NRAPA promotes active participation in lawful __________ shooting.';
    $options = "['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'sport', 'D' => 'unlicensed']";
    expect(substr_count($src, $q1))->toBe(2)
        ->and(substr_count($src, $options))->toBe(2)
        ->and($src)->not->toContain("'D' => 'Postal'");
});
