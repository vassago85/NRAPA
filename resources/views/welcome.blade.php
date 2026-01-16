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
                                        <span class="font-medium text-white">Dedicated Status Tracking</span>
                                        <p class="text-sm text-zinc-500">Knowledge tests & activity logging for dedicated members</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Virtual Armoury</span>
                                        <p class="text-sm text-zinc-500">Track your firearms, licenses & load data</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-white">Motivation Letters</span>
                                        <p class="text-sm text-zinc-500">Request firearm motivation documents</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Membership Types Section --}}
        <section class="py-24 bg-zinc-900">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-base font-semibold leading-7 text-emerald-400">Flexible Options</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Membership Types
                    </p>
                    <p class="mt-6 text-lg leading-8 text-zinc-400">
                        Choose the membership that fits your needs
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Standard --}}
                    <div class="rounded-2xl bg-zinc-800/50 border border-zinc-700/50 p-6 hover:border-emerald-500/50 transition-colors">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-700/50 mb-4">
                            <svg class="size-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Standard</h3>
                        <p class="text-sm text-zinc-400">Annual membership with basic benefits and certificate access.</p>
                    </div>
                    
                    {{-- Dedicated --}}
                    <div class="rounded-2xl bg-gradient-to-br from-emerald-900/50 to-zinc-800/50 border border-emerald-500/30 p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-emerald-500 text-xs font-bold text-white px-3 py-1 rounded-bl-lg">Popular</div>
                        <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/20 mb-4">
                            <svg class="size-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Dedicated Hunter/Sport</h3>
                        <p class="text-sm text-zinc-400">Full access including knowledge tests, activity tracking, and dedicated certificates.</p>
                    </div>
                    
                    {{-- Lifetime --}}
                    <div class="rounded-2xl bg-zinc-800/50 border border-zinc-700/50 p-6 hover:border-emerald-500/50 transition-colors">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-700/50 mb-4">
                            <svg class="size-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Lifetime</h3>
                        <p class="text-sm text-zinc-400">One-time payment for permanent membership with all benefits.</p>
                    </div>
                    
                    {{-- Junior --}}
                    <div class="rounded-2xl bg-zinc-800/50 border border-zinc-700/50 p-6 hover:border-emerald-500/50 transition-colors">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-700/50 mb-4">
                            <svg class="size-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Junior</h3>
                        <p class="text-sm text-zinc-400">Discounted membership for young shooters building their skills.</p>
                    </div>
                </div>
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
