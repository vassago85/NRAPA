<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | NRAPA Site Guides</title>
        <meta name="description" content="@yield('description')">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="NRAPA">
        <meta property="og:title" content="@yield('title') | NRAPA Site Guides">
        <meta property="og:description" content="@yield('description')">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('nrapa-icon.png') }}">
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
            .guide-prose a { color: #0B4EA2; text-decoration: underline; text-underline-offset: 3px; }
            .dark .guide-prose a { color: #60a5fa; }
            .guide-prose a:hover { color: #083A7A; }
            .dark .guide-prose a:hover { color: #93c5fd; }
            .guide-prose img {
                border-radius: 12px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                margin: 16px 0;
                max-width: 100%;
                height: auto;
            }
            .dark .guide-prose img { border-color: rgba(255,255,255,0.08); }
            .guide-prose .info-card {
                padding: 20px 24px; border-radius: 12px; margin: 20px 0;
                background: #f4f6fa; border: 1px solid #e2e8f0;
            }
            .dark .guide-prose .info-card {
                background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.06);
            }
            .guide-prose .info-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
            .guide-prose .step-list { list-style: none; counter-reset: step; padding-left: 0; margin-left: 0; }
            .guide-prose .step-list > li {
                counter-increment: step; position: relative; padding-left: 48px; margin-bottom: 28px;
            }
            .guide-prose .step-list > li::before {
                content: counter(step); position: absolute; left: 0; top: 0;
                width: 32px; height: 32px; border-radius: 8px;
                background: #0B4EA2; color: #fff;
                font-size: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center;
                line-height: 32px; text-align: center;
            }
            .guide-prose .checklist { list-style: none; padding-left: 0; margin-left: 0; }
            .guide-prose .checklist li { position: relative; padding-left: 32px; margin-bottom: 10px; }
            .guide-prose .checklist li::before {
                content: ''; position: absolute; left: 0; top: 5px;
                width: 18px; height: 18px; border-radius: 5px;
                background: rgba(11,78,162,0.1); border: 1px solid rgba(11,78,162,0.2);
            }
            .guide-prose .checklist li::after {
                content: '\2713'; position: absolute; left: 3.5px; top: 3px;
                font-size: 12px; font-weight: 700; color: #0B4EA2;
            }
        </style>
        @stack('structured_data')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-900 dark:text-zinc-100">

        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#061e3c]/80 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8" aria-label="Global">
                <a href="/" class="group">
                    <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA logo" width="117" height="36" class="h-9 w-auto object-contain transition group-hover:opacity-80" />
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="{{ route('home') }}#features" class="text-sm font-medium text-zinc-300 hover:text-white transition">Features</a>
                    <a href="{{ route('home') }}#pricing" class="text-sm font-medium text-zinc-300 hover:text-white transition">Packages</a>
                    <a href="{{ route('info.index') }}" class="text-sm font-medium text-zinc-300 hover:text-white transition">Info &amp; guides</a>
                    <a href="{{ route('guides.index') }}" class="text-sm font-medium text-white transition">Site guides</a>
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
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-nrapa-orange">Site guides</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">@yield('heading')</h1>
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
            {{-- Breadcrumb --}}
            <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('guides.index') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition">Site guides</a>
                @hasSection('breadcrumb')
                    <span>&rsaquo;</span>
                    <span class="text-zinc-900 dark:text-white font-medium">@yield('breadcrumb')</span>
                @endif
            </nav>

            {{-- Guides sub-navigation --}}
            <div class="mb-8 flex flex-wrap gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <a href="{{ route('guides.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('guides.index') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">All guides</a>
                <a href="{{ route('guides.sign-up') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('guides.sign-up') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">How to sign up</a>
                <a href="{{ route('guides.upload-proof-of-payment') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('guides.upload-proof-of-payment') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Upload proof of payment</a>
                <a href="{{ route('guides.submit-activities') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('guides.submit-activities') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Submit activities</a>
                <a href="{{ route('guides.request-endorsement') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('guides.request-endorsement') ? 'bg-nrapa-blue text-white' : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">Request endorsement</a>
            </div>

            <article class="guide-prose prose prose-zinc max-w-none dark:prose-invert prose-headings:font-bold prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3 prose-p:leading-relaxed prose-li:leading-relaxed">
                @yield('content')
            </article>
        </main>

        <footer class="relative overflow-hidden" style="background: linear-gradient(180deg, #030b16 0%, #061e3c 100%);">
            <div class="mx-auto max-w-7xl px-6 pt-12 pb-8 lg:px-8">
                <div class="flex flex-col items-center gap-2 border-white/[0.04] py-6">
                    <div class="flex gap-4 items-center">
                        <a href="{{ route('guides.index') }}" class="text-[11px] text-white/30 hover:text-white/60 transition">Site guides</a>
                        <span class="text-white/10">&bull;</span>
                        <a href="{{ route('info.index') }}" class="text-[11px] text-white/30 hover:text-white/60 transition">Info &amp; guides</a>
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
