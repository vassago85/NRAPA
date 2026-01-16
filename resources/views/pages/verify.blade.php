@php
use App\Models\Certificate;

$certificate = Certificate::where('qr_code', $qr_code)
    ->with(['user', 'certificateType', 'membership.type'])
    ->first();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Verification - NRAPA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 dark:bg-zinc-900">
    <div class="mx-auto max-w-xl px-4 py-12">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-600 text-white">
                <x-app-logo-icon class="size-8" />
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">NRAPA Certificate Verification</h1>
        </div>

        @if($certificate)
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg dark:bg-zinc-800">
                {{-- Status Banner --}}
                @if($certificate->isValid())
                    <div class="bg-emerald-500 px-6 py-4 text-center text-white">
                        <svg class="mx-auto mb-2 size-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xl font-bold">Certificate Valid</p>
                    </div>
                @elseif($certificate->isRevoked())
                    <div class="bg-red-500 px-6 py-4 text-center text-white">
                        <svg class="mx-auto mb-2 size-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xl font-bold">Certificate Revoked</p>
                    </div>
                @else
                    <div class="bg-amber-500 px-6 py-4 text-center text-white">
                        <svg class="mx-auto mb-2 size-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xl font-bold">Certificate Expired</p>
                    </div>
                @endif

                {{-- Certificate Details --}}
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Certificate Type</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $certificate->certificateType->name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Member Name</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $certificate->user->name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Certificate Number</dt>
                            <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $certificate->certificate_number }}</dd>
                        </div>
                        @if($certificate->membership)
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Membership Type</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $certificate->membership->type->name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Member Number</dt>
                            <dd class="font-mono font-medium text-zinc-900 dark:text-white">{{ $certificate->membership->membership_number }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Issue Date</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $certificate->issued_at->format('d F Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Valid Until</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                @if($certificate->valid_until)
                                    {{ $certificate->valid_until->format('d F Y') }}
                                @else
                                    Indefinite
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Footer --}}
                <div class="border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                        Verified on {{ now()->format('d F Y \a\t H:i') }}
                    </p>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg dark:bg-zinc-800">
                <div class="bg-red-500 px-6 py-4 text-center text-white">
                    <svg class="mx-auto mb-2 size-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xl font-bold">Certificate Not Found</p>
                </div>
                <div class="p-6 text-center">
                    <p class="text-zinc-600 dark:text-zinc-400">
                        The certificate you are trying to verify could not be found.
                        Please ensure you have scanned the correct QR code.
                    </p>
                </div>
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-zinc-500">
            &copy; {{ date('Y') }} National Rifle and Pistol Association. All rights reserved.
        </p>
    </div>
</body>
</html>
