<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NRAPA - Members Portal</title>
        <link rel="icon" href="/nrapa-logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-logo.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                /* NRAPA brand tokens (hex fallbacks) */
                --nrapa-blue: #0B4EA2;
                --nrapa-orange: #F58220;

                /* Subtle washes (fallback) */
                --nrapa-blue-wash: rgba(11, 78, 162, 0.06);
                --nrapa-orange-wash: rgba(245, 130, 32, 0.10);
            }

            /* Progressive enhancement for modern browsers (more consistent lightness) */
            @supports (color: oklch(0.6 0.1 200)) {
                :root {
                    --nrapa-blue-wash: color-mix(in oklch, var(--nrapa-blue) 8%, white);
                    --nrapa-orange-wash: color-mix(in oklch, var(--nrapa-orange) 14%, white);
                }
            }

            .hero-pattern {
                background-color: #fafafa;
                background-image:
                    radial-gradient(900px 420px at 10% 0%, var(--nrapa-blue-wash), transparent 55%),
                    radial-gradient(900px 420px at 90% 20%, var(--nrapa-orange-wash), transparent 55%),
                    url("data:image/svg+xml,%3Csvg width='64' height='64' viewBox='0 0 64 64' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%230B4EA2' fill-opacity='0.06'%3E%3Cpath d='M12 10h2v2h-2v-2zm18 10h2v2h-2v-2zM44 8h2v2h-2V8zM8 44h2v2H8v-2zm30 28h2v2h-2v-2zM54 38h2v2h-2v-2z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        {{-- Header --}}
        <header class="sticky inset-x-0 top-0 z-50 border-b border-zinc-200 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/70">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <a href="/" class="-m-1.5 p-1.5 flex items-center gap-3">
                        <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-10 object-contain" />
                        <span class="text-xl font-extrabold tracking-tight text-zinc-900">NRAPA</span>
                    </a>
                </div>
                <div class="hidden lg:flex items-center gap-8">
                    <a href="#features" class="rounded-md text-sm font-semibold text-zinc-700 hover:text-[color:var(--nrapa-blue)] transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)]">Features</a>
                    <a href="#how-it-works" class="rounded-md text-sm font-semibold text-zinc-700 hover:text-[color:var(--nrapa-blue)] transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)]">How it works</a>
                    <a href="#pricing" class="rounded-md text-sm font-semibold text-zinc-700 hover:text-[color:var(--nrapa-blue)] transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)]">Membership Packages</a>
                </div>
                <div class="flex lg:flex-1 lg:justify-end gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold leading-6 text-[color:var(--nrapa-blue)] hover:opacity-80 transition">
                            Go to Dashboard <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md text-sm font-semibold leading-6 text-zinc-700 hover:text-[color:var(--nrapa-blue)] transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)]">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="rounded-lg bg-[color:var(--nrapa-orange)] px-4 py-2 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-black/10 hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-blue)] transition"
                            >
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
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[color:var(--nrapa-blue)] to-[color:var(--nrapa-orange)] opacity-15 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
            </div>
            <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[color:var(--nrapa-blue)] to-[color:var(--nrapa-orange)] opacity-10 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-6 py-32 sm:py-48 lg:px-8 lg:py-56">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    {{-- Left Content --}}
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-[color:var(--nrapa-blue-wash)] px-4 py-1.5 text-sm font-semibold text-[color:var(--nrapa-blue)] ring-1 ring-inset ring-zinc-200 mb-8">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping motion-reduce:animate-none absolute inline-flex h-full w-full rounded-full bg-[color:var(--nrapa-orange)] opacity-40"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[color:var(--nrapa-orange)]"></span>
                            </span>
                            Secure members platform
                        </div>
                        
                        <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
                            <span class="text-zinc-900">National Rifle &amp;</span><br>
                            <span class="text-zinc-900">Pistol Association</span><br>
                            <span class="bg-gradient-to-r from-[color:var(--nrapa-blue)] to-[color:var(--nrapa-orange)] bg-clip-text text-transparent">Members Portal</span>
                        </h1>
                        
                        <p class="mt-6 text-lg leading-8 text-zinc-600 max-w-xl mx-auto lg:mx-0">
                            Everything you need as a member: digital card, certificates, activities, dedicated status, virtual safe, loading bench, endorsements, and learning — in one secure place.
                        </p>
                        
                        <div class="mt-10 flex items-center justify-center lg:justify-start gap-x-6">
                            @auth
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="rounded-lg bg-[color:var(--nrapa-blue)] px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)] transition"
                                >
                                    Go to Dashboard
                                </a>
                                <a href="#features" class="text-sm font-semibold leading-6 text-[color:var(--nrapa-blue)] hover:opacity-80 transition">
                                    View features <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <a
                                    href="{{ route('register') }}"
                                    class="rounded-lg bg-[color:var(--nrapa-orange)] px-6 py-3.5 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-black/10 hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-blue)] transition"
                                >
                                    Become a Member
                                </a>
                                <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-[color:var(--nrapa-blue)] hover:opacity-80 transition">
                                    Already a member? <span aria-hidden="true">→</span>
                                </a>
                            @endauth
                        </div>
                    </div>

                    {{-- Right Content - Feature Card --}}
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-r from-[color:rgba(11,78,162,0.15)] to-[color:rgba(245,130,32,0.15)] rounded-3xl blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-2xl bg-white/80 backdrop-blur border border-zinc-200 p-8 shadow-xl">
                            <img
                                src="{{ asset('nrapa-logo.png') }}"
                                alt=""
                                aria-hidden="true"
                                class="pointer-events-none absolute -right-10 -top-10 w-56 opacity-[0.06] select-none"
                            />
                            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-zinc-200">
                                <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-14 object-contain" />
                                <div>
                                    <h3 class="text-xl font-bold text-zinc-900">Built for members</h3>
                                    <p class="text-sm text-zinc-600">POPIA-aware. QR-verifiable. Easy to use.</p>
                                </div>
                            </div>
                            
                            <ul class="space-y-4">
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[color:var(--nrapa-blue-wash)]">
                                        <svg class="size-5 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-zinc-900">Digital membership card</span>
                                        <p class="text-sm text-zinc-600">Mobile-friendly card with QR verification options.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[color:var(--nrapa-blue-wash)]">
                                        <svg class="size-5 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-zinc-900">Certificates &amp; endorsements</span>
                                        <p class="text-sm text-zinc-600">Download PDFs and track issued documents in one place.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[color:var(--nrapa-blue-wash)]">
                                        <svg class="size-5 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-zinc-900">Virtual Safe</span>
                                        <p class="text-sm text-zinc-600">Keep firearm details for license renewal reminders and linking to your loading bench data.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[color:var(--nrapa-blue-wash)]">
                                        <svg class="size-5 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-zinc-900">Virtual Loading Bench</span>
                                        <p class="text-sm text-zinc-600">Store reloading data and link loads to firearms.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[color:var(--nrapa-blue-wash)]">
                                        <svg class="size-5 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-zinc-900">Dedicated status &amp; activities</span>
                                        <p class="text-sm text-zinc-600">Submit activities and track progress toward requirements.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature Grid --}}
        @php
            $features = [
                [
                    'title' => 'My Digital Card + QR verification',
                    'desc' => 'Mobile-friendly membership card with QR-based verification options.',
                    'icon' => 'card',
                ],
                [
                    'title' => 'Certificates & Endorsements',
                    'desc' => 'View, verify, and download your issued certificates and endorsement letters.',
                    'icon' => 'documents',
                ],
                [
                    'title' => 'Virtual Safe (Firearm Registry)',
                    'desc' => 'Store firearm and license details for renewal reminders and linking loads in the Virtual Loading Bench.',
                    'icon' => 'safe',
                ],
                [
                    'title' => 'Virtual Loading Bench',
                    'desc' => 'Save reloading data, loads, and notes — and link them to your firearms.',
                    'icon' => 'bench',
                ],
                [
                    'title' => 'Shooting Activities',
                    'desc' => 'Submit activities, track approvals, and maintain your dedicated status requirements.',
                    'icon' => 'activities',
                ],
                [
                    'title' => 'Document Management',
                    'desc' => 'Upload required documents, track verification status, and manage expiry.',
                    'icon' => 'upload',
                ],
                [
                    'title' => 'Dedicated Status Workflow',
                    'desc' => 'Clear progress tracking for hunter/sport shooter requirements and eligibility.',
                    'icon' => 'workflow',
                ],
                [
                    'title' => 'Endorsement Requests',
                    'desc' => 'A guided, step-by-step process for endorsement letter requests and tracking.',
                    'icon' => 'endorsement',
                ],
                [
                    'title' => 'Knowledge Tests',
                    'desc' => 'Complete required tests online to unlock dedicated status and endorsements.',
                    'icon' => 'test',
                ],
                [
                    'title' => 'Learning Center',
                    'desc' => 'Articles and resources for safety, compliance, and best practices.',
                    'icon' => 'learning',
                ],
                [
                    'title' => 'Apple & Google Wallet',
                    'desc' => 'Add your membership card to your wallet for quick, offline-friendly access.',
                    'icon' => 'wallet',
                ],
                [
                    'title' => 'POPIA-compliant account controls',
                    'desc' => 'Built with privacy in mind, including account deletion request workflows.',
                    'icon' => 'privacy',
                ],
            ];
        @endphp

        <section id="features" class="py-20 sm:py-24 bg-white border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold tracking-wide text-[color:var(--nrapa-blue)]">Everything in one membership</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                        Built for real member workflows
                    </h2>
                    <p class="mt-4 text-base leading-7 text-zinc-600">
                        NRAPA Members is designed around what you actually do: keep records, stay compliant, maintain status, and generate the documents you need — fast.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($features as $feature)
                        <div class="group relative rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                            <div class="flex items-start gap-4">
                                <div class="flex size-11 items-center justify-center rounded-xl bg-[color:var(--nrapa-blue-wash)]">
                                    <svg class="size-6 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @switch($feature['icon'])
                                            @case('card')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9A2.5 2.5 0 0118.5 19h-13A2.5 2.5 0 013 16.5v-9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9h18"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 15h4"/>
                                                @break
                                            @case('documents')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v6h6"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6M9 17h6"/>
                                                @break
                                            @case('safe')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12a2 2 0 012 2v9a3 3 0 01-3 3H7a3 3 0 01-3-3V9a2 2 0 012-2z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7V5a3 3 0 016 0v2"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v4"/>
                                                @break
                                            @case('bench')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h8l-1 1v6l4 6a2 2 0 01-1.7 3H6.7A2 2 0 015 17l4-6V5L8 4z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9h4"/>
                                                @break
                                            @case('activities')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h10M9 9h10M9 13h10M9 17h6"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 6l1 1 2-2M5 10l1 1 2-2M5 14l1 1 2-2M5 18l1 1 2-2"/>
                                                @break
                                            @case('upload')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17v2a1 1 0 001 1h14a1 1 0 001-1v-2"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7l4-4 4 4"/>
                                                @break
                                            @case('workflow')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h6v6H7V7zM11 11h6v6h-6v-6z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10l2-2m-8 8l2-2"/>
                                                @break
                                            @case('endorsement')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6M9 12h6"/>
                                                @break
                                            @case('test')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6M9 12h6M9 16h4"/>
                                                @break
                                            @case('learning')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h14v16H6a2 2 0 01-2-2V6z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h8M8 12h8M8 16h6"/>
                                                @break
                                            @case('wallet')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 13h2"/>
                                                @break
                                            @case('privacy')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                                @break
                                        @endswitch
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-zinc-900">{{ $feature['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $feature['desc'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="py-20 sm:py-24 bg-zinc-50 border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold tracking-wide text-[color:var(--nrapa-blue)]">How it works</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">From application to endorsements</h2>
                    <p class="mt-4 text-base leading-7 text-zinc-600">
                        A clear workflow that matches the real-world process — with progress visibility at every step.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-5">
                    @php
                        $steps = [
                            ['title' => 'Apply / Renew', 'desc' => 'Register, choose a package, and submit your application.'],
                            ['title' => 'Upload documents', 'desc' => 'Provide required documents and track verification status.'],
                            ['title' => 'Activate membership', 'desc' => 'Get your digital card and initial certificates once approved.'],
                            ['title' => 'Maintain status', 'desc' => 'Complete tests, log activities, and monitor dedicated status requirements.'],
                            ['title' => 'Request endorsements', 'desc' => 'Use the guided wizard to request endorsement letters when eligible.'],
                        ];
                    @endphp
                    @foreach($steps as $idx => $step)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-xl bg-[color:var(--nrapa-orange-wash)] text-[color:var(--nrapa-orange)] font-extrabold">
                                    {{ $idx + 1 }}
                                </div>
                                <h3 class="text-base font-bold text-zinc-900">{{ $step['title'] }}</h3>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Key Features Section - Virtual Safe & Loading Bench --}}
        <section class="py-20 sm:py-24 bg-white border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-sm font-semibold tracking-wide text-[color:var(--nrapa-blue)]">Member tools spotlight</h2>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                        Virtual Safe &amp; Loading Bench
                    </p>
                    <p class="mt-6 text-lg leading-8 text-zinc-600">
                        Keep your firearm records and reloading data organised and ready for endorsements, compliance, and reminders.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    {{-- Virtual Safe --}}
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-gradient-to-r from-[color:rgba(11,78,162,0.10)] to-[color:rgba(245,130,32,0.10)] rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative rounded-2xl bg-zinc-50 border border-zinc-200 p-8 hover:border-[color:rgba(11,78,162,0.40)] transition-all">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex size-14 items-center justify-center rounded-xl bg-[color:var(--nrapa-blue)] shadow-sm">
                                    <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-zinc-900">Virtual Safe</h3>
                                    <p class="text-sm text-[color:var(--nrapa-blue)]">Digital firearm registry</p>
                                </div>
                            </div>
                            <p class="text-zinc-600 mb-6">
                                Keep a complete digital inventory of your firearms, including license details, expiry dates, and compliance documentation - all in one secure location.
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Track all your firearms in one place
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    License expiry notifications (18, 12, 6 months)
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Store license photos and documents
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Email & dashboard expiry alerts
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    {{-- Virtual Loading Bench --}}
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-gradient-to-r from-[color:rgba(245,130,32,0.10)] to-[color:rgba(11,78,162,0.10)] rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative rounded-2xl bg-zinc-50 border border-zinc-200 p-8 hover:border-[color:rgba(245,130,32,0.40)] transition-all">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex size-14 items-center justify-center rounded-xl bg-[color:var(--nrapa-orange)] shadow-sm">
                                    <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-zinc-900">Virtual Loading Bench</h3>
                                    <p class="text-sm text-[color:var(--nrapa-orange)]">Reloading data tracker</p>
                                </div>
                            </div>
                            <p class="text-zinc-600 mb-6">
                                Your personal reloading database. Save your load recipes, track powder charges, bullet weights, and velocities for consistent, accurate results every time.
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Store unlimited load recipes
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Track powder, primers, and bullets
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Record velocity and accuracy notes
                                </li>
                                <li class="flex items-center gap-3 text-sm text-zinc-700">
                                    <svg class="size-5 text-[color:var(--nrapa-blue)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
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
        <section id="pricing" class="py-20 sm:py-24 bg-zinc-50 border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-sm font-semibold tracking-wide text-[color:var(--nrapa-blue)]">Flexible options</h2>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                        Membership Packages
                    </p>
                    <p class="mt-6 text-lg leading-8 text-zinc-600">
                        Choose the membership that fits your needs
                    </p>
                </div>
                
                @if($membershipTypes->count() > 0)
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="rounded-2xl {{ $type->is_featured ? 'bg-white border-[color:rgba(11,78,162,0.25)] ring-2 ring-[color:rgba(11,78,162,0.10)] shadow-sm' : 'bg-white border-zinc-200 hover:border-[color:rgba(11,78,162,0.25)] shadow-sm' }} border p-8 relative overflow-hidden transition-colors">
                        @if($type->is_featured)
                        <div class="absolute top-0 right-0 bg-[color:var(--nrapa-orange)] text-xs font-bold text-zinc-900 px-4 py-1.5 rounded-bl-lg flex items-center gap-1">
                            <svg class="size-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Recommended
                        </div>
                        @endif
                        
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex size-12 items-center justify-center rounded-xl {{ $type->is_featured ? 'bg-[color:var(--nrapa-blue-wash)]' : 'bg-zinc-100' }}">
                                @if($type->dedicated_type === 'both')
                                <svg class="size-6 {{ $type->is_featured ? 'text-[color:var(--nrapa-blue)]' : 'text-zinc-700' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @elseif($type->dedicated_type === 'hunter')
                                <svg class="size-6 {{ $type->is_featured ? 'text-[color:var(--nrapa-blue)]' : 'text-zinc-700' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                @elseif($type->dedicated_type === 'sport')
                                <svg class="size-6 {{ $type->is_featured ? 'text-[color:var(--nrapa-blue)]' : 'text-zinc-700' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="2" stroke-width="2"/>
                                </svg>
                                @else
                                <svg class="size-6 {{ $type->is_featured ? 'text-[color:var(--nrapa-blue)]' : 'text-zinc-700' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                @endif
                            </div>
                            @if($type->dedicated_type)
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $type->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800' : ($type->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type) }}
                            </span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-extrabold text-zinc-900 mb-2">{{ $type->name }}</h3>
                        
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-3xl font-extrabold text-zinc-900">R{{ number_format($type->price, 0) }}</span>
                            <span class="text-sm text-zinc-600">/ {{ $type->duration_type === 'lifetime' ? 'once-off' : 'year' }}</span>
                        </div>
                        
                        <p class="text-sm text-zinc-600 mb-6">{{ $type->description }}</p>
                        
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Virtual Safe & Loading Bench
                            </li>
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                QR-Verified Certificates
                            </li>
                            @if($type->requires_knowledge_test)
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Knowledge Test Access
                            </li>
                            @endif
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Dedicated Status Support
                            </li>
                            @endif
                            <li class="flex items-center gap-2 text-sm text-zinc-700">
                                <svg class="size-4 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Learning Center Access
                            </li>
                        </ul>
                        
                        <a
                            href="{{ route('register') }}"
                            class="block w-full text-center rounded-lg {{ $type->is_featured ? 'bg-[color:var(--nrapa-orange)] hover:brightness-95 text-zinc-900 ring-1 ring-inset ring-black/10' : 'border border-[color:var(--nrapa-blue)] text-[color:var(--nrapa-blue)] hover:bg-[color:var(--nrapa-blue-wash)]' }} px-4 py-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-blue)]"
                        >
                            Get Started
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                {{-- Fallback static cards if no membership types configured --}}
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div class="rounded-2xl bg-white border border-zinc-200 p-8 shadow-sm hover:shadow-lg transition">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-100 mb-4">
                            <svg class="size-6 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 mb-2">Dedicated Hunter</h3>
                        <p class="text-sm text-zinc-600">Full membership for dedicated hunters with all platform features and hunting-specific content.</p>
                    </div>
                    
                    <div class="rounded-2xl bg-white border border-[color:rgba(11,78,162,0.25)] p-8 relative overflow-hidden shadow-sm">
                        <div class="absolute top-0 right-0 bg-[color:var(--nrapa-orange)] text-xs font-bold text-zinc-900 px-3 py-1 rounded-bl-lg">Recommended</div>
                        <div class="flex size-12 items-center justify-center rounded-xl bg-[color:var(--nrapa-blue-wash)] mb-4">
                            <svg class="size-6 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 mb-2">Hunter &amp; Sport Shooter</h3>
                        <p class="text-sm text-zinc-600">Complete access for both dedicated hunters and sport shooters with all content and features.</p>
                    </div>
                    
                    <div class="rounded-2xl bg-white border border-zinc-200 p-8 shadow-sm hover:shadow-lg transition">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-100 mb-4">
                            <svg class="size-6 text-[color:var(--nrapa-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                <circle cx="12" cy="12" r="2" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 mb-2">Dedicated Sport Shooter</h3>
                        <p class="text-sm text-zinc-600">Full membership for dedicated sport shooters with all platform features and sport-specific content.</p>
                    </div>
                </div>
                @endif
            </div>
        </section>

        {{-- Trust / Compliance band --}}
        <section class="py-16 bg-white border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-bold text-zinc-900">POPIA-aware by design</p>
                        <p class="mt-2 text-sm text-zinc-600">Privacy-focused account controls and deletion request workflows.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-bold text-zinc-900">QR verifiable documents</p>
                        <p class="mt-2 text-sm text-zinc-600">Fast verification options for certificates and membership status.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-bold text-zinc-900">Approval workflows</p>
                        <p class="mt-2 text-sm text-zinc-600">Documents, activities, and requests include clear statuses and outcomes.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-bold text-zinc-900">Member-first UX</p>
                        <p class="mt-2 text-sm text-zinc-600">Designed to reduce admin friction and keep members informed.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="py-20 bg-zinc-50 border-t border-zinc-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-white p-10 shadow-sm sm:p-14">
                    <div class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-[color:var(--nrapa-blue-wash)] blur-3xl"></div>
                    <div class="pointer-events-none absolute -left-24 -bottom-24 size-80 rounded-full bg-[color:var(--nrapa-orange-wash)] blur-3xl"></div>

                    <div class="relative grid gap-10 lg:grid-cols-2 lg:items-center">
                        <div>
                            <h2 class="text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                                Ready to join NRAPA?
                            </h2>
                            <p class="mt-4 text-lg leading-8 text-zinc-600">
                                Create your account and manage your membership, certificates, activities, and endorsements — all from one secure portal.
                            </p>
                            <div class="mt-8 flex flex-wrap items-center gap-4">
                                @auth
                                    <a
                                        href="{{ route('dashboard') }}"
                                        class="rounded-lg bg-[color:var(--nrapa-blue)] px-6 py-3 text-sm font-semibold text-white shadow-sm hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-orange)] transition"
                                    >
                                        Go to Dashboard
                                    </a>
                                    <a href="#pricing" class="text-sm font-semibold text-[color:var(--nrapa-blue)] hover:opacity-80 transition">
                                        View membership packages <span aria-hidden="true">→</span>
                                    </a>
                                @else
                                    <a
                                        href="{{ route('register') }}"
                                        class="rounded-lg bg-[color:var(--nrapa-orange)] px-6 py-3 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-black/10 hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--nrapa-blue)] transition"
                                    >
                                        Create my account
                                    </a>
                                    <a href="{{ route('login') }}" class="text-sm font-semibold text-[color:var(--nrapa-blue)] hover:opacity-80 transition">
                                        Already a member? Log in <span aria-hidden="true">→</span>
                                    </a>
                                @endauth
                            </div>
                        </div>

                        <div class="relative rounded-2xl border border-zinc-200 bg-zinc-50 p-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-10 object-contain" />
                                <div>
                                    <p class="text-sm font-extrabold text-zinc-900">NRAPA Members Portal</p>
                                    <p class="text-sm text-zinc-600">Digital card • Certificates • Activities • Endorsements</p>
                                </div>
                            </div>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                    <p class="text-sm font-bold text-zinc-900">Track progress</p>
                                    <p class="mt-1 text-sm text-zinc-600">Know what’s pending and what’s approved.</p>
                                </div>
                                <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                    <p class="text-sm font-bold text-zinc-900">Download PDFs</p>
                                    <p class="mt-1 text-sm text-zinc-600">Certificates and letters when you need them.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-white border-t border-zinc-200">
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-8 object-contain" />
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
