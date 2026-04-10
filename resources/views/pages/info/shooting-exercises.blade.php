@extends('layouts.info')

@section('title', 'Shooting Exercises & Targets')
@section('description', 'NRAPA shooting exercises and target specifications for dedicated sport shooters, including rifle, handgun, shotgun, and air rifle disciplines.')
@section('heading', 'Shooting Exercises')
@section('subheading', 'Target specifications and shooting disciplines for dedicated members')
@section('breadcrumb', 'Shooting Exercises')

@section('content')
    <h2>Available Shooting Exercises</h2>
    <p>NRAPA offers a range of shooting exercises across rifle, handgun, shotgun, and air rifle disciplines. Each exercise has specific target requirements and scoring criteria for dedicated sport shooter qualification.</p>

    <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 mt-6 not-prose">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">1. Dedicated Sport Practical Test</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">100m Target (A4 size). The primary practical test for dedicated sport shooter status qualification.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">2. Dedicated Sport Handgun Practical Test</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Target size A4. Handgun-specific practical test for sport shooter qualification.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">3. Air Rifles</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Air rifle shooting exercise for recreational and competitive disciplines.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">4. Rim Fire Rifle</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Bolt Action and Semi Auto Rifles (A3 target). Precision shooting with rimfire calibres.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">5. High Power Rim Fire Rifle</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Bolt Action (A3 target). Extended range rimfire precision shooting.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">6. Rim Fire Gallery Rifle</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Target size A3. Gallery-style rimfire rifle shooting.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">7. Classic Precision Shooting — Rimfire</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Self Loading and Bolt Action Rifles (A3). Traditional precision rifle disciplines.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">8. Classic Precision Shooting — Centerfire</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Self Loading Rifles (A3). Centerfire precision rifle disciplines.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">9. String Precision Shooting</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Rimfire and Centre Fire Self Loading Rifles Only (A3). Timed string shooting exercises.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">10. Sporting Precision Shooting</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Rimfire Self Loading and Bolt Action Rifles (A3). Sporting-style precision rifle events.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">11. Standard Pistol or Revolver Precision Shooting</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Target size A3. Formal pistol/revolver precision discipline.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">12. Sporting Pistol or Revolver Precision Shooting</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Sporting-style handgun precision events.</p>
        </div>
    </div>

    <h2>How to Participate</h2>
    <ol class="step-list">
        <li>
            <strong>Get your targets</strong>
            <p>Contact the NRAPA office at <a href="mailto:info@nrapa.co.za">info@nrapa.co.za</a> or call <a href="tel:+27871510988">087 151 0988</a> for high-resolution targets.</p>
        </li>
        <li>
            <strong>Shoot at a recognised range</strong>
            <p>Complete targets at any recognised shooting range.</p>
        </li>
        <li>
            <strong>Submit electronically</strong>
            <p>Email completed targets to the NRAPA office. All targets shot on one day constitute one activity.</p>
        </li>
    </ol>

    <div class="info-card">
        <h4>Note</h4>
        <p>Shooting on farm ranges is only accepted with written Exco approval.</p>
    </div>

    <h2>Scoring &amp; Submission</h2>
    <ul class="checklist">
        <li>Most exercises require a minimum <strong>60% score</strong> for dedicated status qualification</li>
        <li>Targets must be accompanied by range receipt or written declaration from range officer</li>
        <li>Electronically submitted scorecards build participation records for licence renewal applications</li>
    </ul>
@endsection
