@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
@endphp

<div class="doc-wrapper">
    {{-- Blue Header --}}
    <div class="doc-header">
        <div class="doc-org">
            <div class="doc-logo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="NRAPA" />
                @else
                    <span class="doc-logo-fallback">NRAPA</span>
                @endif
            </div>
            <div class="doc-org-text">
                <div class="doc-org-name">NRAPA</div>
                <div class="doc-org-sub">National Rifle &amp; Pistol Association of South Africa</div>
            </div>
        </div>
        <span class="doc-badge">Member in Good Standing</span>
    </div>

    {{-- Orange Accent Stripe --}}
    <div class="doc-accent"></div>

    {{-- Title --}}
    <div class="doc-title">
        <h1>Proof of Membership in Good Standing</h1>
        <div class="doc-subtitle">FAR Sport: {{ $farNumbers['sport'] }} &nbsp;|&nbsp; FAR Hunting: {{ $farNumbers['hunting'] }}</div>
    </div>

    <hr class="sep" style="margin: 0 16px;"/>

    {{-- Content --}}
    <div style="padding: 10px 16px;">
        {{-- Details Grid --}}
        <div class="doc-grid">
            <div class="doc-section">
                <div class="doc-section-title">Member Details</div>
                <div class="doc-field">
                    <div class="doc-field-label">Member Name</div>
                    <div class="doc-field-value name">{{ $certificate->user->getIdName() }}</div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">ID / Passport Number</div>
                    <div class="doc-field-value mono">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</div>
                </div>
                <div class="doc-field-row">
                    <div class="doc-field">
                        <div class="doc-field-label">Membership No.</div>
                        <div class="doc-field-value mono">{{ $certificate->membership->membership_number ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label">Membership Type</div>
                        <div class="doc-field-value">{{ $certificate->membership->type->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div class="doc-section">
                <div class="doc-section-title">Certificate Details</div>
                <div class="doc-field">
                    <div class="doc-field-label">Certificate Number</div>
                    <div class="doc-field-value mono">{{ $certificate->certificate_number }}</div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Date Issued</div>
                    <div class="doc-field-value">{{ $certificate->issued_at->format('d F Y') }}</div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Valid Until</div>
                    <div class="doc-field-value">{{ $certificate->valid_until ? $certificate->valid_until->format('d F Y') : 'Lifetime' }}</div>
                </div>
            </div>
        </div>

        <div style="height:8px"></div>

        {{-- Certification Statement --}}
        <div class="doc-notice">
            This is to certify that <b>{{ $certificate->user->getIdName() }}</b> (ID: {{ $certificate->user->getIdNumber() ?? 'N/A' }})
            is a <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
            This confirms that the member's membership is valid, current, and compliant with the Association's requirements at the date of issue.
        </div>

        <div style="height:8px"></div>

        {{-- QR + Signatory --}}
        <div class="doc-grid">
            <div class="doc-qr-section">
                <div class="doc-qr-box">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code" />
                </div>
                <div class="doc-qr-text">
                    <span class="verify-label">Verify Certificate</span>
                    Scan the QR code or visit:<br/>
                    <a href="{{ $verifyUrl }}" style="font-size:8px;">{{ $verifyUrl }}</a>
                </div>
            </div>

            <div class="doc-signatory">
                <div class="doc-field-label">Authorised Signatory</div>
                <div style="height:4px"></div>
                <div class="placeholder-white signature-box">
                    {!! $signatureHtml !!}
                </div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $signatory['name'] }}</div>
                <div class="sig-title">{{ $signatory['title'] }}</div>
            </div>
        </div>
    </div>

    {{-- Blue Footer --}}
    <div class="doc-footer">
        <div>
            <span class="doc-footer-cert">{{ $certificate->certificate_number }}</span>
            &nbsp;&mdash;&nbsp;
            <span>{{ $contact['email'] }} | {{ $contact['tel'] }}</span>
        </div>
        <div class="doc-footer-far">
            FAR Sport: <span class="far-sport">{{ $farNumbers['sport'] }}</span>
            &nbsp;|&nbsp; Hunting: <span class="far-hunting">{{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>

<div style="margin-top: 4px; text-align: center; font-size: 8px; color: var(--muted);">
    This document is generated electronically and is valid without a physical signature when verified via QR code.
</div>
@endsection
