@extends('layouts.guides')

@section('title', 'NRAPA Site Guides')
@section('description', 'Step-by-step NRAPA site guides: how to sign up, upload your proof of payment, submit shooting activities, and request an endorsement letter.')
@section('heading', 'How-to guides for the NRAPA site')

@section('content')
    <p>Short, step-by-step guides for using the NRAPA member site. Start with signing up, then learn how to handle your payment, log your shooting activities, and request endorsement letters.</p>

    <div class="not-prose mt-8 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('guides.sign-up') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-orange/10 text-nrapa-orange">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">How to sign up</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create your NRAPA account and verify your email address.</p>
                <span class="mt-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Open to everyone</span>
            </div>
        </a>

        <a href="{{ route('guides.upload-proof-of-payment') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18V8.25m-18 0V6a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 6v2.25m-18 0h18M12 14.25l3-3m0 0-3-3m3 3H9" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Upload proof of payment</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upload your POP so your membership can be activated.</p>
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Members only</span>
            </div>
        </a>

        <a href="{{ route('guides.submit-activities') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Submit activities</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Log a hunting or sport-shooting activity for your dedicated status.</p>
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Members only</span>
            </div>
        </a>

        <a href="{{ route('guides.request-endorsement') }}" class="group flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-nrapa-blue/30 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-nrapa-blue/50">
            <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-nrapa-blue/10 text-nrapa-blue">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0l-4.725 2.885a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
            </span>
            <div>
                <p class="font-semibold text-zinc-900 group-hover:text-nrapa-blue dark:text-white dark:group-hover:text-nrapa-blue transition">Request an endorsement</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Submit an endorsement request for your Section 16 firearm licence applications.</p>
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Members only</span>
            </div>
        </a>
    </div>

    <p class="mt-8 text-sm text-zinc-500 dark:text-zinc-400">Guides marked <strong>Members only</strong> require you to be logged in. If you're not signed in yet, you'll be asked to log in first.</p>
@endsection
