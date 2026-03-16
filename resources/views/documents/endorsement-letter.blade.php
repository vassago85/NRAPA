@extends('documents.base')

@php
    // Endorsement letters may have QR codes if they're stored as certificates
    $verificationUrl = isset($request) && $request->qr_code 
        ? route('certificates.verify', ['qr_code' => $request->qr_code])
        : (isset($request) && $request->letter_reference 
            ? url('/verify/endorsement/' . $request->letter_reference)
            : '#');
    $qrCodeUrl = $verificationUrl !== '#' ? \App\Helpers\QrCodeHelper::generateUrl($verificationUrl, 256) : null;
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
@endphp

@section('content')
<div class="doc-header">
    <div class="doc-logo">
        @if(isset($logo_url))
            <img src="{{ $logo_url }}" alt="NRAPA">
        @else
            <div style="width:100%; height:100%; background:linear-gradient(135deg, #0f4c81 0%, #3b82f6 100%); display:grid; place-items:center; color:#fff; font-weight:bold; font-size:10pt;">NRAPA</div>
        @endif
    </div>
    <div class="doc-org">
        <h1>National Rifle & Pistol Association</h1>
        <div class="sub">of South Africa</div>
        <div class="doc-badge">
            <span class="dot"></span>
            <span>FAR Accredited | SAPS Recognised</span>
        </div>
        <div style="margin-top: 8px; font-size: 11px; color: var(--text);">
            <span><b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}</span>
            <span style="margin-left: 12px;"><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>

<div class="doc-title">
    <h2>Endorsement Letter</h2>
    <div class="note">Firearm Licence Application</div>
</div>

<div class="doc-block">
    <div class="doc-row">
        <span class="doc-label">Reference:</span>
        <span class="doc-value">{{ $request->letter_reference ?? 'N/A' }}</span>
    </div>
    <div class="doc-row" style="margin-top:6px;">
        <span class="doc-label">Date:</span>
        <span class="doc-value">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</span>
    </div>
</div>

<div class="doc-block">
    <p class="doc-para"><strong>To Whom It May Concern,</strong></p>
    
    <p class="doc-para" style="margin-top:12px;">
        This letter serves to confirm that <strong>{{ $user->getIdName() }}</strong> (ID: {{ $user->getIdNumber() ?? 'N/A' }}, Membership Number: {{ $membership?->membership_number ?? 'N/A' }}) 
        is a member in good standing of the National Rifle & Pistol Association of South Africa (NRAPA).
    </p>
    
    @if($request->firearm)
    <p class="doc-para" style="margin-top:12px;">
        We hereby endorse the application for a Section 16 firearm licence for the following firearm:
    </p>
    
    <div class="doc-block" style="margin-top:12px; background:rgba(15,76,129,.05);">
        <h3 style="margin-bottom:8px;">Firearm Details</h3>
        <div class="doc-row">
            <span class="doc-label">Firearm Type:</span>
            <span class="doc-value">{{ $request->firearm->firearm_type_label ?? 'N/A' }}</span>
        </div>
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Make:</span>
            <span class="doc-value">{{ $request->firearm->make_display ?? $request->firearm->make ?? 'N/A' }}</span>
        </div>
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Model:</span>
            <span class="doc-value">{{ $request->firearm->model_display ?? $request->firearm->model ?? 'N/A' }}</span>
        </div>
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Calibre:</span>
            <span class="doc-value">{{ $request->firearm->calibre_display ?? 'N/A' }}</span>
        </div>
        @if($request->firearm->action_type)
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Action:</span>
            <span class="doc-value">{{ ucfirst(str_replace('_', ' ', $request->firearm->action_type)) }}</span>
        </div>
        @endif
        @if($request->components && $request->components->isNotEmpty())
        <div style="margin-top:8px;">
            <span class="doc-label">Serial Numbers:</span>
            <ul class="doc-list" style="margin-top:4px;">
                @foreach($request->components as $component)
                    <li>{{ ucfirst($component->type) }}: {{ $component->serial ?? 'N/A' }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <p class="doc-para" style="margin-top:12px;">
        The member has demonstrated their commitment to responsible firearm ownership and participation in dedicated status activities 
        as required by NRAPA membership standards.
    </p>

    <p class="doc-para" style="margin-top:12px;">
        This endorsement is valid for the purpose of supporting the member's application for a Section 16 firearm licence 
        with the South African Police Service (SAPS).
    </p>
</div>

<div class="doc-block" style="margin-top:auto;">
    <p class="doc-para" style="margin-bottom:12px;">Yours sincerely,</p>
    <div class="doc-signature">
        <div class="doc-value">NRAPA Administration</div>
        <div class="doc-label" style="margin-top:4px;">National Rifle & Pistol Association of South Africa</div>
    </div>
</div>

@if($qrCodeUrl)
<div class="doc-qr">
    <div class="doc-qr-box">
        <img src="{{ $qrCodeUrl }}" alt="QR Code">
    </div>
    <div class="doc-qr-text">
        <strong>Verify this letter:</strong><br>
        Scan the QR code or visit:<br>
        <span class="doc-qr-link">{{ $verificationUrl }}</span>
    </div>
</div>
@endif

<div class="doc-footer">
    <div>
        <strong>National Rifle & Pistol Association of South Africa</strong><br>
        nrapa.ranyati.co.za
    </div>
    <div style="text-align:right;">
        @if($request->letter_reference)
            Reference: {{ $request->letter_reference }}
        @endif
    </div>
</div>
@endsection
