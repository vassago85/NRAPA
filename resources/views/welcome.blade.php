<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NRAPA - Members Portal</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .hero-pattern {
                background-color: #0c1821;
                background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23166534' fill-opacity='0.08'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        {{-- Header --}}
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <a href="/" class="-m-1.5 p-1.5 flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                            <svg class="size-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z" fill="currentColor"/>
                                <circle cx="12" cy="10" r="3" fill="white" opacity="0.9"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">NRAPA</span>
                    </a>
                </div>
                <div class="flex lg:flex-1 lg:justify-end gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold leading-6 text-emerald-400 hover:text-emerald-300 transition">
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-zinc-300 hover:text-white transition">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition shadow-lg shadow-emerald-600/20">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </nav>
        </header>

        {{-- Hero Section --}}
        <div class="relative isolate hero-pattern min-h-screen flex items-center">
            {{-- Background decorations --}}
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-emerald-600 to-cyan-500 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
            </div>
            <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-emerald-600 to-emerald-400 opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-6 py-32 sm:py-48 lg:px-8 lg:py-56">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    {{-- Left Content --}}
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-4 py-1.5 text-sm font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20 mb-8">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Secure Membership Portal
                        </div>
                        
                        <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
                            <span class="text-white">National Rifle &</span><br>
                            <span class="text-white">Pistol Association</span><br>
                            <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">Members Portal</span>
                        </h1>
                        
                        <p class="mt-6 text-lg leading-8 text-zinc-400 max-w-xl mx-auto lg:mx-0">
                            Your all-in-one platform for membership management, compliance tracking, dedicated status applications, and firearm motivation letters. Secure, compliant, and easy to use.
                        </p>
                        
                        <div class="mt-10 flex items-center justify-center lg:justify-start gap-x-6">
                            @auth
                                <a href="{{ route('dashboard') }}" class="rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-500 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-500 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all">
                                    Become a Member
                                </a>
                                <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-zinc-300 hover:text-white transition">
                                    Already a member? <span aria-hidden="true">→</span>
                                </a>
                            @endauth
                        </div>
                    </div>

                    {{-- Right Content - Feature Card --}}
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-r from-emerald-600/20 to-cyan-600/20 rounded-3xl blur-2xl"></div>
                        <div class="relative rounded-2xl bg-zinc-900/80 backdrop-blur border border-zinc-800 p-8 shadow-2xl">
                            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-zinc-800">
                                <div class="flex size-14 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg">
                                    <svg class="size-8 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z" fill="currentColor"/>
                                        <path d="M10 12L12 14L16 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">Member Benefits</h3>
                                    <p class="text-sm text-zinc-400">POPIA-compliant & secure</p>
                                </div>
                            </div>
                            
                            <ul class="space-y-4">
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Membership Management</span>
                                        <p class="text-sm text-zinc-500">Apply, renew, and track your membership status</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">QR-Verified Certificates</span>
                                        <p class="text-sm text-zinc-500">Instantly verifiable membership certificates</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Virtual Safe</span>
                                        <p class="text-sm text-zinc-500">Track your firearms, licenses & license expiry dates</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Virtual Loading Bench</span>
                                        <p class="text-sm text-zinc-500">Store and manage your reloading data</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Endorsement Letters</span>
                                        <p class="text-sm text-zinc-500">Compliance support for firearm license applications</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Key Features Section - Virtual Safe & Loading Bench --}}
        <section class="py-24 bg-zinc-900 border-b border-zinc-800">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-base font-semibold leading-7 text-emerald-400">Exclusive Member Tools</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Your Digital Firearm Management Hub
                    </p>
                    <p class="mt-6 text-lg leading-8 text-zinc-400">
                        Powerful digital tools to keep your firearms organized, compliant, and your reloading data at your fingertips
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    {{-- Virtual Safe --}}
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-gradient-to-r from-emerald-600/20 to-cyan-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative rounded-2xl bg-gradient-to-br from-zinc-800/80 to-zinc-900/80 border border-zinc-700/50 p-8 hover:border-emerald-500/50 transition-all">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex size-14 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg shadow-emerald-500/20">
                                    <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">Virtual Safe</h3>
                                    <p class="text-sm text-emerald-400">Digital Firearm Registry</p>
                                </div>
                            </div>
                            <p class="text-zinc-400 mb-6">
                                Keep a complete digital inventory of your firearms, including license details, expiry dates, and compliance documentation - all in one secure location.
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Track all your firearms in one place
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    License expiry notifications (18, 12, 6 months)
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Store license photos and documents
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Email & dashboard expiry alerts
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    {{-- Virtual Loading Bench --}}
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-gradient-to-r from-cyan-600/20 to-blue-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative rounded-2xl bg-gradient-to-br from-zinc-800/80 to-zinc-900/80 border border-zinc-700/50 p-8 hover:border-cyan-500/50 transition-all">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex size-14 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 shadow-lg shadow-cyan-500/20">
                                    <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">Virtual Loading Bench</h3>
                                    <p class="text-sm text-cyan-400">Reloading Data Tracker</p>
                                </div>
                            </div>
                            <p class="text-zinc-400 mb-6">
                                Your personal reloading database. Save your load recipes, track powder charges, bullet weights, and velocities for consistent, accurate results every time.
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Store unlimited load recipes
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Track powder, primers, and bullets
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Record velocity and accuracy notes
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-300">
                                    <svg class="size-5 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Link loads to specific firearms
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Membership Types Section - Dynamic from Database --}}
        @php
            $membershipTypes = \App\Models\MembershipType::where('display_on_landing', true)
                ->where('is_active', true)
                ->ordered()
                ->get();
        @endphp
        <section class="py-24 bg-zinc-900">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-base font-semibold leading-7 text-emerald-400">Flexible Options</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Membership Packages
                    </p>
                    <p class="mt-6 text-lg leading-8 text-zinc-400">
                        Choose the membership that fits your needs
                    </p>
                </div>
                
                @if($membershipTypes->count() > 0)
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="rounded-2xl {{ $type->is_featured ? 'bg-gradient-to-br from-emerald-900/50 to-zinc-800/50 border-emerald-500/30 ring-2 ring-emerald-500/20' : 'bg-zinc-800/50 border-zinc-700/50 hover:border-emerald-500/50' }} border p-8 relative overflow-hidden transition-colors">
                        @if($type->is_featured)
                        <div class="absolute top-0 right-0 bg-gradient-to-r from-amber-500 to-amber-600 text-xs font-bold text-white px-4 py-1.5 rounded-bl-lg flex items-center gap-1">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Recommended
                        </div>
                        @endif
                        
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex size-12 items-center justify-center rounded-xl {{ $type->is_featured ? 'bg-emerald-500/20' : 'bg-zinc-700/50' }}">
                                @if($type->dedicated_type === 'both')
                                <svg class="size-6 {{ $type->is_featured ? 'text-emerald-400' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @elseif($type->dedicated_type === 'hunter')
                                <svg class="size-6 {{ $type->is_featured ? 'text-emerald-400' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                @elseif($type->dedicated_type === 'sport')
                                <svg class="size-6 {{ $type->is_featured ? 'text-emerald-400' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="2" stroke-width="2"/>
                                </svg>
                                @else
                                <svg class="size-6 {{ $type->is_featured ? 'text-emerald-400' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                @endif
                            </div>
                            @if($type->dedicated_type)
                            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $type->dedicated_type === 'both' ? 'bg-purple-500/20 text-purple-300' : ($type->dedicated_type === 'hunter' ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300') }}">
                                {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type) }}
                            </span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-bold text-white mb-2">{{ $type->name }}</h3>
                        
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-3xl font-bold text-white">R{{ number_format($type->total_price, 0) }}</span>
                            <span class="text-sm text-zinc-400">/ {{ $type->duration_type === 'lifetime' ? 'once-off' : 'year' }}</span>
                        </div>
                        
                        @if($type->admin_fee > 0)
                        <p class="text-xs text-zinc-500 mb-4">(Includes R{{ number_format($type->admin_fee, 0) }} admin fee)</p>
                        @endif
                        
                        <p class="text-sm text-zinc-400 mb-6">{{ $type->description }}</p>
                        
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center gap-2 text-sm text-zinc-300">
                                <svg class="size-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Virtual Safe & Loading Bench
                            </li>
                            <li class="flex items-center gap-2 text-sm text-zinc-300">
                                <svg class="size-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                QR-Verified Certificates
                            </li>
                            @if($type->requires_knowledge_test)
                            <li class="flex items-center gap-2 text-sm text-zinc-300">
                                <svg class="size-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Knowledge Test Access
                            </li>
                            @endif
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2 text-sm text-zinc-300">
                                <svg class="size-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Dedicated Status Support
                            </li>
                            @endif
                            <li class="flex items-center gap-2 text-sm text-zinc-300">
                                <svg class="size-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Learning Center Access
                            </li>
                        </ul>
                        
                        <a href="{{ route('register') }}" class="block w-full text-center rounded-lg {{ $type->is_featured ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400' : 'bg-zinc-700 hover:bg-zinc-600' }} px-4 py-3 text-sm font-semibold text-white transition">
                            Get Started
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                {{-- Fallback static cards if no membership types configured --}}
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div class="rounded-2xl bg-zinc-800/50 border border-zinc-700/50 p-8 hover:border-emerald-500/50 transition-colors">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-700/50 mb-4">
                            <svg class="size-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Dedicated Hunter</h3>
                        <p class="text-sm text-zinc-400">Full membership for dedicated hunters with all platform features and hunting-specific content.</p>
                    </div>
                    
                    <div class="rounded-2xl bg-gradient-to-br from-emerald-900/50 to-zinc-800/50 border border-emerald-500/30 p-8 relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-emerald-500 text-xs font-bold text-white px-3 py-1 rounded-bl-lg">Recommended</div>
                        <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/20 mb-4">
                            <svg class="size-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Hunter & Sport Shooter</h3>
                        <p class="text-sm text-zinc-400">Complete access for both dedicated hunters and sport shooters with all content and features.</p>
                    </div>
                    
                    <div class="rounded-2xl bg-zinc-800/50 border border-zinc-700/50 p-8 hover:border-emerald-500/50 transition-colors">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-700/50 mb-4">
                            <svg class="size-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                <circle cx="12" cy="12" r="2" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Dedicated Sport Shooter</h3>
                        <p class="text-sm text-zinc-400">Full membership for dedicated sport shooters with all platform features and sport-specific content.</p>
                    </div>
                </div>
                @endif
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="py-24 bg-zinc-950">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-900 to-emerald-950 px-6 py-24 text-center shadow-2xl sm:px-16">
                    <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl"></div>
                    
                    <h2 class="relative mx-auto max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Ready to join NRAPA?
                    </h2>
                    <p class="relative mx-auto mt-6 max-w-xl text-lg leading-8 text-emerald-100/80">
                        Create your account today and start managing your membership, certifications, and compliance requirements in one secure place.
                    </p>
                    <div class="relative mt-10 flex items-center justify-center gap-x-6">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-900 shadow-sm hover:bg-zinc-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-900 shadow-sm hover:bg-zinc-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition">
                                Create Free Account
                            </a>
                            <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-white hover:text-emerald-200 transition">
                                Member login <span aria-hidden="true">→</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-zinc-950 border-t border-zinc-800">
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700">
                            <svg class="size-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z" fill="currentColor"/>
                                <circle cx="12" cy="10" r="3" fill="white" opacity="0.9"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-white">NRAPA Members Portal</span>
                    </div>
                    <p class="text-sm text-zinc-500">
                        &copy; {{ date('Y') }} National Rifle and Pistol Association of South Africa. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
