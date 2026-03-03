<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NRAPA - A Division of Ranyati | Members Portal</title>
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
                <a href="/" class="flex items-center gap-4 group">
                    <img src="{{ asset('ranyati-logo-dark.png') }}" alt="Ranyati" class="h-10 object-contain transition group-hover:scale-105" />
                    <span class="text-zinc-500/50">|</span>
                    <img src="{{ asset('nrapa-logo-horizontal-white.png') }}" alt="NRAPA" class="h-8 object-contain" />
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="#about" class="text-sm font-medium text-zinc-300 hover:text-white transition">About</a>
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

        {{-- About Ranyati Introduction (moved to top per client request) --}}
        <section id="about" class="hero-gradient relative pt-28 pb-28 sm:pt-36 sm:pb-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                    <div class="flex-shrink-0 flex flex-col items-center gap-4">
                        <img src="{{ asset('ranyati-logo-dark.png') }}" alt="Ranyati Firearm Motivations" class="h-24 sm:h-36 object-contain" />
                        <img src="{{ asset('nrapa-logo-horizontal-white.png') }}" alt="NRAPA" class="h-24 sm:h-36 object-contain" />
                    </div>
                    <div class="text-center lg:text-left">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            Firearm Administration Specialists <span class="text-nrapa-orange">Since 2006</span>
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-300">
                            For nearly two decades, Ranyati has been a trusted authority in the administration of the Firearms Control Act in South Africa. We specialise in professional firearm motivations, licence applications, renewals, and compliance support &mdash; simplifying a complex legal process for responsible firearm owners.
                        </p>
                        <p class="mt-4 text-base leading-relaxed text-zinc-300">
                            Through our accredited association, <strong class="text-white">NRAPA</strong> (National Rifle &amp; Pistol Association), we provide members with dedicated hunter and sport shooting status, structured record-keeping, and an advanced online platform that streamlines compliance and renewal reminders.
                        </p>
                        <p class="mt-4 text-base leading-relaxed text-zinc-400">
                            Supported by our secure firearm storage infrastructure, we offer a complete, end-to-end solution &mdash; from application and accreditation to safe custody and estate administration.
                        </p>
                        <p class="mt-6 text-sm font-semibold text-nrapa-orange uppercase tracking-wider">
                            Ranyati is more than a service provider. We are your long-term compliance partner.
                        </p>
                    </div>
                </div>

                {{-- Services pillars --}}
                <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm flex flex-col">
                        <img src="{{ asset('nrapa-icon.png') }}" alt="NRAPA" class="mx-auto size-12 object-contain" />
                        <h3 class="mt-4 text-base font-bold text-white">NRAPA</h3>
                        <p class="mt-1 text-xs font-medium text-nrapa-orange uppercase tracking-wider">Membership Ecosystem</p>
                        <p class="mt-3 text-sm text-zinc-400">SAPS-accredited association for dedicated sport &amp; hunter status, digital compliance, and member services.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm flex flex-col">
                        <img src="{{ asset('ranyati-icon.png') }}" alt="Ranyati" class="mx-auto size-12 object-contain" />
                        <h3 class="mt-4 text-base font-bold text-white">Firearm Motivations</h3>
                        <p class="mt-1 text-xs font-medium text-nrapa-orange uppercase tracking-wider">Licence Applications</p>
                        <p class="mt-3 text-sm text-zinc-400">Professional motivations, licence applications, renewals, and compliance support by experienced administrators.</p>
                        @if(\App\Models\SystemSetting::get('whatsapp_motivations'))
                            @php
                                $now = now('Africa/Johannesburg');
                                $from = \App\Models\SystemSetting::get('whatsapp_available_from', '08:00');
                                $to = \App\Models\SystemSetting::get('whatsapp_available_to', '17:00');
                                $motAvailable = $now->format('H:i') >= $from && $now->format('H:i') < $to;
                            @endphp
                            @if($motAvailable)
                            <a href="https://wa.me/{{ \App\Models\SystemSetting::get('whatsapp_motivations') }}?text=Hi%2C%20I%27d%20like%20to%20enquire%20about%20firearm%20motivations" target="_blank" rel="noopener" class="mt-auto pt-4 border-t border-white/10 inline-flex items-center justify-center gap-2 text-sm font-semibold text-green-400 hover:text-green-300 transition">
                                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Chat on WhatsApp
                            </a>
                            @else
                            <span class="mt-auto pt-4 border-t border-white/10 inline-flex items-center justify-center gap-2 text-sm text-zinc-500">
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Available {{ $from }}&ndash;{{ $to }} SAST
                            </span>
                            @endif
                        @endif
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm flex flex-col">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-nrapa-blue/30">
                            <svg class="size-6 text-nrapa-orange" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/>
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-white">Firearm Storage</h3>
                        <p class="mt-1 text-xs font-medium text-nrapa-orange uppercase tracking-wider">Secure Infrastructure</p>
                        <p class="mt-3 text-sm text-zinc-400">Safe custody, estate administration, and secure firearm storage backed by physical infrastructure.</p>
                        @if(\App\Models\SystemSetting::get('whatsapp_storage'))
                            @php
                                $nowSt = now('Africa/Johannesburg');
                                $fromSt = \App\Models\SystemSetting::get('whatsapp_available_from', '08:00');
                                $toSt = \App\Models\SystemSetting::get('whatsapp_available_to', '17:00');
                                $storAvailable = $nowSt->format('H:i') >= $fromSt && $nowSt->format('H:i') < $toSt;
                            @endphp
                            @if($storAvailable)
                            <a href="https://wa.me/{{ \App\Models\SystemSetting::get('whatsapp_storage') }}?text=Hi%2C%20I%27d%20like%20to%20enquire%20about%20firearm%20storage" target="_blank" rel="noopener" class="mt-auto pt-4 border-t border-white/10 inline-flex items-center justify-center gap-2 text-sm font-semibold text-green-400 hover:text-green-300 transition">
                                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Chat on WhatsApp
                            </a>
                            @else
                            <span class="mt-auto pt-4 border-t border-white/10 inline-flex items-center justify-center gap-2 text-sm text-zinc-500">
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Available {{ $fromSt }}&ndash;{{ $toSt }} SAST
                            </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Curve transition to light section --}}
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full" preserveAspectRatio="none">
                    <path d="M0 80L1440 80L1440 0C1440 0 1080 60 720 60C360 60 0 0 0 0L0 80Z" fill="white"/>
                </svg>
            </div>
        </section>

        {{-- Hero --}}
        <section class="bg-white relative overflow-hidden pt-16 pb-24 sm:pt-20 sm:pb-28">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <div class="animate-fade-up-delay">
                    <img src="{{ asset('nrapa-logo-horizontal.png') }}" alt="NRAPA - National Rifle and Pistol Association" class="mx-auto h-20 sm:h-28 md:h-36 w-auto object-contain" />
                </div>

                <p class="mt-6 text-sm font-medium text-zinc-500 tracking-wide uppercase animate-fade-up-delay">
                    A Division of Ranyati &mdash; Firearm Administration Specialists Since 2006
                </p>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-600 animate-fade-up-delay-2">
                    Your partner in responsible firearm ownership. We help you obtain dedicated status, manage your licences, and stay compliant with the Firearms Control Act &mdash; so you can focus on what you love, whether it's on the range or in the bush.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-up-delay-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-nrapa-orange px-8 py-3.5 text-sm font-bold text-white shadow-lg btn-glow hover:bg-nrapa-orange-dark transition-all">
                            Go to Dashboard
                        </a>
                        <a href="#features" class="text-sm font-semibold text-nrapa-blue hover:text-nrapa-blue-dark transition">
                            View features <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-xl bg-nrapa-orange px-8 py-3.5 text-sm font-bold text-white shadow-lg btn-glow hover:bg-nrapa-orange-dark transition-all">
                            Become a Member
                        </a>
                        <a href="#features" class="rounded-xl border border-nrapa-blue/20 px-8 py-3.5 text-sm font-semibold text-nrapa-blue hover:bg-nrapa-blue/5 transition-all">
                            View Features
                        </a>
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-zinc-500 hover:text-nrapa-blue transition">
                            Already a member? Sign in <span aria-hidden="true">&rarr;</span>
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- Credentials Bar --}}
        <section class="relative bg-blue-50 py-12">
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
        <section id="features" class="bg-white py-24">
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
        <section id="pricing" class="bg-zinc-100 py-24">
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
                @php
                    $count = $membershipTypes->count();
                    $gridClass = match(true) {
                        $count <= 2 => 'md:grid-cols-2 max-w-3xl',
                        $count === 3 => 'md:grid-cols-2 lg:grid-cols-3 max-w-5xl',
                        $count === 4 => 'md:grid-cols-2 lg:grid-cols-4 max-w-6xl',
                        default => 'md:grid-cols-2 lg:grid-cols-3 max-w-5xl',
                    };
                @endphp
                <div class="mt-16 grid gap-8 {{ $gridClass }} mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="pricing-hover relative flex flex-col rounded-2xl {{ $type->is_featured ? 'border-2 border-nrapa-blue bg-white shadow-lg ring-1 ring-nrapa-blue/10' : 'border border-zinc-200 bg-white' }} p-8">
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

                        <div class="flex-1">
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
                        </div>

                        <a href="{{ route('register') }}" class="mt-6 block w-full text-center rounded-xl {{ $type->is_featured ? 'bg-nrapa-blue text-white hover:bg-nrapa-blue-dark shadow-sm' : 'border border-zinc-200 text-zinc-700 hover:border-nrapa-blue hover:text-nrapa-blue' }} px-4 py-3 text-sm font-bold transition-all">
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
            {{-- Top curve from light section --}}
            <div class="absolute top-0 left-0 right-0">
                <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full" preserveAspectRatio="none">
                    <path d="M0 0L1440 0L1440 60C1440 60 1080 10 720 10C360 10 0 60 0 60L0 0Z" fill="#f4f4f5"/>
                </svg>
            </div>
            <div class="hero-pattern absolute inset-0"></div>
            <div class="relative mx-auto max-w-4xl px-6 pt-28 pb-20 text-center">
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
        <footer class="bg-zinc-900">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="py-12 md:py-16">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                        <div>
                            <img src="{{ asset('ranyati-logo-dark.png') }}" alt="Ranyati" class="h-10 object-contain" />
                            <p class="mt-2 text-xs text-zinc-500">Firearm Administration Specialists Since 2006</p>
                            <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-400">
                                Accredited by the SA Police Services with designated powers to allocate Dedicated Sport and Hunter status. FAR 1300122 &amp; 1300127.
                            </p>
                        </div>
                        <div class="flex gap-12">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Platform</h4>
                                <ul class="mt-3 space-y-2">
                                    <li><a href="#features" class="text-sm text-zinc-400 hover:text-white transition">Features</a></li>
                                    <li><a href="#pricing" class="text-sm text-zinc-400 hover:text-white transition">Packages</a></li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Account</h4>
                                <ul class="mt-3 space-y-2">
                                    <li><a href="{{ route('login') }}" class="text-sm text-zinc-400 hover:text-white transition">Sign In</a></li>
                                    <li><a href="{{ route('register') }}" class="text-sm text-zinc-400 hover:text-white transition">Register</a></li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Legal</h4>
                                <ul class="mt-3 space-y-2">
                                    <li><a href="{{ route('terms-and-conditions') }}" class="text-sm text-zinc-400 hover:text-white transition">Terms &amp; Conditions</a></li>
                                    <li><a href="{{ route('privacy-policy') }}" class="text-sm text-zinc-400 hover:text-white transition">Privacy Policy</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-zinc-800 py-6">
                    <p class="text-center text-xs text-zinc-500">
                        &copy; {{ date('Y') }} NRAPA &mdash; A Division of Ranyati Firearm Motivations (Pty) Ltd. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
