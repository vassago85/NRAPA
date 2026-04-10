@extends('layouts.info')

@section('title', 'Shooting Activities & Participation Records')
@section('description', 'Learn how structured shooting activities and participation records support dedicated status, endorsements, and firearm compliance in South Africa through NRAPA.')
@section('heading', 'Shooting Activities & Participation Records')
@section('subheading', 'Maintain genuine activity records that support dedicated status and firearm compliance in South Africa')
@section('breadcrumb', 'Shooting activities')

@section('content')
    <div class="not-prose mb-8 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <img
            src="{{ asset('learning_images/shooting-activities-participation-records-hero.svg') }}"
            alt="Responsible firearm owner at a South African shooting range using a logbook and tablet to maintain participation records."
            class="h-auto w-full object-cover"
            loading="eager"
        >
    </div>

    <div class="info-card">
        <h4>Structured participation, properly recorded</h4>
        <p>Shooting activities form part of a structured participation record for members who hold, apply for, or maintain dedicated status. Clear records support administrative consistency across endorsements, annual participation evidence, and related compliance processes.</p>
        <p>As an accredited association, NRAPA provides a practical system to keep participation records organised, credible, and ready when required.</p>
    </div>

    <h2>What counts as shooting activities</h2>
    <p>Shooting activities should be genuine, relevant, and capable of being recorded with supporting detail. Depending on your context, this may include range practice, club shoots, organised participation events, hunting-related participation where relevant, and other lawful firearm-related activities that can be documented properly.</p>

    <h2>Why record-keeping matters</h2>
    <div class="info-card">
        <h4>Participation records are part of compliance readiness</h4>
        <ul class="checklist">
            <li>Supports dedicated status applications and annual participation evidence</li>
            <li>Strengthens endorsement requests with traceable activity history</li>
            <li>Provides supporting documentation for licence-related admin where relevant</li>
            <li>Reduces last-minute pressure by keeping records current throughout the year</li>
        </ul>
    </div>

    <h2>NRAPA activity logging</h2>
    <p>NRAPA provides a structured activity-logging workflow for members. The system is designed to keep participation history centralised and administratively useful.</p>
    <div class="not-prose mt-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Log each activity</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Capture date, context, and relevant details for each participation entry.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Attach supporting evidence</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Add scorecards, receipts, declarations, or other evidence where the process requires it.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Maintain central history</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Keep your activity trail in one place for easier administrative use.</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-1 text-base font-semibold text-zinc-900 dark:text-white">Stay organised year-round</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Consistent updates improve record quality and reduce compliance friction.</p>
        </div>
    </div>

    <h2>Good practice for members</h2>
    <ol class="step-list">
        <li>
            <strong>Keep records up to date</strong>
            <p>Log activities soon after participation while details are still accurate.</p>
        </li>
        <li>
            <strong>Participate consistently</strong>
            <p>Spread participation across the year instead of relying on late catch-up submissions.</p>
        </li>
        <li>
            <strong>Ensure entries are genuine and relevant</strong>
            <p>Record only legitimate activities with information that supports administrative review.</p>
        </li>
        <li>
            <strong>Do not leave logging too late</strong>
            <p>Late bulk entries are harder to verify and often create avoidable delays.</p>
        </li>
    </ol>

    <h2>Supporting your applications</h2>
    <p>A well-maintained participation record can strengthen dedicated status processes, endorsement submissions, and licence-related supporting documentation where applicable. It also helps keep your member profile compliance-ready when evidence is requested.</p>

    <div class="not-prose mt-8 rounded-2xl border border-nrapa-blue/20 bg-gradient-to-r from-nrapa-blue/10 to-nrapa-orange/10 p-6 dark:border-nrapa-blue/40 dark:from-nrapa-blue/20 dark:to-nrapa-orange/20">
        <h2 class="m-0 text-xl font-bold text-zinc-900 dark:text-white">Next steps</h2>
        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-200">Use the key guides below to move from participation logging to complete dedicated-status administration.</p>
        <ul class="link-list mt-4">
            <li><a href="{{ route('home') }}">NRAPA homepage</a></li>
            <li><a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure</a></li>
            <li><a href="{{ route('info.minimum-requirements') }}">Minimum requirements</a></li>
            <li><a href="{{ route('info.endorsements') }}">Endorsements</a></li>
            <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status in South Africa</a></li>
        </ul>
    </div>
@endsection
