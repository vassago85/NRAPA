<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Terms and Conditions - NRAPA</title>
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
                <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Terms and Conditions</h1>
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
                    <p>These Terms and Conditions ("Terms") govern your use of the National Rifle and Pistol Association of South Africa ("NRAPA", "we", "us", "our") members portal at <strong>members.nrapa.co.za</strong> and all related services, including membership registration, certificate issuance, endorsement applications, and related administration.</p>
                    <p>By registering an account, applying for membership, or using any of our services, you agree to be bound by these Terms. If you do not agree, you must not use our services.</p>
                    <p>NRAPA is a South African hunting and sport shooting association accredited by the South African Police Service (SAPS) under the Firearms Control Act 60 of 2000, with FAR numbers <strong>1300122</strong> (Sport) and <strong>1300127</strong> (Hunting).</p>

                    <h2>2. Definitions</h2>
                    <ul>
                        <li><strong>"Member"</strong> means any natural person who has applied for and been accepted as a member of NRAPA and whose membership fees are current.</li>
                        <li><strong>"Dedicated Status"</strong> means Dedicated Hunter and/or Dedicated Sport Shooter status as defined in the Firearms Control Act 60 of 2000 and the Firearms Control Regulations.</li>
                        <li><strong>"Portal"</strong> means the NRAPA online members portal and all associated digital services.</li>
                        <li><strong>"FCA"</strong> means the Firearms Control Act 60 of 2000 and its regulations.</li>
                        <li><strong>"SAPS"</strong> means the South African Police Service.</li>
                        <li><strong>"POPIA"</strong> means the Protection of Personal Information Act 4 of 2013.</li>
                    </ul>

                    <h2>3. Membership</h2>

                    <h3>3.1 Eligibility</h3>
                    <ul>
                        <li>Ordinary Membership is open to any natural person from the age of 18 years.</li>
                        <li>Junior Membership is available to persons under the age of 18 years, subject to parental or guardian consent.</li>
                        <li>Senior Membership is available to natural persons from the age of 61 years.</li>
                        <li>Membership may be refused if disciplinary measures have been instituted by other accredited associations against the applicant.</li>
                        <li>Applicants must not be declared unfit to possess a firearm in terms of the FCA.</li>
                    </ul>

                    <h3>3.2 Registration and Approval</h3>
                    <p>All membership applications are subject to review and approval by the NRAPA administration. Submission of an application does not guarantee acceptance. NRAPA reserves the right to refuse or revoke membership at any time in accordance with its constitution and applicable law.</p>

                    <h3>3.3 Duration and Renewal</h3>
                    <ul>
                        <li>Annual memberships are valid for 12 months from the date of enrolment or renewal.</li>
                        <li>Membership must be renewed before expiry by payment of the applicable annual subscription fee.</li>
                        <li>A lapsed membership may result in loss of dedicated status and related benefits.</li>
                        <li>NRAPA will endeavour to send renewal reminders, but the responsibility for timely renewal rests solely with the member.</li>
                    </ul>

                    <h3>3.4 Fees</h3>
                    <ul>
                        <li>All fees are quoted in South African Rand (ZAR) and are inclusive of VAT where applicable.</li>
                        <li>Fees are payable in advance and are non-refundable unless otherwise required by the Consumer Protection Act 68 of 2008 ("CPA").</li>
                        <li>NRAPA reserves the right to adjust fees from time to time. Members will be notified of fee changes at least 30 days in advance.</li>
                    </ul>

                    <h2>4. Dedicated Status</h2>

                    <h3>4.1 Requirements</h3>
                    <p>To obtain Dedicated Hunter or Dedicated Sport Shooter status, members must:</p>
                    <ul>
                        <li>Be a fully paid-up member in good standing.</li>
                        <li>Successfully complete the relevant training course as prescribed by the Firearms Control Regulations.</li>
                        <li>Pass any required knowledge tests administered through the Portal.</li>
                        <li>Adhere to NRAPA's Code of Conduct.</li>
                    </ul>

                    <h3>4.2 Maintaining Dedicated Status</h3>
                    <p>It is the member's sole responsibility to maintain Dedicated Status by:</p>
                    <ul>
                        <li>Continuing to be involved in hunting or sport shooting activities as required.</li>
                        <li>Submitting proof of at least <strong>two (2) qualifying activities</strong> during each 12-month membership period upon request.</li>
                        <li>Keeping all membership fees current.</li>
                    </ul>
                    <p><strong>Important:</strong> NRAPA is legally obligated to inform the SAPS should a member fail to comply with the requirements for maintaining Dedicated Status. In such event, the member will lose their Dedicated Status and shall have no claim whatsoever against NRAPA, its management, or any of its officials.</p>

                    <h3>4.3 Endorsements</h3>
                    <p>Endorsement applications are submitted through the Portal and are subject to review and approval. NRAPA issues endorsement letters in accordance with the FCA to support members' licence applications. NRAPA does not guarantee the outcome of any licence application made to the SAPS or the Central Firearms Register.</p>

                    <h2>5. Use of the Portal</h2>

                    <h3>5.1 Account Security</h3>
                    <ul>
                        <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                        <li>You must not share your login details with any other person.</li>
                        <li>You must notify NRAPA immediately if you become aware of any unauthorised use of your account.</li>
                        <li>NRAPA is not liable for any loss arising from unauthorised access to your account.</li>
                    </ul>

                    <h3>5.2 Acceptable Use</h3>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Provide false, misleading, or fraudulent information.</li>
                        <li>Use the Portal for any unlawful purpose.</li>
                        <li>Attempt to gain unauthorised access to any part of the Portal or its systems.</li>
                        <li>Interfere with the proper functioning of the Portal.</li>
                        <li>Upload malicious code, viruses, or harmful content.</li>
                    </ul>

                    <h3>5.3 Content and Data Accuracy</h3>
                    <p>You are responsible for the accuracy of all information submitted through the Portal, including but not limited to personal details, firearm information, and activity records. Submission of false information is a breach of these Terms and may constitute an offence under the FCA.</p>

                    <h2>6. Certificates and Documents</h2>
                    <ul>
                        <li>Membership certificates, endorsement letters, and other documents generated through the Portal are the property of NRAPA.</li>
                        <li>All certificates include QR codes for verification purposes. Tampering with, forging, or misrepresenting any NRAPA-issued document is strictly prohibited and may constitute fraud.</li>
                        <li>NRAPA reserves the right to revoke any certificate or endorsement at any time if the member's status changes or if the document was issued in error.</li>
                    </ul>

                    <h2>7. Code of Conduct</h2>
                    <p>All members must adhere to the NRAPA Code of Conduct, which includes but is not limited to:</p>
                    <ul>
                        <li>Safe and responsible firearm handling at all times.</li>
                        <li>Compliance with all applicable South African laws, including the FCA and its regulations.</li>
                        <li>Treating fellow members, officials, and the public with respect.</li>
                        <li>Never using alcohol or drugs before or while handling firearms.</li>
                        <li>Wearing appropriate eye and ear protection during shooting activities.</li>
                        <li>Using only the correct ammunition for your firearm.</li>
                        <li>Knowing your target and what lies beyond it.</li>
                    </ul>
                    <p>Breach of the Code of Conduct may result in disciplinary action, suspension, or termination of membership.</p>

                    <h2>8. Disciplinary Procedures</h2>
                    <p>NRAPA reserves the right to investigate complaints against members and to institute disciplinary proceedings. Disciplinary measures may include warnings, suspension of membership, termination of membership, revocation of Dedicated Status, and reporting to the SAPS where legally required.</p>
                    <p>Members subject to disciplinary proceedings will be afforded a fair opportunity to be heard in accordance with the principles of natural justice.</p>

                    <h2>9. Intellectual Property</h2>
                    <p>All content on the Portal, including logos, text, graphics, images, software, and the NRAPA brand, is the intellectual property of NRAPA or its licensors and is protected by South African copyright and trademark law. You may not reproduce, distribute, or create derivative works from any Portal content without prior written consent from NRAPA.</p>

                    <h2>10. Limitation of Liability</h2>
                    <ul>
                        <li>NRAPA provides administrative and facilitation services related to membership and firearm licensing. We do not provide legal advice.</li>
                        <li>NRAPA does not guarantee the approval of any firearm licence, competency certificate, or permit application submitted to the SAPS or any other authority.</li>
                        <li>NRAPA shall not be liable for any direct, indirect, incidental, or consequential damages arising from the use of the Portal or our services, except where such liability cannot be excluded by law.</li>
                        <li>NRAPA shall not be liable for any loss arising from the member's failure to maintain their Dedicated Status, renew their membership, or comply with the FCA.</li>
                        <li>The Portal is provided "as is" and "as available". While we strive to maintain uptime and accuracy, we do not warrant that the Portal will be uninterrupted, error-free, or free of viruses.</li>
                    </ul>

                    <h2>11. Indemnity</h2>
                    <p>You indemnify and hold harmless NRAPA, its executive committee, officials, employees, and agents from and against any claims, damages, losses, or expenses (including legal fees) arising from:</p>
                    <ul>
                        <li>Your use of the Portal or services.</li>
                        <li>Your breach of these Terms.</li>
                        <li>Any false or misleading information you have provided.</li>
                        <li>Any unlawful conduct on your part.</li>
                    </ul>

                    <h2>12. Termination</h2>
                    <ul>
                        <li>A member may resign their membership at any time by providing written notice. No refund of fees will be given for the unexpired portion of the membership period.</li>
                        <li>NRAPA may terminate or suspend a membership for breach of these Terms, non-payment of fees, or any other just cause.</li>
                        <li>Upon termination, access to the Portal will be revoked and any active dedicated status or endorsements may be affected.</li>
                        <li>NRAPA will comply with its legal obligation to notify the SAPS of any membership termination where the member holds Dedicated Status.</li>
                    </ul>

                    <h2>13. Consumer Protection</h2>
                    <p>These Terms do not limit or exclude any rights you may have under the Consumer Protection Act 68 of 2008 or the Electronic Communications and Transactions Act 25 of 2002 ("ECT Act"). Where any provision of these Terms conflicts with your statutory rights, the statutory provision will prevail.</p>

                    <h2>14. Amendments</h2>
                    <p>NRAPA reserves the right to amend these Terms at any time. Significant changes will be communicated to members via email or through the Portal. Continued use of the Portal after notification of changes constitutes acceptance of the amended Terms. Members who accept updated terms through the Portal are bound by those terms from the date of acceptance.</p>

                    <h2>15. Governing Law and Jurisdiction</h2>
                    <p>These Terms are governed by the laws of the Republic of South Africa. Any disputes arising from or in connection with these Terms shall be subject to the jurisdiction of the Magistrate's Court or High Court of South Africa, as appropriate, in the jurisdiction of Gauteng.</p>

                    <h2>16. Contact Information</h2>
                    <p>For any queries regarding these Terms, please contact us:</p>
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
                        <img src="{{ asset('logo-ranyatigroup-white_text.png') }}" alt="Ranyati Group" class="h-8 w-auto" />
                        <p class="mt-5 text-[13px] leading-[1.7] text-white/30">
                            Specialist firearm administration services since 2006.<br>
                            Trading as Ranyati Firearm Motivations (Pty) Ltd.
                        </p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Divisions</h4>
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 20px; align-items: center;">
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
                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/25">Contact</h4>
                        <div style="margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; gap: 0;">
                            <a href="tel:+27871510987" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.2);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                +27 87 151 0987
                            </a>
                            <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0;"></div>
                            <a href="mailto:info@nrapa.co.za" style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
                                <svg style="width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,0.2);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                info@nrapa.co.za
                            </a>
                        </div>
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
