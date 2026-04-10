<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | NRAPA</title>
        <meta name="description" content="@yield('description')">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="NRAPA">
        <meta property="og:title" content="@yield('title') | NRAPA">
        <meta property="og:description" content="@yield('description')">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('nrapa-icon.png') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title') | NRAPA">
        <meta name="twitter:description" content="@yield('description')">
        <meta name="twitter:image" content="{{ asset('nrapa-icon.png') }}">
        <link rel="icon" href="/nrapa-icon.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @php
            $seoTitle = trim($__env->yieldContent('title'));
            $seoDescription = trim($__env->yieldContent('description'));
            $breadcrumbLabel = trim($__env->yieldContent('breadcrumb'));
            $infoShortBreadcrumb = trim($__env->yieldContent('short_breadcrumb')) !== '';
            $infoBreadcrumbItems = $infoShortBreadcrumb
                ? [
                    ['position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['position' => 2, 'name' => $breadcrumbLabel !== '' ? $breadcrumbLabel : 'Info', 'item' => url()->current()],
                ]
                : [
                    ['position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['position' => 2, 'name' => 'Info & guides', 'item' => route('info.index')],
                    ['position' => 3, 'name' => $breadcrumbLabel !== '' ? $breadcrumbLabel : 'Guide', 'item' => url()->current()],
                ];
            $infoBreadcrumbJson = array_map(fn ($row) => [
                '@type' => 'ListItem',
                'position' => $row['position'],
                'name' => $row['name'],
                'item' => $row['item'],
            ], $infoBreadcrumbItems);
        @endphp
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $infoBreadcrumbJson,
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $seoTitle,
                'description' => $seoDescription,
                'url' => url()->current(),
                'isPartOf' => [
                    '@type' => 'WebSite',
                    'name' => 'NRAPA',
                    'url' => config('app.url'),
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Ranyati Group',
                        'url' => 'https://ranyati.co.za',
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        @stack('structured_data')
        <style>
            .hero-gradient {
                background: linear-gradient(135deg, #061e3c 0%, #0B4EA2 50%, #083A7A 100%);
            }
            .hero-pattern {
                background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
                background-size: 40px 40px;
            }
            .info-prose a { color: #0B4EA2; text-decoration: underline; text-underline-offset: 3px; }
            .dark .info-prose a { color: #60a5fa; }
            .info-prose a:hover { color: #083A7A; }
            .dark .info-prose a:hover { color: #93c5fd; }
            .info-prose .checklist { list-style: none; padding-left: 0; margin-left: 0; }
            .info-prose .checklist li {
                position: relative; padding-left: 32px; margin-bottom: 10px;
            }
            .info-prose .checklist li::before {
                content: ''; position: absolute; left: 0; top: 5px;
                width: 18px; height: 18px; border-radius: 5px;
                background: rgba(11,78,162,0.1); border: 1px solid rgba(11,78,162,0.2);
            }
            .dark .info-prose .checklist li::before {
                background: rgba(96,165,250,0.1); border-color: rgba(96,165,250,0.2);
            }
            .info-prose .checklist li::after {
                content: '\2713'; position: absolute; left: 3.5px; top: 3px;
                font-size: 12px; font-weight: 700; color: #0B4EA2;
            }
            .dark .info-prose .checklist li::after { color: #60a5fa; }
            .info-prose .info-card {
                padding: 20px 24px; border-radius: 12px; margin: 20px 0;
                background: #f4f6fa; border: 1px solid #e2e8f0;
            }
            .dark .info-prose .info-card {
                background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.06);
            }
            .info-prose .info-card h4 {
                font-size: 15px; font-weight: 700; margin-bottom: 8px;
            }
            .info-prose .step-list { list-style: none; counter-reset: step; padding-left: 0; margin-left: 0; }
            .info-prose .step-list > li {
                counter-increment: step; position: relative; padding-left: 48px; margin-bottom: 20px;
            }
            .info-prose .step-list > li::before {
                content: counter(step); position: absolute; left: 0; top: 0;
                width: 32px; height: 32px; border-radius: 8px;
                background: #0B4EA2; color: #fff;
                font-size: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center;
                line-height: 32px; text-align: center;
            }
            .info-prose .link-list { list-style: none; padding-left: 0; margin-left: 0; display: flex; flex-wrap: wrap; gap: 8px 16px; }
            .info-prose .link-list li a {
                display: inline-flex; align-items: center; gap: 4px; padding: 4px 0;
            }
            .info-prose .link-list li a::before { content: '→ '; font-weight: 600; }
        </style>
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-900 dark:text-zinc-100">

        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#061e3c]/80 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8" aria-label="Global">
                <a href="/" class="group">
                    <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" class="h-9 w-auto object-contain transition group-hover:opacity-80" />
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="{{ route('home') }}#features" class="text-sm font-medium text-zinc-300 hover:text-white transition">Features</a>
                    <a href="{{ route('home') }}#pricing" class="text-sm font-medium text-zinc-300 hover:text-white transition">Packages</a>
                    <a href="{{ route('info.index') }}" class="text-sm font-medium text-zinc-300 hover:text-white transition">Info &amp; guides</a>
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

        <section class="hero-gradient relative overflow-hidden pt-28 pb-16 sm:pt-32 sm:pb-20">
            <div class="hero-pattern absolute inset-0"></div>
            <div class="relative mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">@yield('heading')</h1>
                @hasSection('subheading')
                    <p class="mt-3 text-base text-zinc-300">@yield('subheading')</p>
                @endif
            </div>
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                    <path d="M0 40h1440V20C1200 0 240 0 0 20v20z" fill="white" class="dark:fill-zinc-900"/>
                </svg>
            </div>
        </section>

        <main class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
            {{-- Breadcrumb navigation --}}
            <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition">Home</a>
                @if(trim($__env->yieldContent('short_breadcrumb')))
                    <span>&rsaquo;</span>
                    <span class="text-zinc-900 dark:text-white font-medium">@yield('breadcrumb')</span>
                @else
                    <span>&rsaquo;</span>
                    <a href="{{ route('info.index') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition">Info &amp; guides</a>
                    <span>&rsaquo;</span>
                    <span class="text-zinc-900 dark:text-white font-medium">@yield('breadcrumb')</span>
                @endif
            </nav>

            {{-- Sidebar navigation for info pages --}}
            <div class="mb-8 flex flex-wrap gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <a href="{{ route('info.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.index') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Hub</a>
                <a href="{{ route('info.about') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.about') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">About NRAPA</a>
                <a href="{{ route('info.dedicated-sport-shooter-south-africa') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.dedicated-sport-shooter-south-africa') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Dedicated sport shooter</a>
                <a href="{{ route('info.dedicated-hunter-south-africa') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.dedicated-hunter-south-africa') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Dedicated hunter</a>
                <a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.how-to-get-dedicated-status-south-africa') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">How to get dedicated status</a>
                <a href="{{ route('info.dedicated-procedure') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.dedicated-procedure') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Dedicated procedure</a>
                <a href="{{ route('info.endorsements') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.endorsements') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Endorsements</a>
                <a href="{{ route('info.membership-benefits') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.membership-benefits') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Membership benefits</a>
                <a href="{{ route('info.faq') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.faq') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">FAQ</a>
                <a href="{{ route('info.firearm-licence-process') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.firearm-licence-process') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Firearm licence process</a>
                <a href="{{ route('info.minimum-requirements') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.minimum-requirements') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Minimum requirements</a>
                <a href="{{ route('info.shooting-exercises') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('info.shooting-exercises') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Shooting activities</a>
            </div>

            <article class="info-prose prose prose-zinc max-w-none dark:prose-invert prose-headings:font-bold prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3 prose-p:leading-relaxed prose-li:leading-relaxed">
                @yield('content')
            </article>

            {{-- CTA --}}
            <div class="mt-16 rounded-2xl border border-zinc-200 bg-gradient-to-r from-[#061e3c] to-[#0B4EA2] p-8 text-center dark:border-zinc-700">
                <h3 class="text-xl font-bold text-white">Ready to become a member?</h3>
                <p class="mt-2 text-zinc-300">Join NRAPA today and manage your dedicated status, endorsements, and compliance all in one place.</p>
                <div class="mt-6 flex justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-6 py-2.5 text-sm font-semibold text-[#0B4EA2] hover:bg-zinc-100 transition">Go to Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-lg bg-nrapa-orange px-6 py-2.5 text-sm font-semibold text-white hover:bg-nrapa-orange-dark transition">Register Now</a>
                        <a href="{{ route('login') }}" class="rounded-lg bg-white/10 px-6 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition">Log In</a>
                    @endauth
                </div>
            </div>
        </main>

        <footer class="relative overflow-hidden" style="background: linear-gradient(180deg, #030b16 0%, #061e3c 100%);">
            <div class="mx-auto max-w-7xl px-6 pt-12 pb-8 lg:px-8">
                <div class="grid grid-cols-1 gap-10 sm:grid-cols-3 items-start">
                    <div>
                        <img src="{{ asset('logo-ranyatigroup-white_text.png') }}" alt="Ranyati Group" class="h-8 w-auto" />
                        <p class="mt-5 text-[13px] leading-[1.7] text-white/30">
                            Specialist firearm administration services since 2006.<br>
                            Trading as Ranyati Firearm Motivations (Pty) Ltd.
                        </p>
                    </div>
                    <div class="flex flex-col items-start sm:items-center text-left sm:text-center">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Resources</h4>
                        <div class="mt-5 flex flex-col gap-2">
                            <a href="{{ route('info.index') }}" class="text-[13px] text-white/40 hover:text-white transition">Info hub</a>
                            <a href="{{ route('info.about') }}" class="text-[13px] text-white/40 hover:text-white transition">About NRAPA</a>
                            <a href="{{ route('info.dedicated-sport-shooter-south-africa') }}" class="text-[13px] text-white/40 hover:text-white transition">Dedicated sport shooter</a>
                            <a href="{{ route('info.dedicated-hunter-south-africa') }}" class="text-[13px] text-white/40 hover:text-white transition">Dedicated hunter</a>
                            <a href="{{ route('info.faq') }}" class="text-[13px] text-white/40 hover:text-white transition">FAQ</a>
                            <a href="https://ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition">Ranyati Group</a>
                            <a href="https://motivations.ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition">Firearm licence motivations</a>
                            <a href="https://storage.ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition">Secure firearm storage</a>
                        </div>
                    </div>
                    <div class="flex flex-col items-start sm:items-end">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Contact</h4>
                        <div class="mt-5 flex flex-col items-start sm:items-end gap-2">
                            <a href="tel:+27871510987" class="text-[13px] text-white/40 hover:text-white transition">+27 87 151 0987</a>
                            <a href="mailto:info@nrapa.co.za" class="text-[13px] text-white/40 hover:text-white transition">info@nrapa.co.za</a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-white/[0.04] mt-10 py-6 flex flex-col items-center gap-2">
                    <div class="flex gap-4 items-center">
                        <a href="{{ route('terms-and-conditions') }}" class="text-[11px] text-white/30 hover:text-white/60 transition">Terms</a>
                        <span class="text-white/10">&bull;</span>
                        <a href="{{ route('privacy-policy') }}" class="text-[11px] text-white/30 hover:text-white/60 transition">Privacy</a>
                        <span class="text-white/10">&bull;</span>
                        <a href="{{ route('home') }}" class="text-[11px] text-white/30 hover:text-white/60 transition">Home</a>
                    </div>
                    <p class="text-center text-[10px] tracking-[0.1em] text-white/15">
                        &copy; {{ date('Y') }} Ranyati Firearm Motivations (Pty) Ltd. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
