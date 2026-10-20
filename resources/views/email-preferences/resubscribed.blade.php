<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Re-subscribed - NRAPA</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/nrapa-icon.png" type="image/png">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 py-10">
        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
            <div class="mb-6 flex justify-center">
                <img src="{{ asset('logo-nrapa-blue-text.png') }}" alt="NRAPA" class="h-14 w-auto">
            </div>

            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-6 w-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-center text-2xl font-bold text-zinc-900">Welcome back</h1>
            <p class="mt-3 text-center text-sm leading-6 text-zinc-600">
                <strong class="font-semibold text-zinc-900">{{ $user->email }}</strong> will receive NRAPA reminders, chase emails, bulk messages, and newsletters again.
            </p>

            <div class="mt-8 border-t border-zinc-100 pt-6 text-center">
                <a href="{{ url('/') }}" class="text-sm text-zinc-500 underline hover:text-zinc-700">Return to NRAPA</a>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-zinc-400">
            &copy; {{ date('Y') }} National Rifle &amp; Pistol Association of South Africa
        </p>
    </main>
</body>
</html>
