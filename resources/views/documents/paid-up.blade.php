@extends('documents.base')

@php
    $variant = 'doc--certificate';
    $verificationUrl = isset($certificate) && $certificate->qr_code 
        ? route('certificates.verify', ['qr_code' => $certificate->qr_code])
        : '#';
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
    <h2>Proof of Paid-Up Membership</h2>
    <div class="note">Certificate</div>
</div>

<div class="doc-block">
    <p class="doc-para" style="margin-bottom:12px;">This is to certify that</p>
    
    <div style="text-align:center; margin:20px 0;">
        <p style="font-size:22pt; font-weight:700; color:#0b1320; margin-bottom:6px;">
            {{ $certificate->user->getIdName() }}
        </p>
        <p class="doc-label" style="font-size:10pt;">ID Number: {{ $certificate->user->id_number ?? 'N/A' }}</p>
    </div>
    
    <div class="doc-row" style="margin-top:16px;">
        <span class="doc-label">Membership Number:</span>
        <span class="doc-value">{{ $certificate->membership->membership_number ?? 'N/A' }}</span>
    </div>
    
    @if($certificate->membership->type)
    <div class="doc-row" style="margin-top:8px;">
        <span class="doc-label">Membership Type:</span>
        <span class="doc-value">{{ $certificate->membership->type->name }}</span>
    </div>
    @endif
    
    <p class="doc-para" style="margin-top:16px;">
        is a <strong>paid-up member in good standing</strong> of the National Rifle & Pistol Association of South Africa.
    </p>
    
    @if($certificate->membership->expires_at)
    <div class="doc-row" style="margin-top:12px;">
        <span class="doc-label">Membership Valid Until:</span>
        <span class="doc-value">{{ $certificate->membership->expires_at->format('d F Y') }}</span>
    </div>
    @else
    <div class="doc-row" style="margin-top:12px;">
        <span class="doc-label">Membership Type:</span>
        <span class="doc-value">Lifetime</span>
    </div>
    @endif
    
    <p class="doc-para" style="margin-top:12px;">
        This certificate confirms that the member's account is current and all membership fees have been paid.
        The member is in good standing with the Association.
    </p>
</div>

<div class="doc-bottom" style="margin-top:auto;">
    <div class="doc-block">
        <div class="doc-row">
            <span class="doc-label">Issued Date:</span>
            <span class="doc-value">{{ $certificate->issued_at->format('d F Y') }}</span>
        </div>
        @if($certificate->valid_until)
        <div class="doc-row" style="margin-top:8px;">
            <span class="doc-label">Valid Until:</span>
            <span class="doc-value">{{ $certificate->valid_until->format('d F Y') }}</span>
        </div>
        @endif
    </div>
    
    <div class="doc-block">
        <div class="doc-signature">
            <div class="doc-label" style="margin-bottom:4px;">Authorised Signatory</div>
            <div class="doc-value">NRAPA Administration</div>
        </div>
    </div>
</div>

@if($qrCodeUrl)
<div class="doc-qr">
    <div class="doc-qr-box">
        <img src="{{ $qrCodeUrl }}" alt="QR Code">
    </div>
    <div class="doc-qr-text">
        <strong>Verify this certificate:</strong><br>
        Scan the QR code or visit:<br>
        <span class="doc-qr-link">{{ $verificationUrl }}</span>
    </div>
</div>
@endif

@if($certificate->certificate_number)
<div style="text-align:center; margin-top:12px; font-size:9pt; color:var(--muted);">
    Certificate Number: {{ $certificate->certificate_number }}
</div>
@endif

<div class="doc-footer">
    <div>
        <strong>National Rifle & Pistol Association of South Africa</strong><br>
        www.nrapa.co.za
    </div>
    <div style="text-align:right;">
        This document is generated electronically and is valid without physical signature when verified via QR code.
    </div>
</div>
@endsection
