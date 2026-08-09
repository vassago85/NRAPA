<?php

/**
 * Aligns with public copy: the shooting-activities page frames participation as a
 * record for dedicated status (it does not offer downloadable targets), and knowledge
 * tests use lawful sport shooting (not postal as Q1 answer).
 * Source-level checks avoid duplicating PDF answer keys while keeping repo content consistent.
 */
test('shooting activities blade frames participation records and does not offer targets for download', function () {
    $path = dirname(__DIR__, 2).'/resources/views/pages/info/shooting-exercises.blade.php';
    expect(file_exists($path))->toBeTrue();
    $blade = file_get_contents($path);
    expect($blade)->toContain('Shooting Activity Record for Dedicated Status')
        ->and($blade)->not->toContain('targets for download');
});

test('welcome page links to the shooting activities reference', function () {
    $path = dirname(__DIR__, 2).'/resources/views/welcome.blade.php';
    $blade = file_get_contents($path);
    expect($blade)->toContain('Shooting activities &amp; participation records');
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
