<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>@yield('title', 'NRAPA')</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        :root { color-scheme: light dark; }

        /* Base resets */
        body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }

        /* Dark mode overrides — supported by Apple Mail, iOS Mail, Outlook.com, Hey */
        @media (prefers-color-scheme: dark) {
            body, .email-bg { background-color: #111827 !important; }
            .email-card { background-color: #1f2937 !important; }

            h1, h2, h3, .hd { color: #f9fafb !important; }
            .sub { color: #9ca3af !important; }
            p, td, li, span, .tx { color: #d1d5db !important; }
            .tx-muted { color: #9ca3af !important; }

            /* Coloured boxes */
            .bx-success { background-color: #064e3b !important; border-color: #059669 !important; }
            .bx-success h3, .bx-success td, .bx-success p, .bx-success span { color: #6ee7b7 !important; }

            .bx-danger { background-color: #450a0a !important; border-color: #dc2626 !important; }
            .bx-danger h3, .bx-danger td, .bx-danger p, .bx-danger span { color: #fca5a5 !important; }

            .bx-info { background-color: #1e1b4b !important; border-color: #6366f1 !important; }
            .bx-info h3, .bx-info td, .bx-info p, .bx-info li, .bx-info ol, .bx-info span { color: #a5b4fc !important; }

            .bx-nrapa { background-color: #0a2a52 !important; border-color: #1a5cb8 !important; }
            .bx-nrapa h3, .bx-nrapa td, .bx-nrapa p, .bx-nrapa span { color: #93bbef !important; }

            .bx-nrapa-card { background-color: #1a1a1a !important; border-color: #F58220 !important; }
            .bx-nrapa-card h3 { color: #F58220 !important; }
            .bx-nrapa-card p, .bx-nrapa-card td, .bx-nrapa-card span { color: #e5e7eb !important; }

            .bx-warning { background-color: #451a03 !important; border-color: #d97706 !important; }
            .bx-warning h3, .bx-warning td, .bx-warning p, .bx-warning li, .bx-warning strong, .bx-warning span { color: #fcd34d !important; }

            .bx-neutral { background-color: #374151 !important; border-color: #4b5563 !important; }
            .bx-neutral h3, .bx-neutral td, .bx-neutral p, .bx-neutral span { color: #e5e7eb !important; }

            /* Reference value (payment) */
            .ref-value { background-color: #111827 !important; color: #f9fafb !important; }

            /* Divider */
            .divider { border-color: #374151 !important; }

            /* Footer */
            .email-footer td { background-color: #111827 !important; }
            .ft { color: #6b7280 !important; }

            /* Logo background for visibility */
            .logo-wrap { background-color: #ffffff !important; border-radius: 12px !important; }

            /* Links */
            a { color: #60a5fa !important; }
            .btn-primary a { color: #ffffff !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; background-color: #f3f4f6; -webkit-font-smoothing: antialiased;">
    <!-- Outer wrapper — full-width background -->
    <table role="presentation" class="email-bg" style="width: 100%; border-collapse: collapse; background-color: #f3f4f6;">
        <tr>
            <td style="padding: 30px 15px;">
                <!-- Inner card -->
                <table role="presentation" class="email-card" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">

                    {{-- ===== HEADER ===== --}}
                    <tr>
                        <td style="padding: 30px 30px 20px 30px; text-align: center;">
                            <div class="logo-wrap" style="display: inline-block; padding: 8px; border-radius: 12px;">
                                <img src="{{ config('app.url') }}/nrapa-logo.png" alt="NRAPA" width="72" height="72" style="width: 72px; height: 72px; object-fit: contain; display: block;" />
                            </div>
                            @hasSection('heading')
                                <h1 class="hd" style="color: #111827; margin: 16px 0 4px 0; font-size: 24px; font-weight: 700; line-height: 1.3;">
                                    @yield('heading')
                                </h1>
                            @endif
                            @hasSection('subtitle')
                                <p class="sub" style="color: #6b7280; margin: 0; font-size: 15px;">
                                    @yield('subtitle')
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- ===== BODY ===== --}}
                    <tr>
                        <td class="tx" style="padding: 0 30px 30px 30px; color: #374151; font-size: 15px; line-height: 1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- ===== FOOTER ===== --}}
                    <tr class="email-footer">
                        <td style="padding: 20px 30px; border-top: 1px solid #e5e7eb; text-align: center; background-color: #f9fafb; border-radius: 0 0 8px 8px;">
                            @hasSection('footer')
                                <p class="ft" style="color: #9ca3af; font-size: 12px; line-height: 1.5; margin: 0 0 8px 0;">
                                    @yield('footer')
                                </p>
                            @endif
                            <p class="ft" style="color: #9ca3af; font-size: 12px; line-height: 1.5; margin: 0;">
                                &copy; {{ date('Y') }} National Rifle &amp; Pistol Association of South Africa. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
