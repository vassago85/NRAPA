<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'NRAPA Document' }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            color: #1f2937;
            background: white;
            line-height: 1.6;
        }
        
        .document-container {
            position: relative;
            width: 100%;
            min-height: 100vh;
            background: white;
            padding: 2cm;
        }
        
        /* NRAPA Colors - Blue and Orange */
        .nrapa-blue { color: #1e40af; }
        .nrapa-orange { color: #ea580c; }
        .bg-nrapa-blue { background-color: #1e40af; }
        .bg-nrapa-orange { background-color: #ea580c; }
        
        /* Header */
        .document-header {
            border-bottom: 3px solid #1e40af;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-placeholder {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .header-text {
            flex: 1;
        }
        
        .header-text h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 0.25rem;
        }
        
        .header-text p {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Watermark (faded background) */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(30, 64, 175, 0.05);
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
        }
        
        /* Content */
        .document-content {
            position: relative;
            z-index: 1;
        }
        
        /* Footer */
        .document-footer {
            position: absolute;
            bottom: 1cm;
            left: 2cm;
            right: 2cm;
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
            }
            
            .document-container {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        {{-- Watermark --}}
        <div class="watermark">NRAPA</div>
        
        {{-- Header --}}
        <div class="document-header">
            <div class="logo-section">
                <div class="logo-placeholder">NRAPA</div>
                <div class="header-text">
                    <h1>National Rifle & Pistol Association</h1>
                    <p>of South Africa</p>
                </div>
            </div>
            <div style="text-align: right; font-size: 10px; color: #6b7280;">
                <p>FAR Accredited</p>
                <p>SAPS Recognised</p>
            </div>
        </div>
        
        {{-- Content --}}
        <div class="document-content">
            @yield('content')
        </div>
        
        {{-- Footer --}}
        <div class="document-footer">
            <p>National Rifle & Pistol Association of South Africa | www.nrapa.co.za</p>
            <p>This document is generated electronically and is valid without physical signature when verified via QR code.</p>
            @if(isset($certificate) && $certificate->qr_code)
                <p style="margin-top: 0.5rem;">Verification: {{ route('certificates.verify', ['qr_code' => $certificate->qr_code]) }}</p>
            @endif
        </div>
    </div>
</body>
</html>
