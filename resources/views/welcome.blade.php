<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NRAPA - Members Portal</title>
        <link rel="icon" href="/nrapa-icon.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .hero-gradient {
                background: linear-gradient(135deg, #061e3c 0%, #0B4EA2 50%, #083A7A 100%);
            }
            .hero-pattern {
                background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
                background-size: 40px 40px;
            }
            .hero-glow {
                background: radial-gradient(ellipse at 50% 0%, rgba(245,130,32,0.15) 0%, transparent 60%);
            }
            .card-hover {
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            .card-hover:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 40px -12px rgba(11,78,162,0.15);
            }
            .pricing-hover {
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            .pricing-hover:hover {
                transform: translateY(-6px);
                box-shadow: 0 20px 50px -16px rgba(11,78,162,0.2);
            }
            .btn-glow {
                box-shadow: 0 0 20px rgba(245,130,32,0.3);
            }
            .btn-glow:hover {
                box-shadow: 0 0 30px rgba(245,130,32,0.5);
            }
            @keyframes fade-up {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-up {
                animation: fade-up 0.6s ease-out forwards;
            }
            .animate-fade-up-delay {
                animation: fade-up 0.6s ease-out 0.15s forwards;
                opacity: 0;
            }
            .animate-fade-up-delay-2 {
                animation: fade-up 0.6s ease-out 0.3s forwards;
                opacity: 0;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">

        {{-- Header --}}
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#061e3c]/80 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8" aria-label="Global">
                <a href="https://ranyati.co.za" class="flex items-center group" target="_blank" rel="noopener">
                    <img src="{{ asset('logo-ranyatigroup-horizontal-white.png') }}" alt="Ranyati Group" class="h-7 w-auto object-contain transition group-hover:opacity-80" />
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-zinc-300 hover:text-white transition">Features</a>
                    <a href="#pricing" class="text-sm font-medium text-zinc-300 hover:text-white transition">Packages</a>
                </div>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-300 hover:text-white transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-nrapa-orange px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-nrapa-orange-dark transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        {{-- Hero --}}
        <section class="hero-gradient relative overflow-hidden pt-32 pb-24 sm:pt-40 sm:pb-32">
            <div class="hero-pattern absolute inset-0"></div>
            <div class="hero-glow absolute inset-0"></div>
            <div class="relative mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <div class="animate-fade-up">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 backdrop-blur-sm">
                        <span class="size-2 rounded-full bg-nrapa-orange animate-pulse"></span>
                        <span class="text-xs font-medium text-zinc-300">SAPS Accredited &mdash; FAR 1300122 &amp; 1300127</span>
                    </div>
                </div>

                <div class="mt-8 animate-fade-up-delay">
                    <div class="mx-auto inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-10 py-5 backdrop-blur-sm">
                        <img src="{{ asset('logo-nrapa-white.png') }}" alt="NRAPA" class="h-16 sm:h-20 w-auto object-contain" />
                    </div>
                </div>

                <h1 class="mt-8 text-3xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl animate-fade-up-delay" style="text-wrap: balance">
                    Your Partner in<br>
                    <span class="text-nrapa-orange">Responsible Firearm Ownership</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-300 animate-fade-up-delay-2">
                    We help you obtain dedicated status, manage your licences, and stay compliant with the Firearms Control Act &mdash; so you can focus on what you love, whether it's on the range or in the bush.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-up-delay-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-nrapa-orange px-8 py-3.5 text-sm font-bold text-white shadow-lg btn-glow hover:bg-nrapa-orange-dark transition-all">
                            Go to Dashboard
                        </a>
                        <a href="#features" class="text-sm font-semibold text-zinc-300 hover:text-white transition">
                            View features <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-xl bg-nrapa-orange px-8 py-3.5 text-sm font-bold text-white shadow-lg btn-glow hover:bg-nrapa-orange-dark transition-all">
                            Become a Member
                        </a>
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/20 bg-white/5 px-8 py-3.5 text-sm font-semibold text-white backdrop-blur-sm hover:bg-white/10 transition-all">
                            Already a member? Sign in
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Bottom curve --}}
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                    <path d="M0 60L1440 60L1440 0C1440 0 1080 40 720 40C360 40 0 0 0 0L0 60Z" fill="white"/>
                </svg>
            </div>
        </section>

        {{-- Credentials Bar --}}
        <section class="relative -mt-1 bg-white py-12">
            <div class="mx-auto max-w-5xl px-6">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-nrapa-blue">SAPS</p>
                        <p class="mt-1 text-sm text-zinc-500">Accredited</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-nrapa-blue">1300122</p>
                        <p class="mt-1 text-sm text-zinc-500">FAR Sport</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-nrapa-blue">1300127</p>
                        <p class="mt-1 text-sm text-zinc-500">FAR Hunting</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-nrapa-blue">POPIA</p>
                        <p class="mt-1 text-sm text-zinc-500">Compliant</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="bg-zinc-50 py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <span class="inline-block rounded-full bg-nrapa-blue/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-nrapa-blue">
                        Members Portal
                    </span>
                    <p class="mt-4 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl" style="text-wrap: balance">
                        Manage your membership online
                    </p>
                    <p class="mx-auto mt-4 max-w-xl text-base text-zinc-500">
                        Whether you're building your activity record or already hold dedicated status &mdash; we're with you every step of the way. Here's what you get as a member.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- 1. Endorsements & Dedicated Status --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-blue/10 group-hover:bg-nrapa-blue/15 transition">
                            <svg class="size-6 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Endorsements &amp; Dedicated Licences</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">We assist you in obtaining Dedicated Sport and Hunter status &mdash; allowing you to hold additional firearms and increased ammunition beyond the standard limits.</p>
                    </div>

                    {{-- 2. Certificates --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-blue/10 group-hover:bg-nrapa-blue/15 transition">
                            <svg class="size-6 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h6M9 13h6M9 17h6"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Certificates &amp; Endorsements</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Download your membership certificate, endorsement letters, and competency documents. All QR-verifiable for SAPS and DFO submissions.</p>
                    </div>

                    {{-- 3. Virtual Safe --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-blue/10 group-hover:bg-nrapa-blue/15 transition">
                            <svg class="size-6 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Virtual Safe</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Keep a digital record of your firearms, license details, and barrel life. Get reminders before your licenses expire.</p>
                    </div>

                    {{-- 4. Loading Bench --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-orange/10 group-hover:bg-nrapa-orange/15 transition">
                            <svg class="size-6 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8l-1 1v6l4 6a2 2 0 01-1.7 3H6.7A2 2 0 015 17l4-6V5L8 4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Virtual Loading Bench</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Store load recipes, track component inventory, run ladder tests, and calculate cost per round.</p>
                    </div>

                    {{-- 5. Activities --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-orange/10 group-hover:bg-nrapa-orange/15 transition">
                            <svg class="size-6 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M9 17h6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 6l1 1 2-2M5 10l1 1 2-2M5 14l1 1 2-2M5 18l1 1 2-2"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Shooting Activities</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Log your range sessions and hunting activities. Track rounds fired and build the activity record required for dedicated status.</p>
                    </div>

                    {{-- 6. Digital Membership Card --}}
                    <div class="card-hover group rounded-2xl border border-zinc-200/80 bg-white p-7">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-nrapa-orange/10 group-hover:bg-nrapa-orange/15 transition">
                            <svg class="size-6 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9A2.5 2.5 0 0118.5 19h-13A2.5 2.5 0 013 16.5v-9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M7 15h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-zinc-900">Digital Membership Card</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">QR-verifiable membership card on your phone. Present it at the range, to SAPS, or any official who needs to confirm your membership.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Membership Packages --}}
        @php
            $membershipTypes = \App\Models\MembershipType::where('display_on_landing', true)
                ->where('is_active', true)
                ->ordered()
                ->get();
            $basicType = $membershipTypes->firstWhere('slug', 'basic');
        @endphp
        <section id="pricing" class="bg-white py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <span class="inline-block rounded-full bg-nrapa-orange/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-nrapa-orange">
                        Membership
                    </span>
                    <p class="mt-4 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl" style="text-wrap: balance">
                        Become a member
                    </p>
                    <p class="mx-auto mt-4 max-w-xl text-base text-zinc-500">
                        Choose the membership that fits your needs. All packages include full portal access and digital certificates.
                    </p>
                </div>

                @if($membershipTypes->count() > 0)
                <div class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="pricing-hover relative rounded-2xl {{ $type->is_featured ? 'border-2 border-nrapa-blue bg-white shadow-lg ring-1 ring-nrapa-blue/10' : 'border border-zinc-200 bg-white' }} p-8">
                        @if($type->is_featured)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-nrapa-orange to-nrapa-orange-dark px-4 py-1 text-xs font-bold text-white shadow-sm">
                            Most Popular
                        </div>
                        @endif

                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex size-11 items-center justify-center rounded-xl {{ $type->is_featured ? 'bg-nrapa-blue/10' : 'bg-zinc-100' }}">
                                @if($type->dedicated_type === 'both')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @elseif($type->dedicated_type === 'hunter')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                @elseif($type->dedicated_type === 'sport')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" />
                                </svg>
                                @else
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                @endif
                            </div>
                            @if($type->dedicated_type)
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $type->dedicated_type === 'both' ? 'bg-purple-50 text-purple-700' : ($type->dedicated_type === 'hunter' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                                {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type) }}
                            </span>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-zinc-900">{{ $type->name }}</h3>

                        <div class="mt-3">
                            @if($type->hasUpgradeFee() && $basicType)
                            @php $totalSignup = ($basicType->initial_price ?? 0) + ($type->upgrade_price ?? 0); @endphp
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold tracking-tight text-zinc-900">R{{ number_format($totalSignup, 0) }}</span>
                                <span class="text-sm font-medium text-zinc-400">sign-up</span>
                            </div>
                            <p class="text-sm text-zinc-500 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                            @else
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold tracking-tight text-zinc-900">R{{ number_format($type->initial_price, 0) }}</span>
                                <span class="text-sm font-medium text-zinc-400">/ {{ $type->duration_type === 'lifetime' ? 'once-off' : 'year' }}</span>
                            </div>
                            @if($type->renewal_price > 0 && $type->renewal_price != $type->initial_price)
                            <p class="text-sm text-zinc-500 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                            @endif
                            @endif
                        </div>

                        <p class="mt-4 text-sm leading-relaxed text-zinc-500">{{ $type->description }}</p>

                        <div class="my-6 h-px bg-zinc-100"></div>

                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Virtual Safe
                            </li>
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Virtual Loading Bench
                            </li>
                            @endif
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                QR-Verified Certificates
                            </li>
                            @if($type->requires_knowledge_test)
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Knowledge Test Access
                            </li>
                            @endif
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Dedicated Status Support
                            </li>
                            @endif
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Learning Center Access
                            </li>
                        </ul>

                        <a href="{{ route('register') }}" class="mt-8 block w-full text-center rounded-xl {{ $type->is_featured ? 'bg-nrapa-blue text-white hover:bg-nrapa-blue-dark shadow-sm' : 'border border-zinc-200 text-zinc-700 hover:border-nrapa-blue hover:text-nrapa-blue' }} px-4 py-3 text-sm font-bold transition-all">
                            Get Started
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="mt-16 text-center">
                    <p class="text-zinc-500">Membership packages will be listed here shortly.</p>
                    <a href="{{ route('register') }}" class="mt-4 inline-block rounded-xl bg-nrapa-blue px-8 py-3.5 text-sm font-bold text-white hover:bg-nrapa-blue-dark transition shadow-sm">
                        Register Now
                    </a>
                </div>
                @endif
            </div>
        </section>

        {{-- CTA Banner --}}
        <section class="hero-gradient relative overflow-hidden">
            <div class="hero-pattern absolute inset-0"></div>
            <div class="relative mx-auto max-w-4xl px-6 py-20 text-center">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl" style="text-wrap: balance">
                    We're in your corner
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-zinc-300">
                    Whether you're working toward dedicated status or already there &mdash; NRAPA walks the journey with you. Endorsements, certificates, compliance, and the support you need to enjoy responsible firearm ownership.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="rounded-xl bg-nrapa-orange px-8 py-3.5 text-sm font-bold text-white shadow-lg btn-glow hover:bg-nrapa-orange-dark transition-all">
                        Apply for Membership
                    </a>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-[#020810] border-t border-white/[0.04]">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-12 py-14 sm:grid-cols-3 sm:gap-8 sm:py-16">
                    {{-- Left: Ranyati Group --}}
                    <div class="text-left">
                        <img src="{{ asset('logo-ranyatigroup-white_text.png') }}" alt="Ranyati Group" class="h-8 w-auto" />
                        <p class="mt-5 text-[13px] leading-[1.7] text-white/30">
                            Specialist firearm administration services since 2006.<br>
                            Trading as Ranyati Firearm Motivations (Pty) Ltd.
                        </p>
                    </div>

                    {{-- Center: Divisions --}}
                    <div class="flex flex-col items-center text-center">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Divisions</h4>
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 20px; align-items: center;">
                            <a href="https://motivations.ranyati.co.za" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(245,130,32,0.1); box-shadow: inset 0 0 0 1px rgba(245,130,32,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(245,130,32,0.2)'" onmouseout="this.style.background='rgba(245,130,32,0.1)'">
                                <img src="{{ asset('logo-ranyati_motivations-white-text.png') }}" alt="Motivations" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain;" />
                            </a>
                            <a href="https://nrapa.ranyati.co.za" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(56,189,248,0.1); box-shadow: inset 0 0 0 1px rgba(56,189,248,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(56,189,248,0.2)'" onmouseout="this.style.background='rgba(56,189,248,0.1)'">
                                <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain;" />
                            </a>
                            <a href="https://storage.ranyati.co.za" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(52,211,153,0.1); box-shadow: inset 0 0 0 1px rgba(52,211,153,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(52,211,153,0.2)'" onmouseout="this.style.background='rgba(52,211,153,0.1)'">
                                <img src="{{ asset('logo-ranyati_storage-white_text.png') }}" alt="Storage" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain;" />
                            </a>
                        </div>
                    </div>

                    {{-- Right: Contact --}}
                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Contact</h4>
                        <div style="margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; gap: 0;">
                            <a href="tel:+27871510987" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.2);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                +27 87 151 0987
                            </a>
                            <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0;"></div>
                            <a href="mailto:info@nrapa.co.za" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.2);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                info@nrapa.co.za
                            </a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-white/[0.04] py-6">
                    <p class="text-center text-[10px] tracking-[0.1em] text-white/15">
                        &copy; {{ date('Y') }} Ranyati Firearm Motivations (Pty) Ltd. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
