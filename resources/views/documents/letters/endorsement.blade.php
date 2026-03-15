@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getEndorsementQrCodeUrl($request, 200);
    $verifyUrl = $request->letter_reference
        ? url('/verify/endorsement/' . $request->letter_reference)
        : ($request->uuid ? url('/verify/endorsement/' . $request->uuid) : '#');
    $signatory = \App\Helpers\DocumentDataHelper::getEndorsementSignatoryInfo($request);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(\App\Helpers\DocumentDataHelper::getDefaultSignaturePath());
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml(\App\Helpers\DocumentDataHelper::getDefaultCommissionerScanPath());
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();

    $user = $request->user;
    $membership = $user->activeMembership;
    $firearm = $request->firearm;

    $purposeText = match($request->purpose) {
        'section_16_application' => 'Section 16 firearm licence application',
        'status_confirmation' => 'Status confirmation for regulatory purposes',
        'licence_renewal' => 'Firearm licence renewal application',
        'additional_firearm' => 'Application for additional firearm',
        'other' => $request->purpose_other_text ?? 'Other purpose',
        default => 'Firearm licence application',
    };

    // Base64 encode the target background for reliable PDF rendering
    $targetBgPath = public_path('Target.png');
    if (!file_exists($targetBgPath)) $targetBgPath = public_path('nrapa-target-bg.png');
    $targetBgData = file_exists($targetBgPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($targetBgPath)) : '';

    // Base64 encode the horizontal logo (blue text, genuine PNG)
    $logoHorizPath = public_path('logo-nrapa-blue-text.png');
    $logoHorizData = file_exists($logoHorizPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoHorizPath)) : '';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Endorsement Letter — NRAPA</title>
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
            --panel: rgba(255, 255, 255, 0.20);
            --border: rgba(255, 255, 255, 0.35);
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
            padding: 6mm 8mm 0 8mm;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        /* Target graphic — DO NOT MOVE */
        .bg-graphic {
            position: absolute;
            top: 6mm;
            right: 8mm;
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header { margin-bottom: 6px; margin-top: 6px; }
        .header-logo { max-height: 86px; width: auto; }

        /* FAR info banner */
        .far-banner {
            position: relative;
            width: 66%;
            background: rgba(255, 255, 255, 0.20);
            padding: 6px 14px;
            border-radius: 6px 0 0 6px;
            margin-top: 8px;
            overflow: visible;
        }
        .far-banner::after {
            content: '';
            position: absolute;
            top: 0;
            left: 100%;
            width: 20px;
            height: 100%;
            background: rgba(255, 255, 255, 0.20);
            clip-path: polygon(0 0, 100% 0, calc(100% - 8px) 100%, 0 100%);
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

        /* Endorsement banner with angled right edge */
        .endorsement-banner {
            position: relative;
            width: 66%;
            background: rgba(255, 255, 255, 0.20);
            padding: 10px 14px;
            border-radius: 6px 0 0 6px;
            margin: 8px 0 6px 0;
            overflow: visible;
        }
        .endorsement-banner::after {
            content: '';
            position: absolute;
            top: 0;
            left: 100%;
            width: 20px;
            height: 100%;
            background: rgba(255, 255, 255, 0.20);
            clip-path: polygon(0 0, 100% 0, calc(100% - 8px) 100%, 0 100%);
        }
        .endorsement-banner-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--blue);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
        }
        .endorsement-banner-subtitle {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
            font-style: italic;
        }

        /* Info grid */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px 14px;
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
            justify-content: space-between;
            align-items: baseline;
            padding: 4px 0;
            font-size: 11px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .kv-row:last-child { border-bottom: none; }
        .kv-label { font-size: 11px; font-weight: 500; color: var(--muted); }
        .kv-value { font-weight: 600; font-size: 12px; color: var(--text); text-align: right; max-width: 60%; word-break: break-word; }
        .kv-value a { color: var(--blue); font-weight: 500; text-decoration: none; word-break: break-all; font-size: 9px; }

        /* Component card */
        .components-card { margin-top: 10px; padding: 12px 14px; }
        .component-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 6px 0;
            font-size: 11px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .component-item:last-child { border-bottom: none; }
        .component-type { font-weight: 600; color: var(--blue); }
        .component-detail { color: var(--muted); font-size: 10px; }

        /* Letter body */
        .letter-body {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 14px 16px;
            margin-top: 10px;
            font-size: 12px;
            line-height: 1.45;
            color: #2c2c2c;
        }
        .letter-body b { color: var(--text); }
        .letter-body .purpose-line {
            display: block;
            margin: 6px 0;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.25);
            border-left: 4px solid var(--blue);
            border-radius: 0 4px 4px 0;
            font-weight: 600;
            font-size: 12px;
        }

        /* Bottom grid */
        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }

        .verify-card { display: block; }
        .qr-box {
            width: 77px;
            height: 77px;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.85);
            padding: 2px;
        }
        .qr-box img { width: 100%; height: 100%; object-fit: contain; }
        .verify-text { font-size: 10px; color: var(--muted); line-height: 1.4; }
        .verify-text strong { display: block; font-size: 11px; color: var(--text); margin-bottom: 2px; }
        .verify-text a { color: var(--blue); font-weight: 500; text-decoration: none; }

        .signatory-card { text-align: left; }
        .sig-box {
            height: 36px;
            background: rgba(255, 255, 255, 0.85) !important;
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

        /* Commissioner */
        .commissioner { margin-top: 12px; }
        .commissioner-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--blue);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .commissioner-sub {
            font-size: 9px;
            color: var(--muted);
            margin-top: 1px;
        }
        .commissioner-box {
            margin-top: 4px;
            height: 64px;
            border: 2px dashed #d7d7d7;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 10px;
            background: transparent;
            overflow: hidden;
        }
        .commissioner-box img { max-height: 100%; max-width: 100%; object-fit: contain; }

        /* Orange footer bar */
        .footer-bar {
            background: var(--orange);
            color: #000;
            font-size: 10px;
            text-align: center;
            padding: 10px 14px;
            margin-top: auto;
            border-radius: 6px;
            line-height: 1.5;
            letter-spacing: 0.3px;
        }
        .footer-bar .contacts { font-weight: 600; }
        .footer-bar .disclaimer { font-size: 9px; margin-top: 2px; }

        @media print {
            html, body { background: #fff !important; }
            .page { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Target / circuit background graphic --}}
    @if($targetBgData)
    <div class="bg-graphic">
        <img src="{{ $targetBgData }}" alt=""/>
    </div>
    @endif

    <div class="content">

        {{-- Header: Horizontal logo + FAR info --}}
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

        {{-- Title banner with wedge --}}
        <div class="endorsement-banner">
            <div class="endorsement-banner-title">Endorsement Letter</div>
            <div class="endorsement-banner-subtitle">Issued for firearm licence application purposes</div>
        </div>

        {{-- Info grid: Member + Letter details --}}
        <div class="info-grid">
            <div class="card">
                <div class="card-title">Applicant / Member</div>
                <div class="kv-row">
                    <span class="kv-label">Full Name</span>
                    <span class="kv-value">{{ $user->getIdName() }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">ID / Passport</span>
                    <span class="kv-value">{{ $user->getIdNumber() ?? 'N/A' }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Membership Number</span>
                    <span class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Membership Status</span>
                    <span class="kv-value" style="color:#1f6b3a; font-weight:600;">Member in Good Standing</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Dedicated Status</span>
                    <span class="kv-value">{{ $request->dedicated_status_label }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Dedicated Category</span>
                    <span class="kv-value">{{ $request->dedicated_category_label }}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-title">Letter Details</div>
                <div class="kv-row">
                    <span class="kv-label">Endorsement Ref</span>
                    <span class="kv-value">{{ $request->letter_reference ?? 'N/A' }}</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Issued Date</span>
                    <span class="kv-value">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Component endorsements --}}
        @if ($request->components && $request->components->isNotEmpty())
        <div class="card components-card">
            <div class="card-title">{{ $firearm ? 'Firearm & Component Endorsements' : 'Component Endorsements' }}</div>
            @if($firearm)
            <div class="component-item">
                <div>
                    <span class="component-type">Firearm</span>
                    &mdash; {{ trim(($firearm->firearmMake->name ?? '') . ' ' . ($firearm->firearmModel->name ?? '')) }}
                </div>
                <div class="component-detail">
                    @if($firearm->firearmCalibre)
                        Calibre: {{ $firearm->firearmCalibre->name }}
                    @endif
                    @if($firearm->serial_number)
                        &nbsp;| Serial: {{ $firearm->serial_number }}
                    @endif
                </div>
            </div>
            @endif
            @foreach ($request->components as $component)
            <div class="component-item">
                <div>
                    <span class="component-type">{{ $component->component_type_label }}</span>
                    @if ($component->component_make || $component->component_model)
                        &mdash; {{ trim(($component->component_make ?? '') . ' ' . ($component->component_model ?? '')) }}
                    @endif
                </div>
                <div class="component-detail">
                    @if ($component->component_type === 'barrel' && $component->diameter)
                        Diameter: {{ $component->diameter }}
                    @elseif ($component->calibre_display)
                        Calibre: {{ $component->calibre_display }}
                    @endif
                    @if ($component->component_serial)
                        &nbsp;| Serial: {{ $component->component_serial }}
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Letter body --}}
        <div class="letter-body">
            To whom it may concern,<br/><br/>
            This letter serves to confirm that <b>{{ $user->getIdName() }}</b>
            (ID/Passport: <b>{{ $user->getIdNumber() ?? 'N/A' }}</b>) is a
            <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
            <br/><br/>
            This endorsement is issued for the following purpose(s):
            <span class="purpose-line">{{ $purposeText }}</span>
            Issued under the member's <b>{{ $request->dedicated_category_label }}</b> status.
            <br/><br/>
            The Association supports the member's application for the {{ $firearm ? 'firearm and ' : '' }}component(s) described above, issued under the member's compliant dedicated status, subject to compliance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
        </div>

        {{-- Bottom: QR verification + Signatory --}}
        <div class="bottom-grid">
            <div class="card verify-card">
                <div class="card-title">Verification</div>
                <div style="display:flex; gap:10px; align-items:flex-start; margin-top:4px;">
                    <div class="qr-box">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                    </div>
                    <div class="verify-text">
                        <strong>Verify this endorsement</strong>
                        Scan the QR code or visit the link below.
                        <br/>
                        <a href="{{ $verifyUrl }}" style="color:var(--blue); word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                        <br/>
                        Online status shown: <b>Member in Good Standing</b>
                    </div>
                </div>
            </div>

            <div class="card signatory-card">
                <div class="card-title">Authorised NRAPA Signatory</div>
                <div class="sig-box">{!! $signatureHtml !!}</div>
                <div style="font-size:8px; color:var(--muted); margin-top:2px;">Signature placeholder must remain white.</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $signatory['name'] }}</div>
                <div class="sig-title">{{ $signatory['title'] }}</div>
                <div class="sig-date">Issued at {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
            </div>
        </div>

        {{-- Commissioner of Oaths --}}
        <div class="commissioner">
            <div class="commissioner-title">Commissioner of Oaths (Scan Upload &mdash; Optional)</div>
            <div class="commissioner-sub">If required, upload the commissioned scan in the admin dashboard. Placeholder must remain white.</div>
            <div class="commissioner-box">
                @if($commissionerHtml && trim(strip_tags($commissionerHtml)))
                    {!! $commissionerHtml !!}
                @else
                    Commissioner of Oaths scan
                @endif
            </div>
        </div>


    </div>

    {{-- Orange footer bar --}}
    <div class="footer-bar">
        <div class="contacts">
            TEL: {{ $contact['tel'] }} &bull;
            FAX: {{ $contact['fax'] }} &bull;
            E-MAIL: {{ $contact['email'] }} &bull;
            ADDRESS: {{ $contact['physical_address'] }}
        </div>
        <div class="disclaimer">This document is generated electronically and is valid without a physical signature when verified via QR code</div>
    </div>

</div>
</body>
</html>
