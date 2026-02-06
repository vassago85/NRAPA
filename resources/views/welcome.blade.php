<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NRAPA - Members Portal</title>
        <link rel="icon" href="/nrapa-logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-logo.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">

        {{-- Header --}}
        <header class="sticky inset-x-0 top-0 z-50 border-b border-zinc-200 bg-white/90 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4" aria-label="Global">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-9 object-contain" />
                    <span class="text-lg font-bold tracking-tight text-zinc-900">NRAPA</span>
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-zinc-600 hover:text-nrapa-blue transition">Features</a>
                    <a href="#pricing" class="text-sm font-medium text-zinc-600 hover:text-nrapa-blue transition">Packages</a>
                </div>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-nrapa-blue hover:text-nrapa-blue-dark transition">
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-600 hover:text-nrapa-blue transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-nrapa-blue-dark transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        {{-- Hero --}}
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-6xl px-6 text-center">
                <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="mx-auto size-20 object-contain" />

                <h1 class="mt-8 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl" style="text-wrap: balance">
                    National Rifle &amp; Pistol Association
                </h1>
                <p class="mt-2 text-xl font-medium text-nrapa-blue">Members Portal</p>

                <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-zinc-600">
                    Digital membership card, certificates, virtual safe, loading bench, shooting activities, and endorsements &mdash; all in one secure place.
                </p>

                <div class="mt-10 flex items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-nrapa-blue px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-nrapa-blue-dark transition">
                            Go to Dashboard
                        </a>
                        <a href="#features" class="text-sm font-semibold text-nrapa-blue hover:text-nrapa-blue-dark transition">
                            View features <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-lg bg-nrapa-orange px-6 py-3 text-sm font-semibold text-zinc-900 shadow-sm hover:bg-nrapa-orange-dark transition">
                            Become a Member
                        </a>
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-nrapa-blue hover:text-nrapa-blue-dark transition">
                            Already a member? <span aria-hidden="true">&rarr;</span>
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="border-t border-zinc-100 bg-zinc-50 py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <h2 class="text-sm font-semibold tracking-wide uppercase text-nrapa-blue">What you get</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-zinc-900" style="text-wrap: balance">
                        Everything a member needs
                    </p>
                </div>

                <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- 1. Digital Card --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue-light">
                            <svg class="size-5 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9A2.5 2.5 0 0118.5 19h-13A2.5 2.5 0 013 16.5v-9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M7 15h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Digital Membership Card</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">QR-verifiable virtual card, always accessible from your phone.</p>
                    </div>

                    {{-- 2. Certificates --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue-light">
                            <svg class="size-5 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h6M9 13h6M9 17h6"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Certificates &amp; Endorsements</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Download, verify, and track your issued certificates and endorsement letters.</p>
                    </div>

                    {{-- 3. Virtual Safe --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-blue-light">
                            <svg class="size-5 text-nrapa-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Virtual Safe</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Track your firearms, store license details, and get renewal reminders.</p>
                    </div>

                    {{-- 4. Loading Bench --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-orange-light">
                            <svg class="size-5 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8l-1 1v6l4 6a2 2 0 01-1.7 3H6.7A2 2 0 015 17l4-6V5L8 4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Virtual Loading Bench</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Store load recipes and track powder, primers, and bullets. Link loads to your firearms.</p>
                    </div>

                    {{-- 5. Activities --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-orange-light">
                            <svg class="size-5 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M9 17h6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 6l1 1 2-2M5 10l1 1 2-2M5 14l1 1 2-2M5 18l1 1 2-2"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Shooting Activities</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Log activities, track rounds fired per firearm, and maintain dedicated status requirements.</p>
                    </div>

                    {{-- 6. Dedicated Status --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-nrapa-orange-light">
                            <svg class="size-5 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-zinc-900">Dedicated Status</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Monitor your progress toward dedicated hunter or sport shooter requirements.</p>
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
        @endphp
        <section id="pricing" class="border-t border-zinc-100 py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <h2 class="text-sm font-semibold tracking-wide uppercase text-nrapa-blue">Membership</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-zinc-900" style="text-wrap: balance">
                        Choose your package
                    </p>
                </div>

                @if($membershipTypes->count() > 0)
                <div class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="relative rounded-xl border {{ $type->is_featured ? 'border-nrapa-blue bg-white shadow-md' : 'border-zinc-200 bg-white' }} p-8 transition">
                        @if($type->is_featured)
                        <div class="absolute -top-3 left-6 rounded-full bg-nrapa-orange px-3 py-1 text-xs font-bold text-zinc-900">
                            Recommended
                        </div>
                        @endif

                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex size-10 items-center justify-center rounded-lg {{ $type->is_featured ? 'bg-nrapa-blue-light' : 'bg-zinc-100' }}">
                                @if($type->dedicated_type === 'both')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @elseif($type->dedicated_type === 'hunter')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                @elseif($type->dedicated_type === 'sport')
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" />
                                </svg>
                                @else
                                <svg class="size-5 {{ $type->is_featured ? 'text-nrapa-blue' : 'text-zinc-600' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                @endif
                            </div>
                            @if($type->dedicated_type)
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $type->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800' : ($type->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type) }}
                            </span>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-zinc-900">{{ $type->name }}</h3>

                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-3xl font-extrabold text-zinc-900">R{{ number_format($type->price, 0) }}</span>
                            <span class="text-sm text-zinc-500">/ {{ $type->duration_type === 'lifetime' ? 'once-off' : 'year' }}</span>
                        </div>

                        <p class="mt-3 text-sm text-zinc-600">{{ $type->description }}</p>

                        <ul class="mt-6 space-y-2">
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Virtual Safe &amp; Loading Bench
                            </li>
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                QR-Verified Certificates
                            </li>
                            @if($type->requires_knowledge_test)
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Knowledge Test Access
                            </li>
                            @endif
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Dedicated Status Support
                            </li>
                            @endif
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-nrapa-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Learning Center Access
                            </li>
                        </ul>

                        <a href="{{ route('register') }}" class="mt-8 block w-full text-center rounded-lg {{ $type->is_featured ? 'bg-nrapa-blue text-white hover:bg-nrapa-blue-dark' : 'border border-nrapa-blue text-nrapa-blue hover:bg-nrapa-blue-light' }} px-4 py-2.5 text-sm font-semibold transition">
                            Get Started
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="mt-16 text-center">
                    <p class="text-zinc-500">Membership packages will be listed here shortly.</p>
                    <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-nrapa-blue px-6 py-3 text-sm font-semibold text-white hover:bg-nrapa-blue-dark transition">
                        Register Now
                    </a>
                </div>
                @endif
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 bg-zinc-50">
            <div class="mx-auto max-w-6xl px-6 py-10">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-7 object-contain" />
                        <span class="text-sm font-semibold text-zinc-900">NRAPA Members Portal</span>
                    </div>
                    <p class="text-sm text-zinc-500">
                        &copy; {{ date('Y') }} National Rifle and Pistol Association of South Africa. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
