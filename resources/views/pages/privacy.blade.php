<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy - NRAPA</title>
        <link rel="icon" href="/nrapa-logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/nrapa-logo.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .hero-gradient {
                background: linear-gradient(135deg, #061e3c 0%, #0B4EA2 50%, #083A7A 100%);
            }
            .hero-pattern {
                background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
                background-size: 40px 40px;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">

        {{-- Header --}}
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#061e3c]/80 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8" aria-label="Global">
                <a href="/" class="flex items-center gap-3 group">
                    <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-9 object-contain transition group-hover:scale-105" />
                    <span class="text-lg font-bold tracking-tight text-white">NRAPA</span>
                </a>
                <div class="hidden sm:flex items-center gap-8">
                    <a href="{{ route('home') }}#features" class="text-sm font-medium text-zinc-300 hover:text-white transition">Features</a>
                    <a href="{{ route('home') }}#pricing" class="text-sm font-medium text-zinc-300 hover:text-white transition">Packages</a>
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

        {{-- Page Header --}}
        <section class="hero-gradient relative overflow-hidden pt-28 pb-16 sm:pt-32 sm:pb-20">
            <div class="hero-pattern absolute inset-0"></div>
            <div class="relative mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Privacy Policy</h1>
                <p class="mt-3 text-base text-zinc-300">Last updated: {{ now()->format('d F Y') }}</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                    <path d="M0 60L1440 60L1440 0C1440 0 1080 40 720 40C360 40 0 0 0 0L0 60Z" fill="white"/>
                </svg>
            </div>
        </section>

        {{-- Content --}}
        <section class="bg-white py-16">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <div class="prose prose-zinc max-w-none prose-headings:text-zinc-900 prose-headings:font-bold prose-h2:text-xl prose-h2:mt-10 prose-h2:mb-4 prose-h3:text-lg prose-h3:mt-8 prose-h3:mb-3 prose-p:leading-relaxed prose-li:leading-relaxed prose-a:text-nrapa-blue hover:prose-a:text-nrapa-blue-dark">

                    <h2>1. Introduction</h2>
                    <p>The National Rifle and Pistol Association of South Africa ("NRAPA", "we", "us", "our") is committed to protecting your privacy and personal information in accordance with the <strong>Protection of Personal Information Act 4 of 2013 ("POPIA")</strong> and other applicable South African legislation.</p>
                    <p>This Privacy Policy explains how we collect, use, store, share, and protect your personal information when you use the NRAPA members portal ("Portal") and our related services.</p>
                    <p>By using the Portal or providing us with your personal information, you acknowledge that you have read and understood this Privacy Policy and consent to the processing of your personal information as described herein.</p>

                    <h2>2. Responsible Party</h2>
                    <p>For purposes of POPIA, the responsible party is:</p>
                    <ul>
                        <li><strong>Organisation:</strong> National Rifle and Pistol Association of South Africa (NRAPA)</li>
                        <li><strong>Address:</strong> 241 Jean Avenue, Centurion, Gauteng, South Africa</li>
                        <li><strong>Email:</strong> <a href="mailto:info@nrapa.co.za">info@nrapa.co.za</a></li>
                        <li><strong>FAR Numbers:</strong> 1300122 (Sport) &amp; 1300127 (Hunting)</li>
                    </ul>

                    <h2>3. Personal Information We Collect</h2>
                    <p>We collect and process the following categories of personal information:</p>

                    <h3>3.1 Information You Provide</h3>
                    <ul>
                        <li><strong>Identity information:</strong> Full name, surname, title, South African ID number or passport number, date of birth, gender.</li>
                        <li><strong>Contact information:</strong> Email address, mobile phone number, physical address, postal address.</li>
                        <li><strong>Membership information:</strong> Membership type, membership number, application date, renewal history, membership status.</li>
                        <li><strong>Financial information:</strong> Payment records, proof of payment, transaction history (we do not store credit card numbers).</li>
                        <li><strong>Firearm information:</strong> Firearm make, model, calibre, serial number, licence number, licence expiry dates (as recorded in your Virtual Safe).</li>
                        <li><strong>Activity records:</strong> Shooting activity logs, hunting activity records, scores, range session details, competition participation.</li>
                        <li><strong>Endorsement information:</strong> Endorsement applications, supporting motivation, firearm details for endorsement requests.</li>
                        <li><strong>Supporting documents:</strong> ID copies, proof of residence, competency certificates, photographs, and other documents uploaded to the Portal.</li>
                    </ul>

                    <h3>3.2 Information Collected Automatically</h3>
                    <ul>
                        <li><strong>Technical data:</strong> IP address, browser type, device type, operating system.</li>
                        <li><strong>Usage data:</strong> Pages visited, features used, timestamps, session duration.</li>
                        <li><strong>Cookies:</strong> Session cookies to maintain your logged-in state and security tokens. See Section 11 for our Cookie Policy.</li>
                    </ul>

                    <h2>4. Purpose of Processing</h2>
                    <p>We process your personal information for the following lawful purposes:</p>

                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left font-bold">Purpose</th>
                                <th class="text-left font-bold">Legal Basis (POPIA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Processing membership applications and renewals</td>
                                <td>Contract / Consent</td>
                            </tr>
                            <tr>
                                <td>Issuing membership certificates and endorsement letters</td>
                                <td>Contract / Legal obligation</td>
                            </tr>
                            <tr>
                                <td>Administering Dedicated Hunter and Sport Shooter status</td>
                                <td>Legal obligation (FCA)</td>
                            </tr>
                            <tr>
                                <td>Maintaining activity records for compliance</td>
                                <td>Legal obligation (FCA)</td>
                            </tr>
                            <tr>
                                <td>Communicating with you about your membership</td>
                                <td>Contract / Legitimate interest</td>
                            </tr>
                            <tr>
                                <td>Sending renewal reminders and important notices</td>
                                <td>Legitimate interest</td>
                            </tr>
                            <tr>
                                <td>Reporting to SAPS as required by the FCA</td>
                                <td>Legal obligation</td>
                            </tr>
                            <tr>
                                <td>Processing endorsement applications</td>
                                <td>Contract</td>
                            </tr>
                            <tr>
                                <td>Generating QR-verifiable certificates</td>
                                <td>Contract / Legitimate interest</td>
                            </tr>
                            <tr>
                                <td>Maintaining the Virtual Safe and Loading Bench features</td>
                                <td>Consent</td>
                            </tr>
                            <tr>
                                <td>Preventing fraud and ensuring Portal security</td>
                                <td>Legitimate interest</td>
                            </tr>
                            <tr>
                                <td>Complying with legal and regulatory requirements</td>
                                <td>Legal obligation</td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>5. Sharing of Personal Information</h2>
                    <p>We may share your personal information with the following parties, only to the extent necessary:</p>

                    <h3>5.1 South African Police Service (SAPS)</h3>
                    <p>We are legally required under the FCA to share certain information with SAPS, including:</p>
                    <ul>
                        <li>Confirmation of membership status.</li>
                        <li>Dedicated Status records and compliance.</li>
                        <li>Endorsement letters supporting licence applications.</li>
                        <li>Notification when a member's Dedicated Status is revoked or membership is terminated.</li>
                    </ul>

                    <h3>5.2 QR Certificate Verification</h3>
                    <p>When a third party (such as a SAPS official, DFO, or range officer) scans a QR code on your certificate, they will be able to see limited verification information including your name, membership number, membership status, and the validity of the certificate. This is necessary for the legitimate purpose of certificate verification.</p>

                    <h3>5.3 Service Providers</h3>
                    <p>We engage trusted third-party service providers to assist in operating the Portal. These may include:</p>
                    <ul>
                        <li><strong>Hosting providers</strong> for server infrastructure.</li>
                        <li><strong>Email service providers</strong> for transactional and notification emails.</li>
                        <li><strong>Cloud storage providers</strong> for secure document storage.</li>
                        <li><strong>Payment processors</strong> for handling membership fee payments.</li>
                    </ul>
                    <p>All service providers are contractually bound to process your data in accordance with POPIA and only for the purposes we specify.</p>

                    <h3>5.4 We Do Not</h3>
                    <ul>
                        <li>Sell your personal information to any third party.</li>
                        <li>Share your information with marketing companies.</li>
                        <li>Transfer your personal information outside South Africa without appropriate safeguards as required by POPIA.</li>
                    </ul>

                    <h2>6. Retention of Personal Information</h2>
                    <p>We retain your personal information only for as long as necessary to fulfil the purposes for which it was collected, or as required by law:</p>
                    <ul>
                        <li><strong>Active membership records:</strong> For the duration of your membership plus 5 years after termination or expiry.</li>
                        <li><strong>Dedicated Status records:</strong> As required by the FCA, records relating to Dedicated Status are retained indefinitely or as prescribed by SAPS regulations.</li>
                        <li><strong>Financial records:</strong> 5 years as required by the Tax Administration Act and other fiscal legislation.</li>
                        <li><strong>Activity records:</strong> For the duration of your membership and 3 years thereafter.</li>
                        <li><strong>Endorsement records:</strong> 5 years from date of issue.</li>
                        <li><strong>Technical/usage data:</strong> 12 months.</li>
                    </ul>
                    <p>After the retention period, personal information will be securely destroyed or de-identified in accordance with POPIA.</p>

                    <h2>7. Security Measures</h2>
                    <p>We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, loss, destruction, or damage, including:</p>
                    <ul>
                        <li>Encryption of data in transit (TLS/SSL) and at rest.</li>
                        <li>Secure authentication with password hashing and optional two-factor authentication.</li>
                        <li>Role-based access controls limiting who can access your information.</li>
                        <li>Regular security reviews and updates.</li>
                        <li>Secure cloud infrastructure with access logging.</li>
                        <li>Automated backups with encrypted storage.</li>
                    </ul>
                    <p>While we take all reasonable steps to protect your personal information, no system is completely secure. We cannot guarantee absolute security of your data.</p>

                    <h2>8. Your Rights Under POPIA</h2>
                    <p>As a data subject under POPIA, you have the right to:</p>
                    <ul>
                        <li><strong>Access:</strong> Request confirmation of whether we hold personal information about you and request access to that information.</li>
                        <li><strong>Correction:</strong> Request the correction or deletion of personal information that is inaccurate, irrelevant, excessive, out of date, incomplete, misleading, or obtained unlawfully.</li>
                        <li><strong>Deletion:</strong> Request the destruction of your personal information, subject to our legal retention obligations.</li>
                        <li><strong>Object:</strong> Object to the processing of your personal information on reasonable grounds.</li>
                        <li><strong>Withdraw consent:</strong> Withdraw your consent to processing where consent was the legal basis, subject to any legal or contractual obligations.</li>
                        <li><strong>Complain:</strong> Lodge a complaint with the Information Regulator if you believe your rights have been infringed.</li>
                    </ul>
                    <p>To exercise any of these rights, please contact us at <a href="mailto:info@nrapa.co.za">info@nrapa.co.za</a>. We will respond to your request within a reasonable time, and no later than 30 days.</p>

                    <h3>Important Note on Deletion Requests</h3>
                    <p>Please note that we may not be able to delete certain information where we have a legal obligation to retain it, particularly records related to Dedicated Status, endorsements, and SAPS reporting requirements under the FCA. In such cases, we will inform you of the reason and the applicable retention period.</p>

                    <h2>9. Special Personal Information</h2>
                    <p>We are aware that certain information we process, such as your ID number, may constitute "special personal information" under POPIA. We process this information only because it is necessary for:</p>
                    <ul>
                        <li>Compliance with the FCA, which requires positive identification of members.</li>
                        <li>The establishment, exercise, or defence of a right or obligation in law.</li>
                        <li>The performance of the membership contract.</li>
                    </ul>

                    <h2>10. Children's Information</h2>
                    <p>Junior membership is available to persons under 18 years of age. We process personal information of children (under 18) only with the consent of a parent or legal guardian. We collect only the minimum information necessary for membership administration and comply with all POPIA requirements regarding the processing of children's personal information.</p>

                    <h2>11. Cookies</h2>
                    <p>The Portal uses the following types of cookies:</p>
                    <ul>
                        <li><strong>Essential cookies:</strong> Required for the Portal to function, including session management and CSRF protection tokens. These cannot be disabled.</li>
                        <li><strong>Functional cookies:</strong> Remember your preferences (such as theme settings) to improve your experience.</li>
                    </ul>
                    <p>We do not use advertising or tracking cookies. We do not use third-party analytics services that track individual users.</p>

                    <h2>12. Direct Marketing</h2>
                    <p>We may send you communications related to your membership, including renewal reminders, important notices, and updates about NRAPA services. These are transactional communications necessary for the administration of your membership.</p>
                    <p>We will not send you unsolicited direct marketing without your prior opt-in consent. You may opt out of marketing communications at any time by contacting us or using the unsubscribe mechanism provided in such communications.</p>

                    <h2>13. Changes to This Privacy Policy</h2>
                    <p>We may update this Privacy Policy from time to time to reflect changes in our practices, legal requirements, or services. When significant changes are made, we will notify members via email or through the Portal. The "Last updated" date at the top of this page indicates when the policy was last revised.</p>

                    <h2>14. Information Regulator</h2>
                    <p>If you are not satisfied with how we handle your personal information, you have the right to lodge a complaint with the South African Information Regulator:</p>
                    <ul>
                        <li><strong>Address:</strong> JD House, 27 Stiemens Street, Braamfontein, Johannesburg, 2001</li>
                        <li><strong>Email:</strong> <a href="mailto:enquiries@inforegulator.org.za">enquiries@inforegulator.org.za</a></li>
                        <li><strong>Website:</strong> <a href="https://inforegulator.org.za" target="_blank" rel="noopener">inforegulator.org.za</a></li>
                    </ul>

                    <h2>15. Contact Us</h2>
                    <p>For any questions, concerns, or requests regarding this Privacy Policy or the processing of your personal information, please contact:</p>
                    <ul>
                        <li><strong>Organisation:</strong> National Rifle and Pistol Association of South Africa (NRAPA)</li>
                        <li><strong>Address:</strong> 241 Jean Avenue, Centurion, Gauteng, South Africa</li>
                        <li><strong>Email:</strong> <a href="mailto:info@nrapa.co.za">info@nrapa.co.za</a></li>
                        <li><strong>Website:</strong> <a href="https://nrapa.co.za">nrapa.co.za</a></li>
                    </ul>

                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-[#020810] border-t border-white/[0.04]">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-12 py-14 sm:grid-cols-3 sm:gap-8 sm:py-16">
                    <div>
                        <img src="{{ asset('ranyati-group-logo.png') }}" alt="Ranyati Group" class="h-8 w-auto" />
                        <p class="mt-5 text-[13px] leading-[1.7] text-white/30">
                            Specialist firearm administration services since 2006.<br>
                            Trading as Ranyati Firearm Motivations (Pty) Ltd.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Divisions</h4>
                        <ul class="mt-5 space-y-3">
                            <li><a href="https://motivations.ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition-colors">Motivations</a></li>
                            <li><a href="https://nrapa.ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition-colors">NRAPA</a></li>
                            <li><a href="https://storage.ranyati.co.za" class="text-[13px] text-white/40 hover:text-white transition-colors">Storage</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Contact</h4>
                        <ul class="mt-5 space-y-3">
                            <li>
                                <a href="tel:+27871510987" class="flex items-center gap-2.5 text-[13px] text-white/40 hover:text-white transition-colors">
                                    <svg class="size-3.5 shrink-0 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                    +27 87 151 0987
                                </a>
                            </li>
                            <li>
                                <a href="mailto:info@firearmmotivations.co.za" class="flex items-center gap-2.5 text-[13px] text-white/40 hover:text-white transition-colors">
                                    <svg class="size-3.5 shrink-0 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                    info@firearmmotivations.co.za
                                </a>
                            </li>
                        </ul>
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
