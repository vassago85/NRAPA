@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();

    $targetBgPath = public_path('Target.png');
    if (!file_exists($targetBgPath)) $targetBgPath = public_path('nrapa-target-bg.png');
    $targetBgData = file_exists($targetBgPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($targetBgPath)) : '';

    $logoHorizPath = public_path('logo-nrapa-blue-text.png');
    $logoHorizData = file_exists($logoHorizPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoHorizPath)) : '';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>{{ $title ?? 'NRAPA Document' }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 210mm;
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            color: #222222;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 4mm 5mm 3mm 5mm;
            position: relative;
            overflow: hidden;
            background: #fff;
        }

        .bg-graphic {
            position: absolute;
            top: 4mm;
            right: 5mm;
            width: 60%;
            opacity: 1;
            pointer-events: none;
            z-index: 0;
        }
        .bg-graphic img {
            display: block;
            width: 100%;
            height: auto;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header { margin-bottom: 4px; margin-top: 22px; }
        .header-logo { max-height: 95px; width: auto; }

        /* FAR info banner */
        .far-banner {
            width: 66%;
            background: #f0f3f8;
            border: 1px solid #e5e5e5;
            padding: 6px 14px;
            border-radius: 6px;
            margin-top: 4px;
            margin-left: 2.5%;
        }
        .far-badge {
            font-size: 11px;
            color: #6a6a6a;
            font-weight: 500;
        }
        .far-badge b { color: #1f4e8c; font-weight: 600; }
        .far-nums { margin-top: 3px; font-size: 11px; color: #6a6a6a; }
        .far-nums b { color: #1f4e8c; font-weight: 600; }

        /* Document title banner */
        .doc-banner {
            width: 66%;
            background: #f0f3f8;
            border: 1px solid #e5e5e5;
            padding: 6px 14px;
            border-radius: 6px;
            margin: 2px 0 2px 2.5%;
        }
        .doc-banner-title {
            font-size: 17px;
            font-weight: 700;
            color: #1f4e8c;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
        }
        .doc-banner-subtitle {
            font-size: 11px;
            color: #6a6a6a;
            margin-top: 3px;
            font-style: italic;
        }

        /* ---- Table-based layouts (DomPDF compatible) ---- */
        .layout-table {
            width: 95%;
            margin: 4px auto 0 auto;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .layout-table td {
            vertical-align: top;
            padding: 0;
        }
        .layout-table td.half { width: 50%; }
        .layout-table td.third { width: 33.33%; }

        /* Cards */
        .card {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .card-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1f4e8c;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        /* Key-Value rows (table-based) */
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 2px 0; font-size: 11px; border-bottom: 1px solid #ececec; }
        .kv-table tr:last-child td { border-bottom: none; }
        .kv-label { width: 45%; font-size: 11px; font-weight: 500; color: #6a6a6a; }
        .kv-value { font-weight: 600; font-size: 12px; color: #222222; word-break: break-word; }
        .kv-value a { color: #1f4e8c; font-weight: 500; text-decoration: none; word-break: break-all; font-size: 9px; }

        /* Components card */
        .components-card { margin-top: 4px; padding: 8px 12px; width: 95%; margin-left: auto; margin-right: auto; }

        /* Firearm grid (table-based) */
        .fg-table { width: 100%; border-collapse: collapse; border-bottom: 1px solid #ececec; }
        .fg-table td { padding: 2px 4px 4px 0; vertical-align: top; }
        .fg-label { display: block; font-size: 9px; font-weight: 500; color: #6a6a6a; text-transform: uppercase; letter-spacing: 0.3px; }
        .fg-value { display: block; font-size: 12px; font-weight: 700; color: #222222; }

        /* Component items */
        .component-table { width: 100%; border-collapse: collapse; }
        .component-table td { padding: 3px 0; font-size: 11px; border-bottom: 1px solid #ececec; }
        .component-table tr:last-child td { border-bottom: none; }
        .component-type { font-weight: 600; color: #1f4e8c; }
        .component-detail { color: #6a6a6a; font-size: 10px; text-align: right; }

        /* Letter body */
        .letter-body {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.35;
            color: #2c2c2c;
            width: 95%;
            margin-left: auto;
            margin-right: auto;
        }
        .letter-body b { color: #222222; }
        .letter-body .purpose-line {
            display: block;
            margin: 4px 0;
            padding: 4px 10px;
            background: #f2f2f2;
            border-left: 4px solid #1f4e8c;
            border-radius: 0 4px 4px 0;
            font-weight: 600;
            font-size: 12px;
        }
        .letter-body ul { margin: 6px 0 8px 18px; }
        .letter-body li { margin: 2px 0; font-size: 11px; }

        /* QR / verify */
        .verify-card {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 4px;
            width: 95%;
            margin-left: auto;
            margin-right: auto;
        }
        .qr-box {
            width: 77px;
            height: 77px;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
            padding: 2px;
        }
        .qr-box img { width: 100%; height: auto; }
        .verify-text { font-size: 10px; color: #6a6a6a; line-height: 1.4; padding-left: 10px; }
        .verify-text strong { display: block; font-size: 11px; color: #222222; margin-bottom: 2px; }
        .verify-text a { color: #1f4e8c; font-weight: 500; text-decoration: none; }

        /* Signatory card */
        .signatory-card { text-align: left; }
        .sig-box {
            height: 36px;
            background: #fff;
            border: 1px dashed #d7d7d7;
            border-radius: 6px;
            overflow: hidden;
            margin: 4px 0 6px 0;
            text-align: center;
        }
        .sig-box img { max-height: 32px; max-width: 100%; }
        .sig-line { height: 1px; background: #e5e5e5; margin: 4px 0; }
        .sig-name { font-weight: 600; font-size: 12px; color: #1f4e8c; }
        .sig-title { font-size: 10px; color: #6a6a6a; }
        .sig-date { font-size: 9px; color: #6a6a6a; margin-top: 2px; }

        /* Commissioner card */
        .commissioner-card { text-align: left; }
        .commissioner-box {
            min-height: 36px;
            border: 2px dashed #d7d7d7;
            border-radius: 6px;
            text-align: center;
            color: #888;
            font-size: 10px;
            background: #fff;
            overflow: hidden;
            margin-top: 4px;
            padding: 4px;
        }
        .commissioner-box img { max-height: 60px; max-width: 100%; }
        .commissioner-sub { font-size: 8px; color: #6a6a6a; margin-top: 2px; }

        /* Orange footer bar */
        .footer-bar {
            background: #f58220;
            color: #000;
            font-size: 8px;
            text-align: center;
            padding: 4px 14px;
            margin-top: 8px;
            width: 98%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 6px;
            line-height: 1.3;
            letter-spacing: 0.3px;
        }
        .footer-bar .contacts { font-weight: 600; }

        @media print {
            html, body { background: #fff !important; }
            .page { margin: 0; box-shadow: none; }
        }
    </style>
    @stack('document-styles')
</head>
<body>
<div class="page">

    @if($targetBgData)
    <div class="bg-graphic">
        <img src="{{ $targetBgData }}" alt=""/>
    </div>
    @endif

    <div class="content">

        <div class="header">
            @if($logoHorizData)
                <img src="{{ $logoHorizData }}" alt="NRAPA" class="header-logo"/>
            @elseif($logoUrl)
                <img src="{{ $logoUrl }}" alt="NRAPA" class="header-logo"/>
            @endif
            <div class="far-banner">
                <div class="far-badge"><b>FAR Accredited</b> &nbsp;|&nbsp; SAPS Recognised</div>
                <div class="far-nums">
                    <b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}
                    &nbsp;&nbsp;&nbsp;
                    <b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}
                </div>
            </div>
        </div>

        @yield('document-banner')

        @yield('content')

    </div>

    <div class="footer-bar">
        <div class="contacts">
            TEL: {{ $contact['tel'] }} &bull;
            FAX: {{ $contact['fax'] }} &bull;
            E-MAIL: {{ $contact['email'] }} &bull;
            ADDRESS: {{ $contact['physical_address'] }}
        </div>
    </div>

</div>
</body>
</html>
