@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);

    $title = 'Membership Certificate — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Membership Certificate</div>
    <div class="doc-banner-subtitle">Member in Good Standing</div>
</div>
@endsection

@section('content')
    {{-- Info grid: Member + Certificate details --}}
    <div class="info-grid">
        <div class="card">
            <div class="card-title">Member Details</div>
            <div class="kv-row">
                <span class="kv-label">Full Name</span>
                <span class="kv-value">{{ $certificate->user->getIdName() }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">ID / Passport</span>
                <span class="kv-value">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Membership No.</span>
                <span class="kv-value">{{ $certificate->membership->membership_number ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Membership Type</span>
                <span class="kv-value">{{ $certificate->membership->type->name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Certificate Details</div>
            <div class="kv-row">
                <span class="kv-label">Certificate No.</span>
                <span class="kv-value">{{ $certificate->certificate_number }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Date Issued</span>
                <span class="kv-value">{{ $certificate->issued_at->format('d F Y') }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Valid Until</span>
                <span class="kv-value">{{ $certificate->valid_until ? $certificate->valid_until->format('d F Y') : 'Lifetime' }}</span>
            </div>
        </div>
    </div>

    {{-- Certification statement --}}
    <div class="letter-body">
        This is to certify that <b>{{ $certificate->user->getIdName() }}</b>
        (ID: {{ $certificate->user->getIdNumber() ?? 'N/A' }})
        is a <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
        This confirms that the member's membership is valid, current, and compliant with the Association's requirements at the date of issue.
    </div>

    {{-- Signatory + Verification --}}
    <div class="bottom-grid">
        <div class="card signatory-card">
            <div class="card-title">Authorised Signatory</div>
            <div class="sig-box">{!! $signatureHtml !!}</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $signatory['name'] }}</div>
            <div class="sig-title">{{ $signatory['title'] }}</div>
            <div class="sig-date">Issued {{ $certificate->issued_at->format('d F Y') }}</div>
        </div>

        <div class="card">
            <div class="card-title">Verify Certificate</div>
            <div style="display:flex; gap:10px; align-items:flex-start; margin-top:4px;">
                <div class="qr-box">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                </div>
                <div class="verify-text">
                    <strong>Scan to verify</strong>
                    Scan the QR code or visit the link below to confirm this certificate.
                    <br/>
                    <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:8px; text-align:center; font-size:9px; color:var(--muted);">
        This document is generated electronically and is valid without a physical signature when verified via QR code.
    </div>
@endsection
