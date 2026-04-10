@extends('layouts.info')

@section('title', 'NRAPA Information & Guides')
@section('description', 'Official NRAPA guides: dedicated sport shooter and hunter status in South Africa, membership benefits, endorsements, and how dedicated status fits into the Firearms Control Act.')
@section('heading', 'Information & guides')
@section('breadcrumb', 'Info & guides')
@section('short_breadcrumb', '1')

@section('content')
    <p>Use these pages to understand how NRAPA supports lawful firearm owners under the Firearms Control Act. NRAPA is a SAPS-accredited association for dedicated sport shooter and dedicated hunter pathways, serving <strong>members across South Africa</strong>, operated within the <a href="https://ranyati.co.za">Ranyati Group</a> ecosystem.</p>

    {{-- Dedicated status & membership --}}
    <h2>Dedicated status &amp; membership</h2>
    <div class="not-prose grid gap-4 sm:grid-cols-2">
        <a href="{{ route('info.dedicated-sport-shooter-south-africa') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0l-4.725 2.885a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Dedicated sport shooter</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Requirements, benefits, and how to qualify as a dedicated sport shooter in South Africa.</p>
            </div>
        </a>
        <a href="{{ route('info.dedicated-hunter-south-africa') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m20.893 13.393-1.135-1.135a2.252 2.252 0 0 1-.421-.585l-1.08-2.16a.414.414 0 0 0-.663-.107.827.827 0 0 1-.812.21l-1.273-.363a.89.89 0 0 0-.738 1.595l.587.39c.59.395.674 1.23.172 1.732l-.2.2c-.211.212-.33.498-.33.796v.41c0 .409-.11.809-.32 1.158l-1.315 2.191a2.11 2.11 0 0 1-1.81 1.025 1.055 1.055 0 0 1-1.055-1.055v-1.172c0-.92-.56-1.747-1.414-2.089l-.654-.261a2.25 2.25 0 0 1-1.384-2.46l.007-.042a2.25 2.25 0 0 1 .29-.787l.09-.15a2.25 2.25 0 0 1 2.37-1.048l1.178.236a1.125 1.125 0 0 0 1.302-.795l.208-.73a1.125 1.125 0 0 0-.578-1.315l-.665-.332-.091.091a2.25 2.25 0 0 1-1.591.659h-.18c-.249 0-.487.1-.662.274a.931.931 0 0 1-1.458-1.137l1.411-2.353a2.25 2.25 0 0 0 .286-.76m11.928 9.869A9 9 0 0 0 8.965 3.525m11.928 9.868A9 9 0 1 1 8.965 3.525" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Dedicated hunter</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Requirements, benefits, and how to qualify as a dedicated hunter in South Africa.</p>
            </div>
        </a>
        <a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">How to get dedicated status</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Step-by-step guide to obtaining dedicated status through NRAPA.</p>
            </div>
        </a>
        <a href="{{ route('info.dedicated-procedure') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Dedicated procedure (detailed)</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">The full administrative procedure for dedicated status applications.</p>
            </div>
        </a>
        <a href="{{ route('info.membership-benefits') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Membership benefits</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">What you get as an NRAPA member — endorsements, compliance, and more.</p>
            </div>
        </a>
    </div>

    {{-- Endorsements & administration --}}
    <h2>Endorsements &amp; administration</h2>
    <div class="not-prose grid gap-4 sm:grid-cols-2">
        <a href="{{ route('info.endorsements') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Endorsement letters</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">What endorsement letters are, when you need them, and how NRAPA issues them.</p>
            </div>
        </a>
        <a href="{{ route('info.firearm-licence-process') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Firearm licence process</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Overview of the South African firearm licence application process.</p>
            </div>
        </a>
        <a href="{{ route('info.minimum-requirements') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Minimum requirements</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">What you need to meet before applying for dedicated status.</p>
            </div>
        </a>
    </div>

    {{-- Shooting activities --}}
    <h2>Shooting activities</h2>
    <div class="not-prose grid gap-4 sm:grid-cols-2">
        <a href="{{ route('info.shooting-exercises') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Shooting activities &amp; records</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">How to maintain genuine shooting activities and participation records for dedicated status and compliance.</p>
            </div>
        </a>
    </div>

    {{-- General --}}
    <h2>General</h2>
    <div class="not-prose grid gap-4 sm:grid-cols-2">
        <a href="{{ route('info.about') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">About NRAPA</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Who we are, our accreditation, and how NRAPA fits into the Ranyati Group.</p>
            </div>
        </a>
        <a href="{{ route('info.faq') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Frequently asked questions</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Common questions about NRAPA membership, dedicated status, and endorsements.</p>
            </div>
        </a>
    </div>

    {{-- Sister services --}}
    <h2>Sister services</h2>
    <div class="not-prose grid gap-4 sm:grid-cols-2">
        <a href="https://motivations.ranyati.co.za" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 shadow-sm transition hover:border-nrapa-orange/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:border-nrapa-orange/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-orange dark:text-white dark:group-hover:text-nrapa-orange transition">Ranyati Motivations <span class="ml-1 text-zinc-400 dark:text-zinc-500">&rarr;</span></p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Professional firearm licence motivations, renewals, appeals, and compliance support.</p>
            </div>
        </a>
        <a href="https://storage.ranyati.co.za" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 shadow-sm transition hover:border-nrapa-orange/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:border-nrapa-orange/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-orange dark:text-white dark:group-hover:text-nrapa-orange transition">Ranyati Storage <span class="ml-1 text-zinc-400 dark:text-zinc-500">&rarr;</span></p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Secure firearm storage in Pretoria (Ranyati Storage).</p>
            </div>
        </a>
    </div>
@endsection
