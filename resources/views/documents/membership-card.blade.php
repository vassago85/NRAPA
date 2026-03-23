<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->user->getIdName() }} - Membership Card</title>
    <style>
        @page { size: 90mm 148mm; margin: 0; }
        @media print {
            body { background: #fff !important; }
            .preview-controls { display: none !important; }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e2e8f0;
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

        .card {
            width: 86mm;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
        }

        /* ── Header: NRAPA Blue ── */
        .card-header {
            background: linear-gradient(135deg, #0B4EA2 0%, #0a3d80 100%);
            padding: 4mm 5mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 3mm;
        }
        .logo-box {
            width: 9mm;
            height: 9mm;
            background: rgba(255,255,255,0.9);
            border-radius: 2.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .logo-box img {
            width: 7mm;
            height: 7mm;
            object-fit: contain;
        }
        .logo-box .logo-fallback {
            font-weight: 800;
            font-size: 5.5pt;
            color: #0B4EA2;
        }
        .org-name {
            font-weight: 800;
            font-size: 11pt;
            color: #fff;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .card-label {
            font-size: 6.5pt;
            color: rgba(255,255,255,0.8);
            font-weight: 600;
        }
        .status-badge {
            padding: 1mm 2.5mm;
            border-radius: 10px;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }
        .status-badge.active {
            background: #F58220;
            color: #fff;
        }
        .status-badge.expired {
            background: #ef4444;
            color: #fff;
        }

        /* ── Orange Accent Stripe ── */
        .accent-stripe {
            height: 1mm;
            background: linear-gradient(90deg, #F58220, #f9a825, #F58220);
        }

        /* ── Card Body: White ── */
        .card-body {
            padding: 4mm 5mm;
            background: #fff;
        }
        .field {
            margin-bottom: 3mm;
        }
        .field:last-child {
            margin-bottom: 0;
        }
        .field-label {
            font-size: 5.5pt;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0B4EA2;
            font-weight: 600;
            margin-bottom: 0.5mm;
        }
        .field-value {
            font-size: 8.5pt;
            color: #27272a;
            font-weight: 600;
            line-height: 1.3;
        }
        .field-value.name {
            font-size: 11pt;
            font-weight: 700;
            color: #18181b;
        }
        .field-value.mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
        }
        .field-value.expired-text {
            color: #ef4444;
        }
        .field-value.lifetime-text {
            color: #0B4EA2;
            font-weight: 700;
        }
        .field-row {
            display: flex;
            gap: 4mm;
            margin-bottom: 3mm;
        }
        .field-row .field {
            flex: 1;
            margin-bottom: 0;
        }

        /* ── QR Code Section ── */
        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 2mm;
            padding-top: 2mm;
        }
        .qr-box {
            padding: 1.5mm;
            border-radius: 2.5mm;
            background: #f8fafc;
            border: 0.5px solid #e4e4e7;
        }
        .qr-box img {
            width: 24mm;
            height: 24mm;
            display: block;
        }
        .qr-text {
            font-size: 5.5pt;
            color: #6b7280;
            margin-top: 1.5mm;
            text-align: center;
        }

        /* ── Footer: NRAPA Blue ── */
        .card-footer {
            background: #0B4EA2;
            padding: 2mm 5mm;
            text-align: center;
        }
        .card-footer-text {
            font-size: 5.5pt;
            color: rgba(255,255,255,0.9);
            font-weight: 600;
        }
    </style>
</head>
<body>
    @php
        $verificationUrl = isset($certificate) && $certificate->qr_code
            ? route('certificates.verify', ['qr_code' => $certificate->qr_code])
            : '#';
        $qrCodeUrl = \App\Helpers\QrCodeHelper::generateUrl($verificationUrl, 250);
        $logoUrl = $logo_url ?? \App\Helpers\DocumentDataHelper::getLogoUrl();

        $membership = $certificate->membership;
        $isExpired = $membership?->isExpired() ?? true;
        $isLifetime = $membership?->type?->is_lifetime ?? false;
        $validUntilLabel = $isLifetime ? 'Lifetime' : ($membership?->expires_at?->format('d M Y') ?? 'N/A');
    @endphp

    <div class="preview-controls">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="card">
        {{-- Header: NRAPA Blue --}}
        <div class="card-header">
            <div class="header-left">
                <div class="logo-box">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="NRAPA">
                    @else
                        <span class="logo-fallback">NRAPA</span>
                    @endif
                </div>
                <div>
                    <div class="org-name">NRAPA</div>
                    <div class="card-label">Member Card</div>
                </div>
            </div>
            @if($isExpired)
                <span class="status-badge expired">Expired</span>
            @else
                <span class="status-badge active">Active</span>
            @endif
        </div>

        {{-- Orange Accent Stripe --}}
        <div class="accent-stripe"></div>

        {{-- Body: White background --}}
        <div class="card-body">
            <div class="field">
                <div class="field-label">Member Name</div>
                <div class="field-value name">{{ $certificate->user->getIdName() }}</div>
            </div>

            <div class="field-row">
                <div class="field">
                    <div class="field-label">Membership No.</div>
                    <div class="field-value mono">{{ $membership->membership_number ?? 'N/A' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Type</div>
                    <div class="field-value">{{ $membership->type->name ?? 'Member' }}</div>
                </div>
            </div>

            <div class="field">
                <div class="field-label">Valid Until</div>
                @if($isLifetime)
                    <div class="field-value lifetime-text">Lifetime</div>
                @else
                    <div class="field-value {{ $isExpired ? 'expired-text' : '' }}">{{ $validUntilLabel }}</div>
                @endif
            </div>

            @if($certificate->qr_code)
            <div class="qr-section">
                <div class="qr-box">
                    <img src="{{ $qrCodeUrl }}" alt="Verification QR Code">
                </div>
                <div class="qr-text">Scan to verify membership</div>
            </div>
            @endif
        </div>

        {{-- Footer: NRAPA Blue --}}
        <div class="card-footer">
            <div class="card-footer-text">{{ $membership->membership_number ?? '—' }}</div>
        </div>
    </div>
</body>
</html>
