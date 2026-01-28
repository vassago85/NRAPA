<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Verification - NRAPA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-white antialiased">
    <div class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-600">
                <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">NRAPA Certificate Verification</h1>
            <p class="mt-2 text-zinc-400">Verify the authenticity of NRAPA certificates and documents</p>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-8 shadow-xl">
            @if($result['valid'])
                {{-- Valid Certificate --}}
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-600/20">
                        <svg class="size-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-emerald-400">Valid Certificate</h2>
                        <p class="text-sm text-zinc-400">This certificate is authentic and valid</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-400">Document Type</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">{{ $result['document_type'] }}</dd>
                    </div>

                    @if($result['member_info'])
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-zinc-400">Member Name</dt>
                                <dd class="mt-1 font-semibold text-white">
                                    {{ $result['member_info']['initials'] }}. {{ $result['member_info']['surname'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-zinc-400">Membership Number</dt>
                                <dd class="mt-1 font-semibold text-white">{{ $result['member_info']['membership_number'] }}</dd>
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        @if($result['issued_date'])
                            <div>
                                <dt class="text-sm font-medium text-zinc-400">Issued Date</dt>
                                <dd class="mt-1 font-semibold text-white">{{ $result['issued_date'] }}</dd>
                            </div>
                        @endif
                        @if($result['valid_until'])
                            <div>
                                <dt class="text-sm font-medium text-zinc-400">Valid Until</dt>
                                <dd class="mt-1 font-semibold text-white">{{ $result['valid_until'] }}</dd>
                            </div>
                        @else
                            <div>
                                <dt class="text-sm font-medium text-zinc-400">Validity</dt>
                                <dd class="mt-1 font-semibold text-white">Indefinite (subject to membership status)</dd>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Invalid Certificate --}}
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full bg-red-600/20">
                        <svg class="size-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-red-400">Invalid Certificate</h2>
                        <p class="text-sm text-zinc-400">{{ $result['reason'] ?? 'This certificate cannot be verified' }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-red-800/50 bg-red-900/20 p-4">
                    <p class="text-sm text-red-300">
                        This certificate may have been revoked, expired, or the member's status may have changed.
                        Please contact NRAPA for more information.
                    </p>
                </div>
            @endif

            <div class="mt-8 border-t border-zinc-800 pt-6">
                <p class="text-center text-sm text-zinc-500">
                    National Rifle & Pistol Association of South Africa<br>
                    <a href="{{ route('home') }}" class="text-emerald-400 hover:text-emerald-300">www.nrapa.co.za</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
