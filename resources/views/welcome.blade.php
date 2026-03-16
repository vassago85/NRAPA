<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Google Search Console verification — replace PLACEHOLDER with actual code --}}
        <meta name="google-site-verification" content="ofpn02fgz8G9-u_lZnCwMckrIFkddbLv68ZdBOMEMu8">
        {{-- Google Analytics 4 — replace G-XXXXXXXXXX with actual measurement ID --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-JV2NSWMYTQ"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-JV2NSWMYTQ');</script>
        <title>NRAPA — Dedicated Status Simplified | SAPS Accredited Association</title>
        <meta name="description" content="NRAPA is a SAPS-accredited hunting and sport shooting association. Obtain dedicated status, manage your licences, and stay compliant with the Firearms Control Act. A division of Ranyati Group.">
        <link rel="canonical" href="{{ url('/') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="NRAPA">
        <meta property="og:title" content="NRAPA — Dedicated Status Simplified">
        <meta property="og:description" content="SAPS-accredited association for dedicated sport shooters and hunters. Membership, endorsements, certificates, and compliance — all in one portal.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('nrapa-icon.png') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="NRAPA — Dedicated Status Simplified">
        <meta name="twitter:description" content="SAPS-accredited association for dedicated sport shooters and hunters. Membership, endorsements, certificates, and compliance.">
        <meta name="twitter:image" content="{{ asset('nrapa-icon.png') }}">
        <link rel="icon" href="/nrapa-icon.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "NRAPA — National Rifle and Pistol Association of South Africa",
            "alternateName": "NRAPA",
            "url": "{{ url('/') }}",
            "logo": "{{ asset('nrapa-icon.png') }}",
            "description": "SAPS-accredited hunting and sport shooting association offering dedicated status, membership certificates, endorsements, and compliance support under the Firearms Control Act.",
            "parentOrganization": {
                "@type": "Organization",
                "name": "Ranyati Firearm Motivations (Pty) Ltd",
                "url": "https://ranyati.co.za"
            },
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+27-87-151-0987",
                "email": "info@nrapa.co.za",
                "contactType": "customer service",
                "areaServed": "ZA",
                "availableLanguage": ["English", "Afrikaans"]
            },
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "241 Jean Avenue",
                "addressLocality": "Centurion",
                "addressRegion": "Gauteng",
                "addressCountry": "ZA"
            },
            "sameAs": [
                "https://ranyati.co.za"
            ]
        }
        </script>
        <style>
            body { font-family: 'Inter', system-ui, sans-serif; background: #020810; }

            .hero-section {
                background:
                    radial-gradient(ellipse 90% 70% at 50% 30%, rgba(11,78,162,0.45) 0%, transparent 60%),
                    radial-gradient(ellipse 60% 40% at 80% 20%, rgba(11,78,162,0.2) 0%, transparent 50%),
                    radial-gradient(ellipse 50% 35% at 20% 60%, rgba(6,30,60,0.4) 0%, transparent 50%),
                    linear-gradient(180deg, #0a3a78 0%, #072e60 30%, #051d3d 60%, #030f1e 85%, #020810 100%);
            }

            .emblem-ring {
                border: 1px solid rgba(255,255,255,0.04);
                border-radius: 50%;
                position: absolute;
            }

            .card-feature {
                background: linear-gradient(180deg, rgba(12,35,65,0.7) 0%, rgba(8,22,42,0.8) 100%);
                border: 1px solid rgba(255,255,255,0.06);
                position: relative;
                overflow: hidden;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.15, 1);
            }
            .card-feature::after {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 3px;
                background: var(--accent, #0B4EA2);
                opacity: 0;
                transition: opacity 0.4s ease;
            }
            .card-feature:hover {
                border-color: rgba(255,255,255,0.12);
                transform: translateY(-8px);
                box-shadow: 0 40px 80px -20px rgba(0,0,0,0.6), 0 0 1px 0 rgba(255,255,255,0.1);
            }
            .card-feature:hover::after { opacity: 1; }
            .card-feature:hover .icon-box {
                transform: scale(1.1);
                box-shadow: 0 0 32px -8px var(--accent, #0B4EA2);
            }

            .icon-box { transition: transform 0.4s ease, box-shadow 0.4s ease; }

            .card-pricing {
                background: linear-gradient(180deg, rgba(12,35,65,0.6) 0%, rgba(8,22,42,0.75) 100%);
                border: 1px solid rgba(255,255,255,0.06);
                position: relative;
                overflow: visible;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.15, 1);
            }
            .card-pricing:hover {
                border-color: rgba(255,255,255,0.12);
                transform: translateY(-8px);
                box-shadow: 0 40px 80px -20px rgba(0,0,0,0.6);
            }
            .card-pricing.featured {
                border-color: rgba(11,78,162,0.4);
                box-shadow: 0 0 60px -20px rgba(11,78,162,0.25);
            }
            .card-pricing.featured:hover {
                border-color: rgba(11,78,162,0.6);
            }

            .btn-cta {
                background: linear-gradient(135deg, #F58220 0%, #d46f16 100%);
                box-shadow: 0 2px 12px -2px rgba(245,130,32,0.4), 0 0 0 1px rgba(245,130,32,0.15);
                transition: all 0.25s ease;
            }
            .btn-cta:hover {
                box-shadow: 0 6px 24px -4px rgba(245,130,32,0.5), 0 0 0 1px rgba(245,130,32,0.25);
                transform: translateY(-2px);
            }

            .btn-outline {
                border: 1px solid rgba(255,255,255,0.18);
                transition: all 0.25s ease;
            }
            .btn-outline:hover {
                border-color: rgba(255,255,255,0.35);
                background: rgba(255,255,255,0.05);
            }

            .btn-blue {
                background: linear-gradient(135deg, #0B4EA2 0%, #083A7A 100%);
                box-shadow: 0 2px 12px -2px rgba(11,78,162,0.4);
                transition: all 0.25s ease;
            }
            .btn-blue:hover {
                box-shadow: 0 6px 24px -4px rgba(11,78,162,0.5);
                transform: translateY(-2px);
            }

            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .anim   { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
            .anim-1 { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.1s forwards; opacity: 0; }
            .anim-2 { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.2s forwards; opacity: 0; }
            .anim-3 { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.3s forwards; opacity: 0; }
            .anim-4 { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.45s forwards; opacity: 0; }
            .anim-5 { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.6s forwards; opacity: 0; }

            .header-pills, .header-contact { display: none; }
            .header-logo { flex: 1; }
            .header-auth { display: flex; flex-shrink: 0; }
            .footer-grid { display: flex; flex-direction: column; gap: 32px; padding: 40px 0; }
            .footer-center { align-items: flex-start; text-align: left; }
            .footer-center .footer-pills { align-items: flex-start; }
            .footer-right { align-items: flex-start; }
            .footer-right-inner { align-items: flex-start; }

            @media (min-width: 768px) {
                .header-pills { display: flex; }
                .header-contact { display: flex; }
                .header-logo { width: 25%; flex: none; }
                .header-auth { display: flex; flex-shrink: 0; }
                .footer-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; padding: 56px 0; }
                .footer-center { align-items: center; text-align: center; }
                .footer-center .footer-pills { align-items: center; }
                .footer-right { align-items: flex-end; }
                .footer-right-inner { align-items: flex-end; }
            }
        </style>
    </head>
    <body class="min-h-screen antialiased text-white">

        {{-- Header --}}
        <header style="position: absolute; top: 0; left: 0; right: 0; z-index: 50;">
            <div style="max-width: 80rem; margin: 0 auto; padding: 0 1.5rem;">
                <div style="display: flex; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.04);">
                    {{-- Left: Logo --}}
                    <div class="header-logo" style="flex-shrink: 0;">
                        <a href="https://ranyati.co.za">
                            <img src="{{ asset('logo-ranyatigroup-white_text.png') }}" alt="Ranyati Group" style="height: 26px; width: auto; object-fit: contain;" />
                        </a>
                    </div>
                    {{-- Center: Division pill buttons --}}
                    <div class="header-pills" style="width: 50%; align-items: center; justify-content: center; gap: 12px;">
                        <a href="https://motivations.ranyati.co.za" title="Motivations" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(245,130,32,0.1); box-shadow: inset 0 0 0 1px rgba(245,130,32,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(245,130,32,0.2)'" onmouseout="this.style.background='rgba(245,130,32,0.1)'">
                            <img src="{{ asset('logo-ranyati_motivations-white-text.png') }}" alt="Motivations" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain; opacity: 0.6;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" />
                        </a>
                        <a href="https://nrapa.ranyati.co.za" title="NRAPA" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(56,189,248,0.1); box-shadow: inset 0 0 0 1px rgba(56,189,248,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(56,189,248,0.2)'" onmouseout="this.style.background='rgba(56,189,248,0.1)'">
                            <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain; opacity: 0.6;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" />
                        </a>
                        <a href="https://storage.ranyati.co.za" title="Storage" style="display: inline-flex; align-items: center; justify-content: center; width: 144px; height: 36px; padding: 6px; border-radius: 10px; background: rgba(52,211,153,0.1); box-shadow: inset 0 0 0 1px rgba(52,211,153,0.15); transition: background 0.2s; overflow: hidden;" onmouseover="this.style.background='rgba(52,211,153,0.2)'" onmouseout="this.style.background='rgba(52,211,153,0.1)'">
                            <img src="{{ asset('logo-ranyati_storage-white_text.png') }}" alt="Storage" style="max-height: 24px; max-width: 132px; width: auto; height: auto; object-fit: contain; opacity: 0.6;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" />
                        </a>
                    </div>
                    {{-- Right: Contact + Auth --}}
                    <div class="header-contact" style="width: 25%; flex-direction: column; align-items: flex-end; gap: 0;">
                        <a href="tel:+27871510987" style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                            <svg style="width: 11px; height: 11px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            +27 87 151 0987
                        </a>
                        <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.06); margin: 5px 0;"></div>
                        <a href="mailto:info@nrapa.co.za" style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                            <svg style="width: 11px; height: 11px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            info@nrapa.co.za
                        </a>
                    </div>
                    {{-- Auth (always visible) --}}
                    <div class="header-auth" style="margin-left: 16px; align-items: center; gap: 8px; flex-shrink: 0;">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-cta rounded-lg px-4 py-2 text-[12px] font-bold text-white tracking-wide">
                                Dashboard <span aria-hidden="true">&rarr;</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" style="font-size: 12px; font-weight: 500; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-cta rounded-lg px-4 py-2 text-[12px] font-bold text-white tracking-wide">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        {{-- Hero --}}
        <section class="hero-section relative min-h-[85vh] flex flex-col items-center justify-center overflow-hidden">

            {{-- Emblem rings --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                <div class="emblem-ring w-[400px] h-[400px] sm:w-[550px] sm:h-[550px]" style="border-color: rgba(255,255,255,0.03);"></div>
            </div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                <div class="emblem-ring w-[600px] h-[600px] sm:w-[800px] sm:h-[800px]" style="border-color: rgba(255,255,255,0.02);"></div>
            </div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                <div class="emblem-ring w-[900px] h-[900px] sm:w-[1100px] sm:h-[1100px]" style="border-color: rgba(255,255,255,0.015);"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-3xl px-6 text-center lg:px-8 pt-28 pb-20 sm:pb-24">

                {{-- NRAPA Logo --}}
                <div class="anim" style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.03); padding: 3px 20px;">
                    <img src="{{ asset('logo-nrapa-white.png') }}" alt="NRAPA" style="height: 74px; width: auto; object-fit: contain; opacity: 1;" />
                </div>

                {{-- Heading --}}
                <h1 class="mt-10 text-[2.5rem] font-black leading-[1.05] tracking-[-0.03em] text-white sm:text-[3.25rem] lg:text-[4rem] anim-1">
                    Dedicated Status<br> Simplified
                </h1>
                <p class="mt-4 text-[13px] font-semibold uppercase tracking-[0.25em] text-[#F58220]/70 anim-1">
                    A Division of Ranyati Group
                </p>

                {{-- Sub text --}}
                <p class="mx-auto mt-7 max-w-lg text-[15px] leading-[1.8] text-white/45 anim-2">
                    We help you obtain dedicated status, manage your licences, and stay compliant with the Firearms Control Act &mdash; so you can focus on what you love, whether it's on the range or in the bush.
                </p>

                {{-- CTAs --}}
                <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center sm:gap-4 anim-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-cta rounded-xl px-8 py-3.5 text-[13px] font-bold text-white tracking-wide">
                            Go to Dashboard
                        </a>
                        <a href="#features" class="btn-outline rounded-xl px-8 py-3.5 text-[13px] font-semibold text-white/50 hover:text-white tracking-wide">
                            View features <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-cta rounded-xl px-8 py-3.5 text-[13px] font-bold text-white tracking-wide">
                            Become a Member
                        </a>
                        <a href="{{ route('login') }}" class="btn-outline rounded-xl px-8 py-3.5 text-[13px] font-semibold text-white/50 hover:text-white tracking-wide">
                            Already a member? Sign in
                        </a>
                    @endauth
                </div>

                {{-- Trust strip --}}
                <div class="mt-14 anim-4">
                    <div class="inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-2 rounded-2xl border border-white/[0.04] bg-white/[0.02] px-6 py-3 backdrop-blur-sm">
                        <div class="flex items-center gap-2 px-2 text-[11.5px] font-medium tracking-wide text-white/30">
                            <svg class="size-3.5 text-[#F58220]/50" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            SAPS Accredited
                        </div>
                        <span class="hidden sm:block h-3 w-px bg-white/[0.06]"></span>
                        <div class="flex items-center gap-2 px-2 text-[11.5px] font-medium tracking-wide text-white/30">
                            <svg class="size-3.5 text-[#F58220]/50" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            FAR 1300122 &amp; 1300127
                        </div>
                        <span class="hidden sm:block h-3 w-px bg-white/[0.06]"></span>
                        <div class="flex items-center gap-2 px-2 text-[11.5px] font-medium tracking-wide text-white/30">
                            <svg class="size-3.5 text-[#F58220]/50" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/></svg>
                            POPIA Compliant
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Credentials Bar --}}
        <section class="relative bg-[#020810] border-t border-white/[0.04] py-14">
            <div class="mx-auto max-w-5xl px-6">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">SAPS</p>
                        <p class="mt-1 text-[12px] tracking-wide text-white/30">Accredited</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">1300122</p>
                        <p class="mt-1 text-[12px] tracking-wide text-white/30">FAR Sport</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">1300127</p>
                        <p class="mt-1 text-[12px] tracking-wide text-white/30">FAR Hunting</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-white">POPIA</p>
                        <p class="mt-1 text-[12px] tracking-wide text-white/30">Compliant</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="bg-[#020810] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/[0.06] bg-white/[0.03] px-5 py-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">Members Portal</span>
                    </div>
                    <p class="mt-6 text-[2rem] font-black leading-[1.1] tracking-[-0.02em] text-white sm:text-[2.5rem]">
                        Manage your membership online
                    </p>
                    <p class="mx-auto mt-5 max-w-xl text-[15px] leading-[1.8] text-white/40">
                        Whether you're building your activity record or already hold dedicated status &mdash; we're with you every step of the way. Here's what you get as a member.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- 1. Endorsements & Dedicated Status --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #0B4EA2;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#0B4EA2]/10 ring-1 ring-[#0B4EA2]/15">
                            <svg class="size-6 text-[#0B4EA2]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Endorsements &amp; Dedicated Licences</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">We assist you in obtaining Dedicated Sport and Hunter status &mdash; allowing you to hold additional firearms and increased ammunition beyond the standard limits.</p>
                    </div>

                    {{-- 2. Certificates --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #0B4EA2;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#0B4EA2]/10 ring-1 ring-[#0B4EA2]/15">
                            <svg class="size-6 text-[#0B4EA2]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h6M9 13h6M9 17h6"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Certificates &amp; Endorsements</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">Download your membership certificate, endorsement letters, and competency documents. All QR-verifiable for SAPS and DFO submissions.</p>
                    </div>

                    {{-- 3. Virtual Safe --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #0B4EA2;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#0B4EA2]/10 ring-1 ring-[#0B4EA2]/15">
                            <svg class="size-6 text-[#0B4EA2]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Virtual Safe</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">Keep a digital record of your firearms, license details, and barrel life. Get reminders before your licenses expire.</p>
                    </div>

                    {{-- 4. Loading Bench --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #F58220;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#F58220]/10 ring-1 ring-[#F58220]/15">
                            <svg class="size-6 text-[#F58220]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8l-1 1v6l4 6a2 2 0 01-1.7 3H6.7A2 2 0 015 17l4-6V5L8 4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Virtual Loading Bench</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">Store load recipes, track component inventory, run ladder tests, and calculate cost per round.</p>
                    </div>

                    {{-- 5. Activities --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #F58220;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#F58220]/10 ring-1 ring-[#F58220]/15">
                            <svg class="size-6 text-[#F58220]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M9 17h6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 6l1 1 2-2M5 10l1 1 2-2M5 14l1 1 2-2M5 18l1 1 2-2"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Shooting Activities</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">Log your range sessions and hunting activities. Track rounds fired and build the activity record required for dedicated status.</p>
                    </div>

                    {{-- 6. Digital Membership Card --}}
                    <div class="card-feature group flex flex-col rounded-2xl p-8 sm:p-9" style="--accent: #F58220;">
                        <div class="icon-box flex size-14 items-center justify-center rounded-2xl bg-[#F58220]/10 ring-1 ring-[#F58220]/15">
                            <svg class="size-6 text-[#F58220]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9A2.5 2.5 0 0118.5 19h-13A2.5 2.5 0 013 16.5v-9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M7 15h4"/>
                            </svg>
                        </div>
                        <h3 class="mt-7 text-lg font-bold tracking-tight text-white">Digital Membership Card</h3>
                        <p class="mt-3 flex-1 text-[14px] leading-[1.75] text-white/40">QR-verifiable membership card on your phone. Present it at the range, to SAPS, or any official who needs to confirm your membership.</p>
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
        <section id="pricing" class="bg-[#020810] border-t border-white/[0.04] py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[#F58220]/20 bg-[#F58220]/5 px-5 py-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#F58220]/70">Membership</span>
                    </div>
                    <p class="mt-6 text-[2rem] font-black leading-[1.1] tracking-[-0.02em] text-white sm:text-[2.5rem]">
                        Become a member
                    </p>
                    <p class="mx-auto mt-5 max-w-xl text-[15px] leading-[1.8] text-white/40">
                        Choose the membership that fits your needs. All packages include full portal access and digital certificates.
                    </p>
                </div>

                @if($membershipTypes->count() > 0)
                <div class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto">
                    @foreach($membershipTypes as $type)
                    <div class="card-pricing {{ $type->is_featured ? 'featured' : '' }} relative rounded-2xl p-8 flex flex-col">
                        @if($type->is_featured)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-[#F58220] to-[#d46f16] px-4 py-1 text-[10px] font-bold text-white shadow-sm tracking-wide uppercase">
                            Most Popular
                        </div>
                        @endif

                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex size-11 items-center justify-center rounded-xl {{ $type->is_featured ? 'bg-[#0B4EA2]/15 ring-1 ring-[#0B4EA2]/20' : 'bg-white/[0.04] ring-1 ring-white/[0.06]' }}">
                                @if($type->dedicated_type === 'both')
                                <svg class="size-5 {{ $type->is_featured ? 'text-[#0B4EA2]' : 'text-white/30' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @elseif($type->dedicated_type === 'hunter')
                                <svg class="size-5 {{ $type->is_featured ? 'text-[#0B4EA2]' : 'text-white/30' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                @elseif($type->dedicated_type === 'sport')
                                <svg class="size-5 {{ $type->is_featured ? 'text-[#0B4EA2]' : 'text-white/30' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" />
                                </svg>
                                @else
                                <svg class="size-5 {{ $type->is_featured ? 'text-[#0B4EA2]' : 'text-white/30' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                @endif
                            </div>
                            @if($type->dedicated_type)
                            <span class="text-[10px] font-bold uppercase tracking-[0.15em] px-2.5 py-1 rounded-full {{ $type->dedicated_type === 'both' ? 'bg-purple-500/10 text-purple-400/70 ring-1 ring-purple-500/15' : ($type->dedicated_type === 'hunter' ? 'bg-amber-500/10 text-amber-400/70 ring-1 ring-amber-500/15' : 'bg-sky-500/10 text-sky-400/70 ring-1 ring-sky-500/15') }}">
                                {{ $type->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($type->dedicated_type) }}
                            </span>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-white">{{ $type->name }}</h3>

                        <div class="mt-3">
                            @if($type->hasUpgradeFee() && $basicType)
                            @php $totalSignup = ($basicType->initial_price ?? 0) + ($type->upgrade_price ?? 0); @endphp
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold tracking-tight text-white">R{{ number_format($totalSignup, 0) }}</span>
                                <span class="text-[13px] font-medium text-white/30">sign-up</span>
                            </div>
                            <p class="text-[13px] text-white/35 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                            @else
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold tracking-tight text-white">R{{ number_format($type->initial_price, 0) }}</span>
                                <span class="text-[13px] font-medium text-white/30">/ {{ $type->duration_type === 'lifetime' ? 'once-off' : 'year' }}</span>
                            </div>
                            @if($type->renewal_price > 0 && $type->renewal_price != $type->initial_price)
                            <p class="text-[13px] text-white/35 mt-1">Renewal: R{{ number_format($type->renewal_price, 0) }}/year</p>
                            @endif
                            @endif
                        </div>

                        <p class="mt-4 text-[14px] leading-[1.75] text-white/40">{{ $type->description }}</p>

                        <div class="mt-auto pt-6">
                        <div class="mb-6 h-px bg-white/[0.06]"></div>

                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Virtual Safe
                            </li>
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Virtual Loading Bench
                            </li>
                            @endif
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                QR-Verified Certificates
                            </li>
                            @if($type->requires_knowledge_test)
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Knowledge Test Access
                            </li>
                            @endif
                            @if($type->allows_dedicated_status)
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Dedicated Status Support
                            </li>
                            @endif
                            <li class="flex items-center gap-2.5 text-[13px] text-white/45">
                                <svg class="size-4 text-[#F58220]/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                                Learning Center Access
                            </li>
                        </ul>

                        <a href="{{ route('register') }}" class="{{ $type->is_featured ? 'btn-cta' : 'btn-outline text-white/50 hover:text-white' }} mt-8 block w-full text-center rounded-xl px-4 py-3 text-[13px] font-bold tracking-wide">
                            Get Started
                        </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="mt-16 text-center">
                    <p class="text-white/40">Membership packages will be listed here shortly.</p>
                    <a href="{{ route('register') }}" class="btn-cta mt-6 inline-block rounded-xl px-8 py-3.5 text-[13px] font-bold text-white tracking-wide">
                        Register Now
                    </a>
                </div>
                @endif
            </div>
        </section>

        {{-- CTA Banner --}}
        <section class="hero-section relative overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                <div class="emblem-ring w-[600px] h-[600px] sm:w-[800px] sm:h-[800px]" style="border-color: rgba(255,255,255,0.02);"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-4xl px-6 py-24 text-center">
                <h2 class="text-[2rem] font-black leading-[1.1] tracking-[-0.02em] text-white sm:text-[2.5rem]">
                    We're in your corner
                </h2>
                <p class="mx-auto mt-5 max-w-xl text-[15px] leading-[1.8] text-white/45">
                    Whether you're working toward dedicated status or already there &mdash; NRAPA walks the journey with you. Endorsements, certificates, compliance, and the support you need to enjoy responsible firearm ownership.
                </p>
                <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center sm:gap-4">
                    <a href="{{ route('register') }}" class="btn-cta rounded-xl px-8 py-3.5 text-[13px] font-bold text-white tracking-wide">
                        Apply for Membership
                    </a>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer style="background: #020810; border-top: 1px solid rgba(255,255,255,0.04);">
            <div style="max-width: 80rem; margin: 0 auto; padding: 0 24px;">
                <div class="footer-grid">
                    {{-- Left: Ranyati Group --}}
                    <div style="text-align: left;">
                        <img src="{{ asset('logo-ranyatigroup-white_text.png') }}" alt="Ranyati Group" style="height: 32px; width: auto; object-fit: contain;" />
                        <p style="margin-top: 16px; font-size: 13px; line-height: 1.7; color: rgba(255,255,255,0.25);">
                            Specialist firearm administration services since 2006.<br>
                            Trading as Ranyati Firearm Motivations (Pty) Ltd.
                        </p>
                    </div>
                    {{-- Center: Divisions --}}
                    <div class="footer-center" style="display: flex; flex-direction: column;">
                        <h4 style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(255,255,255,0.2);">Divisions</h4>
                        <div class="footer-pills" style="display: flex; flex-direction: column; gap: 8px; margin-top: 16px;">
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
                    <div class="footer-right" style="display: flex; flex-direction: column;">
                        <h4 style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(255,255,255,0.2);">Contact</h4>
                        <div class="footer-right-inner" style="margin-top: 16px; display: flex; flex-direction: column; gap: 0;">
                            <a href="tel:+27871510987" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.15);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                +27 87 151 0987
                            </a>
                            <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0;"></div>
                            <a href="mailto:info@nrapa.co.za" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.15);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                info@nrapa.co.za
                            </a>
                        </div>
                    </div>
                </div>
                <div style="border-top: 1px solid rgba(255,255,255,0.04); padding: 24px 0; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <a href="{{ route('terms-and-conditions') }}" style="font-size: 11px; color: rgba(255,255,255,0.3); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">Terms &amp; Conditions</a>
                        <span style="color: rgba(255,255,255,0.1);">&bull;</span>
                        <a href="{{ route('privacy-policy') }}" style="font-size: 11px; color: rgba(255,255,255,0.3); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">Privacy Policy</a>
                    </div>
                    <p style="text-align: center; font-size: 10px; letter-spacing: 0.1em; color: rgba(255,255,255,0.15);">
                        &copy; {{ date('Y') }} Ranyati Firearm Motivations (Pty) Ltd. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
