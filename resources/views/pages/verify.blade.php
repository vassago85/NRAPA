<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Verification — NRAPA</title>
    <meta name="description" content="Verify the authenticity of NRAPA certificates and documents using QR code verification.">
    <meta property="og:title" content="NRAPA Certificate Verification">
    <meta property="og:description" content="Verify the authenticity of NRAPA certificates and documents.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://nrapa.ranyati.co.za/verify">
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebPage",
        "name": "NRAPA Certificate Verification",
        "description": "Verify the authenticity of NRAPA membership certificates and endorsement documents.",
        "url": "https://nrapa.ranyati.co.za/verify",
        "isPartOf": {
            "@type": "WebSite",
            "name": "NRAPA",
            "url": "https://nrapa.ranyati.co.za"
        }
    }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-white antialiased">
    <div class="mx-auto max-w-2xl px-4 py-12">
        <div class="mb-8 text-center">
            <img src="{{ asset('logo-nrapa-white.png') }}" alt="NRAPA" width="200" height="74" class="mx-auto mb-5 h-[74px] w-auto max-w-[min(100%,220px)] object-contain object-center" />
            <h1 class="text-3xl font-bold text-white">NRAPA Certificate Verification</h1>
            <p class="mt-2 text-zinc-400">Verify the authenticity of NRAPA certificates and documents</p>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-8 shadow-xl">
            @if($result['valid'])
                {{-- Valid Certificate --}}
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full bg-nrapa-blue/20">
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
                        <div class="rounded-lg border border-zinc-700/80 bg-zinc-800/40 divide-y divide-zinc-700/80">
                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[minmax(0,11rem)_1fr] sm:items-baseline sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-400">Name</dt>
                                <dd class="text-base font-semibold text-white">{{ $result['member_info']['display_name'] }}</dd>
                            </div>
                            @if(!empty($result['member_info']['id_masked']))
                                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[minmax(0,11rem)_1fr] sm:items-baseline sm:gap-4">
                                    <dt class="text-sm font-medium text-zinc-400">ID Number</dt>
                                    <dd class="text-base font-semibold text-white">{{ $result['member_info']['id_masked'] }}</dd>
                                </div>
                            @endif
                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[minmax(0,11rem)_1fr] sm:items-baseline sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-400">Membership Number</dt>
                                <dd class="text-base font-semibold text-white">{{ $result['member_info']['membership_number'] }}</dd>
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
                    <a href="{{ route('home') }}" class="text-emerald-400 hover:text-emerald-300">nrapa.ranyati.co.za</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
