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
            page-break-inside: avoid !important;
        }

        :root {
            --blue: #1f4e8c;
            --orange: #f58220;
            --panel: rgba(255, 255, 255, 0.60);
            --border: rgba(0, 0, 0, 0.10);
            --text: #222222;
            --muted: #6a6a6a;
            --status-green: #1f6b3a;
            --font: "Inter", "Helvetica Neue", Arial, sans-serif;
        }

        html, body {
            width: 210mm;
            height: 297mm;
            font-family: var(--font);
            font-size: 12px;
            line-height: 1.45;
            color: var(--text);
            background: #fff;
            font-feature-settings: "tnum";
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 210mm;
            height: 297mm;
            padding: 4mm 5mm 3mm 5mm;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .bg-graphic {
            position: absolute;
            top: 4mm;
            right: 5mm;
            width: 66%;
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header { margin-bottom: 4px; margin-top: 22px; }
        .header-logo { max-height: 95px; width: auto; }

        /* FAR info banner */
        .far-banner {
            width: 66%;
            background: linear-gradient(90deg, rgba(31,78,140,0.07) 0%, rgba(245,130,32,0.07) 100%);
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 6px;
            margin-top: 4px;
            margin-left: 2.5%;
        }
        .far-badge {
            display: inline-block;
            font-size: 11px;
            color: var(--muted);
            font-weight: 500;
        }
        .far-badge b { color: var(--blue); font-weight: 600; }

        .far-row { display: flex; gap: 20px; margin-top: 3px; font-size: 11px; color: var(--muted); }
        .far-row b { color: var(--blue); font-weight: 600; }

        /* Document title banner */
        .doc-banner {
            width: 66%;
            background: linear-gradient(90deg, rgba(31,78,140,0.07) 0%, rgba(245,130,32,0.07) 100%);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 6px;
            margin: 4px 0 4px 2.5%;
        }
        .doc-banner-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--blue);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
        }
        .doc-banner-subtitle {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
            font-style: italic;
        }

        /* Info grid */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; width: 95%; margin-left: auto; margin-right: auto; }
        .info-grid .card {
            background: linear-gradient(90deg, rgba(31,78,140,0.07) 0%, rgba(245,130,32,0.07) 100%), var(--panel);
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px;
        }
        .card-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--blue);
            border-bottom: 1px solid var(--border);
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .kv-row {
            display: flex;
            justify-content: flex-start;
            align-items: baseline;
            padding: 3px 0;
            font-size: 11px;
            border-bottom: 1px solid #ececec;
        }
        .kv-row:last-child { border-bottom: none; }
        .kv-label { width: 45%; flex-shrink: 0; font-size: 11px; font-weight: 500; color: var(--muted); }
        .kv-value { font-weight: 600; font-size: 12px; color: var(--text); text-align: left; word-break: break-word; }
        .kv-value a { color: var(--blue); font-weight: 500; text-decoration: none; word-break: break-all; font-size: 9px; }

        /* Component card */
        .components-card { margin-top: 6px; padding: 10px 14px; width: 95%; margin-left: auto; margin-right: auto; }
        .component-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 4px 0;
            font-size: 11px;
            border-bottom: 1px solid #ececec;
        }
        .component-item:last-child { border-bottom: none; }
        .component-type { font-weight: 600; color: var(--blue); }
        .component-detail { color: var(--muted); font-size: 10px; }

        /* Letter body */
        .letter-body {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 6px;
            font-size: 12px;
            line-height: 1.4;
            color: #2c2c2c;
            width: 95%;
            margin-left: auto;
            margin-right: auto;
        }
        .letter-body b { color: var(--text); }
        .letter-body .purpose-line {
            display: block;
            margin: 6px 0;
            padding: 6px 12px;
            background: #f2f2f2;
            border-left: 4px solid var(--blue);
            border-radius: 0 4px 4px 0;
            font-weight: 600;
            font-size: 12px;
        }
        .letter-body ul { margin: 6px 0 8px 18px; }
        .letter-body li { margin: 2px 0; font-size: 11px; }

        /* Bottom grid */
        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; width: 95%; margin-left: auto; margin-right: auto; }

        .verify-card { display: block; }
        .qr-box {
            width: 77px;
            height: 77px;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            background: #fff;
            padding: 2px;
        }
        .qr-box img { width: 100%; height: 100%; object-fit: contain; }
        .verify-text { font-size: 10px; color: var(--muted); line-height: 1.4; }
        .verify-text strong { display: block; font-size: 11px; color: var(--text); margin-bottom: 2px; }
        .verify-text a { color: var(--blue); font-weight: 500; text-decoration: none; }

        .signatory-card { text-align: left; }
        .sig-box {
            height: 36px;
            background: #fff !important;
            border: 1px dashed var(--border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 4px 0 6px 0;
        }
        .sig-box img { max-height: 32px; max-width: 100%; object-fit: contain; }
        .sig-line { height: 1px; background: var(--border); margin: 4px 0; }
        .sig-name { font-weight: 600; font-size: 12px; color: var(--blue); }
        .sig-title { font-size: 10px; color: var(--muted); }
        .sig-date { font-size: 9px; color: var(--muted); margin-top: 2px; }

        /* Commissioner card */
        .commissioner-card { text-align: left; }
        .commissioner-box {
            flex: 1;
            min-height: 36px;
            border: 2px dashed #d7d7d7;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 10px;
            background: #fff;
            overflow: hidden;
            margin-top: 4px;
        }
        .commissioner-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .commissioner-sub { font-size: 8px; color: var(--muted); margin-top: 2px; }

        /* Verification row */
        .verify-row {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 6px;
            width: 95%;
            margin-left: auto;
            margin-right: auto;
        }

        /* Orange footer bar */
        .footer-bar {
            background: var(--orange);
            color: #000;
            font-size: 8px;
            text-align: center;
            padding: 4px 14px;
            margin-top: auto;
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
                <div class="far-row">
                    <span><b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}</span>
                    <span><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
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
