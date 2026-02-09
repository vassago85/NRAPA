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
            background: linear-gradient(145deg, #062d6e 0%, #0B4EA2 30%, #0d5ab8 60%, #0B4EA2 85%, #073878 100%);
        }

        /* Subtle light sweep across the card */
        .doc-card-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 20%, rgba(255,255,255,0.08) 0%, transparent 60%);
        }

        .doc-card-inner {
            position: relative;
            padding: 5.5mm 6mm 5mm 6mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* ── Top row: logo + org ── */
        .doc-card-top {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .doc-card-logo {
            width: 11mm;
            height: 11mm;
            flex: 0 0 auto;
        }
        .doc-card-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,.35));
        }

        .doc-card-org {
            font-weight: 800;
            font-size: 11pt;
            text-transform: uppercase;
            color: #fff;
            line-height: 1.1;
            letter-spacing: 0.5px;
        }

        .doc-card-subtitle {
            font-size: 7.5pt;
            color: rgba(255,255,255,.85);
            font-weight: 600;
            line-height: 1.3;
        }

        .doc-card-far {
            font-size: 5.5pt;
            color: rgba(255,255,255,.7);
            margin-top: 1.5px;
            line-height: 1.2;
        }
        .doc-card-far .far-label {
            color: rgba(255,255,255,.55);
        }
        .doc-card-far .far-sport {
            color: #F58220;
            font-weight: 700;
        }
        .doc-card-far .far-hunting {
            color: #fbbf24;
            font-weight: 700;
        }

        /* ── Middle: member info + QR ── */
        .doc-card-body {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 6px;
        }

        .doc-card-name {
            font-weight: 800;
            font-size: 10pt;
            color: #fff;
            line-height: 1.2;
        }

        .doc-card-detail {
            font-size: 6.5pt;
            color: rgba(255,255,255,.82);
            line-height: 1.35;
        }

        .doc-card-type {
            font-size: 6.5pt;
            color: rgba(255,255,255,.72);
            font-style: italic;
            margin-top: 1px;
        }

        .doc-card-qr {
            width: 14mm;
            height: 14mm;
            border-radius: 6px;
            overflow: hidden;
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(255,255,255,.3);
            flex: 0 0 auto;
            padding: 1px;
        }
        .doc-card-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ── Footer: dates ── */
        .doc-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 6pt;
            color: rgba(255,255,255,.65);
        }
        .doc-card-footer .footer-value {
            color: rgba(255,255,255,.9);
            font-weight: 600;
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

        <div class="doc-card-inner">
            {{-- Top: Logo + Org Name --}}
            <div class="doc-card-top">
                <div class="doc-card-logo">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="NRAPA">
                    @else
                        <div style="width:100%; height:100%; display:grid; place-items:center; color:#fff; font-weight:bold; font-size:8pt; text-shadow: 0 1px 3px rgba(0,0,0,.5);">NRAPA</div>
                    @endif
                </div>
                <div>
                    <div class="doc-card-org">NRAPA</div>
                    <div class="doc-card-subtitle">Membership Card</div>
                    <div class="doc-card-far">
                        <span class="far-label">FAR</span> Sport: <span class="far-sport">{{ $farNumbers['sport'] }}</span>
                        | Hunting: <span class="far-hunting">{{ $farNumbers['hunting'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Middle: Member Details + QR --}}
            <div class="doc-card-body">
                <div>
                    <div class="doc-card-name">{{ $certificate->user->getIdName() }}</div>
                    <div class="doc-card-detail">
                        ID: {{ $certificate->user->id_number ?? 'N/A' }}
                    </div>
                    <div class="doc-card-detail">
                        #{{ $certificate->membership->membership_number ?? 'N/A' }}
                    </div>
                    @if($certificate->membership->type)
                    <div class="doc-card-type">
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
                    Enrolled: <span class="footer-value">{{ $certificate->membership->activated_at?->format('M Y') ?? $certificate->membership->applied_at?->format('M Y') ?? 'N/A' }}</span>
                </div>
                <div style="text-align:right;">
                    @if($certificate->membership->expires_at)
                        Expires: <span class="footer-value">{{ $certificate->membership->expires_at->format('M Y') }}</span>
                    @else
                        <span class="footer-value">Lifetime</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
