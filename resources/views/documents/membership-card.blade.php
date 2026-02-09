<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->user->getIdName() }} - Membership Card</title>
    <style>
        @page { size: 86mm 54mm; margin: 0; }
        @media print {
            body { background: #fff !important; }
            .preview-controls { display: none !important; }
            .doc-card { box-shadow: none !important; margin: 0 !important; }
        }

        :root {
            --accent: #0f4c81;
            --accent2: #f28c28;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .preview-controls {
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
        }
        .preview-controls button {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        .preview-controls button:hover {
            background: #f1f5f9;
        }

        .doc-card {
            width: 86mm;
            height: 54mm;
            border-radius: 14px;
            position: relative;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.25);
        }

        .doc-card-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #0b1320 0%, #1a365d 40%, #0f4c81 70%, #1e3a5f 100%);
        }

        .doc-card-watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.08;
        }
        .doc-card-watermark img {
            width: 44mm;
            height: auto;
            filter: grayscale(1) contrast(1.1);
        }

        .doc-card-inner {
            position: relative;
            padding: 7mm 8mm 6mm 8mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .doc-card-top {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .doc-card-logo {
            width: 13mm;
            height: 13mm;
            border-radius: 8px;
            background: #fff;
            border: 1px solid rgba(255,255,255,.25);
            overflow: hidden;
            flex: 0 0 auto;
        }
        .doc-card-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2px;
        }

        .doc-card-org {
            font-weight: 800;
            font-size: 10pt;
            text-transform: uppercase;
            color: #fff;
            line-height: 1.1;
        }

        .doc-card-meta {
            font-size: 8pt;
            color: rgba(255,255,255,.9);
            line-height: 1.25;
        }

        .doc-card-far {
            font-size: 5.5pt;
            color: rgba(255,255,255,.8);
            margin-top: 2px;
            line-height: 1.2;
        }

        .doc-card-body {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 8px;
            margin-top: 4px;
        }

        .doc-card-name {
            font-weight: 800;
            font-size: 10pt;
            color: #fff;
        }

        .doc-card-small {
            font-size: 8pt;
            color: rgba(255,255,255,.88);
            margin-top: 2px;
        }

        .doc-card-qr {
            width: 15mm;
            height: 15mm;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(255,255,255,.88);
            border: 1px solid rgba(255,255,255,.35);
            flex: 0 0 auto;
        }
        .doc-card-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .doc-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 7pt;
            color: rgba(255,255,255,.88);
        }
        .doc-card-footer strong {
            color: #fff;
        }
    </style>
</head>
<body>
    @php
        $verificationUrl = isset($certificate) && $certificate->qr_code
            ? route('certificates.verify', ['qr_code' => $certificate->qr_code])
            : '#';
        $qrCodeUrl = \App\Helpers\QrCodeHelper::generateUrl($verificationUrl, 120);
        $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
        $logoUrl = $logo_url ?? \App\Helpers\DocumentDataHelper::getLogoUrl();
    @endphp

    <div class="preview-controls">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="doc-card">
        <div class="doc-card-bg"></div>

        <div class="doc-card-watermark">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="NRAPA">
            @else
                <div style="font-size: 36pt; font-weight: bold; color: rgba(255,255,255,.5);">NRAPA</div>
            @endif
        </div>

        <div class="doc-card-inner">
            {{-- Top: Logo + Org Name --}}
            <div class="doc-card-top">
                <div class="doc-card-logo">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="NRAPA">
                    @else
                        <div style="width:100%; height:100%; background:linear-gradient(135deg, #0f4c81 0%, #3b82f6 100%); display:grid; place-items:center; color:#fff; font-weight:bold; font-size:7pt;">NRAPA</div>
                    @endif
                </div>
                <div>
                    <div class="doc-card-org">NRAPA</div>
                    <div class="doc-card-meta">Membership Card</div>
                    <div class="doc-card-far">
                        FAR Sport: {{ $farNumbers['sport'] }} | Hunting: {{ $farNumbers['hunting'] }}
                    </div>
                </div>
            </div>

            {{-- Middle: Member Details + QR --}}
            <div class="doc-card-body">
                <div>
                    <div class="doc-card-name">{{ $certificate->user->getIdName() }}</div>
                    <div class="doc-card-small">
                        ID: {{ $certificate->user->id_number ?? 'N/A' }}
                    </div>
                    <div class="doc-card-small">
                        #{{ $certificate->membership->membership_number ?? 'N/A' }}
                    </div>
                    @if($certificate->membership->type)
                    <div class="doc-card-small" style="margin-top:3px;">
                        {{ $certificate->membership->type->name }}
                    </div>
                    @endif
                </div>
                @if($certificate->qr_code)
                <div class="doc-card-qr">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code">
                </div>
                @endif
            </div>

            {{-- Bottom: Dates --}}
            <div class="doc-card-footer">
                <div>
                    Enrolled: {{ $certificate->membership->activated_at?->format('M Y') ?? $certificate->membership->applied_at?->format('M Y') ?? 'N/A' }}
                </div>
                <div style="text-align:right;">
                    @if($certificate->membership->expires_at)
                        Expires: {{ $certificate->membership->expires_at->format('M Y') }}
                    @else
                        <strong>Lifetime</strong>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
