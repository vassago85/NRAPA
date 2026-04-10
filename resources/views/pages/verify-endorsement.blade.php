<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endorsement Verification - NRAPA</title>
    <meta name="description" content="Verify the authenticity of NRAPA endorsement letters using reference code verification.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NRAPA">
    <meta property="og:title" content="Endorsement Verification - NRAPA">
    <meta property="og:description" content="Verify the authenticity of NRAPA endorsement letters.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Endorsement Verification - NRAPA">
    <meta name="twitter:description" content="Verify the authenticity of NRAPA endorsement letters.">
    <meta name="robots" content="noindex">
    <link rel="icon" href="/nrapa-icon.png" type="image/png">
    <link rel="apple-touch-icon" href="/nrapa-icon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f4f6fa;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1e40af;
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header img {
            display: block;
            margin: 0 auto 14px;
            height: 52px;
            width: auto;
            max-width: min(100%, 200px);
            object-fit: contain;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
        }
        .body {
            padding: 32px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .status-valid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-invalid {
            background: #fee2e2;
            color: #991b1b;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .info-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }
        .error-message {
            padding: 16px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            text-align: center;
        }
        @media (max-width: 640px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('logo-nrapa-white.png') }}" alt="NRAPA" width="200" height="74" />
            <h1>Endorsement Verification</h1>
        </div>
        <div class="body">
            @if($error)
                <div class="error-message">
                    {{ $error }}
                </div>
            @elseif($request)
                <div class="status-badge status-valid">
                    ✓ Valid Endorsement
                </div>
                
                @if($member_info)
                    <div style="margin-bottom: 24px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                            <div class="info-label" style="margin-bottom: 4px;">Name</div>
                            <div class="info-value" style="font-weight: 600;">{{ $member_info['display_name'] }}</div>
                        </div>
                        @if(!empty($member_info['id_masked']))
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #fff;">
                            <div class="info-label" style="margin-bottom: 4px;">ID Number</div>
                            <div class="info-value" style="font-weight: 600;">{{ $member_info['id_masked'] }}</div>
                        </div>
                        @endif
                        <div style="padding: 12px 16px; background: #fff;">
                            <div class="info-label" style="margin-bottom: 4px;">Membership Number</div>
                            <div class="info-value" style="font-weight: 600;">{{ $member_info['membership_number'] }}</div>
                        </div>
                    </div>
                @endif

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Endorsement Ref</div>
                        <div class="info-value">{{ $request->letter_reference ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Issued Date</div>
                        <div class="info-value">{{ $request->issued_at?->format('d F Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Membership Status</div>
                        <div class="info-value">Member in Good Standing</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dedicated Status</div>
                        <div class="info-value">{{ $request->dedicated_status_label }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dedicated Category</div>
                        <div class="info-value">{{ $request->dedicated_category_label }}</div>
                    </div>
                    @if($request->expires_at)
                    <div class="info-item">
                        <div class="info-label">Expires</div>
                        <div class="info-value">{{ $request->expires_at->format('d F Y') }}</div>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</body>
</html>
