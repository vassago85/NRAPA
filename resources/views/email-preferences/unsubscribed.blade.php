<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed - NRAPA</title>
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

            @if($alreadyUnsubscribed)
                <h1 class="text-center text-2xl font-bold text-zinc-900">You're already unsubscribed</h1>
                <p class="mt-3 text-center text-sm leading-6 text-zinc-600">
                    {{ $user->email ?? 'This address' }} is already opted out of NRAPA reminders, chase emails, and bulk messages. No action needed.
                </p>
            @else
                <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-6 w-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="text-center text-2xl font-bold text-zinc-900">You've been unsubscribed</h1>
                <p class="mt-3 text-center text-sm leading-6 text-zinc-600">
                    @if($user)
                        <strong class="font-semibold text-zinc-900">{{ $user->email }}</strong> will no longer receive reminders, chase emails, bulk messages, or newsletters from NRAPA.
                    @else
                        That email address will no longer receive reminders, chase emails, bulk messages, or newsletters from NRAPA.
                    @endif
                </p>
            @endif

            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">You will still receive essential account mail.</p>
                <p class="mt-1 text-amber-800">
                    NRAPA will keep sending transactional notices — payment confirmations, approval decisions, membership status changes, password resets, and documents affecting your membership. These cannot be unsubscribed from without closing your account.
                </p>
            </div>

            @if($resubscribeUrl)
                <div class="mt-6 flex flex-col items-center gap-3">
                    <p class="text-xs text-zinc-500">Changed your mind?</p>
                    <a href="{{ $resubscribeUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-subscribe
                    </a>
                </div>
            @endif

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
