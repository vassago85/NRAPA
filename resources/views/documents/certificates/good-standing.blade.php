@extends('documents.layouts.nrapa-official')

@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
@endphp

@section('content')
<div class="header">
    @if($logoUrl)
        <img class="logo" src="{{ $logoUrl }}" alt="NRAPA Logo" />
    @else
        <div class="logo" style="background: var(--blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; border-radius: 4px;">NRAPA</div>
    @endif
    <div class="org">
        <div class="org-title">NATIONAL RIFLE &amp; PISTOL ASSOCIATION</div>
        <div class="org-sub">of South Africa</div>
        <div class="accreditation-badge">
            <span class="accreditation-dot"></span>
            <span>FAR Accredited | SAPS Recognised</span>
        </div>
        <div class="far-numbers" style="margin-top: 4px; font-size: 10px; color: var(--text);">
            <span><b>FAR Sport:</b> {{ $farNumbers['sport'] }}</span>
            <span style="margin-left: 10px;"><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>

<div class="titlebar">
    <div class="h1">PROOF OF MEMBERSHIP IN GOOD STANDING</div>
</div>

<hr class="sep"/>

<div class="grid">
    <section class="card">
        <div class="h2">Member Details</div>
        <div style="height:6px"></div>
        <div class="kv">
            <div class="k">Member Name</div><div class="v">{{ $certificate->user->getIdName() }}</div>
            <div class="k">ID Number</div><div class="v">{{ $certificate->user->id_number ?? 'N/A' }}</div>
            <div class="k">Membership No.</div><div class="v">{{ $certificate->membership->membership_number ?? 'N/A' }}</div>
            <div class="k">Membership Type</div><div class="v">{{ $certificate->membership->type->name ?? 'N/A' }}</div>
        </div>
    </section>

    <section class="card">
        <div class="h2">Certificate Details</div>
        <div style="height:6px"></div>
        <div class="kv">
            <div class="k">Certificate No.</div><div class="v">{{ $certificate->certificate_number }}</div>
            <div class="k">Issued Date</div><div class="v">{{ $certificate->issued_at->format('d F Y') }}</div>
            <div class="k">Valid Until</div><div class="v">{{ $certificate->valid_until ? $certificate->valid_until->format('d F Y') : 'Lifetime' }}</div>
        </div>
    </section>
</div>

<div style="height:6px"></div>

<section class="notice">
    This is to certify that <b>{{ $certificate->user->getIdName() }}</b> (ID: {{ $certificate->user->id_number ?? 'N/A' }}) is a <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
    This confirms that the member's membership is valid, current, and compliant with the Association's requirements at the date of issue.
</section>

<div style="height:6px"></div>

<div class="sig-grid">
    <section class="sig">
        <div class="h2">Verification</div>
        <div style="height:6px"></div>
        <div style="display:flex; gap:10px; align-items:center;">
            <div class="qr">
                <img src="{{ $qrCodeUrl }}" alt="QR Code" />
            </div>
            <div>
                <div style="font-size:11px; font-weight:700;">Verify this certificate</div>
                <div class="small">Scan QR code or visit:</div>
                <div class="small" style="word-break:break-all;"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
            </div>
        </div>
    </section>

    <section class="sig">
        <div class="h2">Authorised Signatory</div>
        <div style="height:6px"></div>
        <div class="placeholder-white signature-box">
            {!! $signatureHtml !!}
        </div>
        <div class="line"></div>
        <div style="font-weight:700; font-size:12px;">{{ $signatory['name'] }}</div>
        <div class="small">{{ $signatory['title'] }}</div>
    </section>
</div>

<div class="footer">
    <div style="flex: 1;">
        <div class="footer-contact">
            <span class="footer-contact-item"><b>TEL:</b> {{ $contact['tel'] }}</span>
            <span class="footer-contact-item"><b>E-MAIL:</b> {{ $contact['email'] }}</span>
        </div>
        <div style="margin-top: 4px; font-size: 9px; color: var(--muted);">
            This document is generated electronically and is valid without a physical signature when verified via QR code.
        </div>
    </div>
</div>
@endsection
