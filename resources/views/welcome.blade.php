<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NRAPA - Secure Membership Platform</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="min-h-screen bg-zinc-900">
    {{-- Hero Section --}}
    <div class="relative isolate overflow-hidden">
        {{-- Background gradient --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-emerald-950 via-zinc-900 to-zinc-900"></div>
        <div class="absolute inset-y-0 right-1/2 -z-10 mr-16 w-[200%] origin-bottom-left skew-x-[-30deg] bg-zinc-900/60 shadow-xl shadow-emerald-600/10 ring-1 ring-emerald-900/20 sm:mr-28 lg:mr-0 xl:mr-16 xl:origin-center"></div>

        <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8 lg:py-40">
            <div class="mx-auto max-w-2xl lg:mx-0 lg:grid lg:max-w-none lg:grid-cols-2 lg:gap-x-16 lg:gap-y-6 xl:grid-cols-1 xl:grid-rows-1 xl:gap-x-8">
                <h1 class="max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-6xl lg:col-span-2 xl:col-auto">
                    NRAPA<br>
                    <span class="text-emerald-400">Members Portal</span>
                </h1>
                <div class="mt-6 max-w-xl lg:mt-0 xl:col-end-1 xl:row-start-1">
                    <p class="text-lg leading-8 text-zinc-300">
                        Welcome to the National Rifle and Pistol Association Secure Membership, Compliance & Motivation Platform. Manage your membership, certificates, and compliance requirements in one place.
                    </p>
                    <div class="mt-10 flex items-center gap-x-6">
                    @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition">
                                Go to Dashboard
                        </a>
                    @else
                            <a href="{{ route('login') }}" class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition">
                                Log In
                            </a>
                        @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg border border-zinc-600 bg-zinc-800/50 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-zinc-700 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                    </div>
                </div>
                <div class="mt-10 aspect-[6/5] w-full max-w-lg rounded-2xl bg-zinc-800 p-8 object-cover shadow-xl ring-1 ring-zinc-700 sm:mt-16 lg:mt-0 lg:max-w-none xl:row-span-2 xl:row-end-2 xl:mt-36">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex size-12 items-center justify-center rounded-lg bg-emerald-600">
                            <svg class="size-7 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z" fill="currentColor"/>
                                <path d="M10 12L12 14L16 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Secure & Compliant</h3>
                            <p class="text-sm text-zinc-400">POPIA-aligned data handling</p>
                        </div>
                    </div>
                    <ul class="space-y-4 text-zinc-300">
                        <li class="flex items-center gap-3">
                            <svg class="size-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Apply for & manage your membership</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Download QR-verified certificates</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                            <span>Complete knowledge test online</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                            <span>Track dedicated status progress</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="size-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Request firearm motivation letters</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Features Section --}}
    <div class="bg-zinc-900 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:text-center">
                <h2 class="text-base font-semibold leading-7 text-emerald-400">Membership Made Easy</h2>
                <p class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Everything you need for compliance
                </p>
                <p class="mt-6 text-lg leading-8 text-zinc-300">
                    Our platform streamlines your membership journey from application to certification.
                </p>
            </div>
            <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600">
                                <svg class="size-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            Multiple Membership Types
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-zinc-300">
                            <p class="flex-auto">Choose from Standard, Dedicated, Lifetime, or Junior memberships - each with configurable benefits and requirements.</p>
                        </dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600">
                                <svg class="size-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                            </div>
                            Verified Certificates
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-zinc-300">
                            <p class="flex-auto">All certificates include QR codes for instant verification. Present with confidence knowing your credentials are verifiable.</p>
                        </dd>
                    </div>
                    <div class="flex flex-col">
                        <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-white">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600">
                                <svg class="size-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                            </div>
                            Online Knowledge Test
                        </dt>
                        <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-zinc-300">
                            <p class="flex-auto">Complete your required knowledge test online at your convenience. Mixed question types with automatic and manual grading.</p>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="bg-zinc-950 py-12">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="flex items-center gap-3">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-600">
                        <svg class="size-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z" fill="currentColor"/>
                            <path d="M10 12L12 14L16 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    </div>
                    <span class="text-sm font-semibold text-white">NRAPA Members</span>
                </div>
                <p class="text-sm text-zinc-400">
                    &copy; {{ date('Y') }} National Rifle and Pistol Association. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    </body>
</html>
