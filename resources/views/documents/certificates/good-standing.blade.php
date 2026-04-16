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
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card">
                    <div class="card-title">Member Details</div>
                    <table class="kv-table">
                        <tr><td class="kv-label">Full Name</td><td class="kv-value">{{ $certificate->user->getIdName() }}</td></tr>
                        <tr><td class="kv-label">ID / Passport</td><td class="kv-value">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Membership No.</td><td class="kv-value">{{ $certificate->membership->membership_number ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Membership Type</td><td class="kv-value">
                            {{ $certificate->membership->type->name ?? 'N/A' }}
                            @if($certificate->membership->type->dedicated_type)
                                <br/><span style="font-size:9px; color:#6a6a6a;">
                                    @if($certificate->membership->type->dedicated_type === 'both')
                                        (Dedicated Hunter &amp; Sport Shooter)
                                    @elseif($certificate->membership->type->dedicated_type === 'hunter')
                                        (Dedicated Hunter)
                                    @else
                                        (Dedicated Sport Shooter)
                                    @endif
                                </span>
                            @endif
                        </td></tr>
                    </table>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">Certificate Details</div>
                    <table class="kv-table">
                        <tr><td class="kv-label">Certificate No.</td><td class="kv-value">{{ $certificate->certificate_number }}</td></tr>
                        <tr><td class="kv-label">Date Issued</td><td class="kv-value">{{ $certificate->issued_at->format('d F Y') }}</td></tr>
                        <tr><td class="kv-label">Valid Until</td><td class="kv-value">{{ $certificate->valid_until ? $certificate->valid_until->format('d F Y') : 'Lifetime' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Certification statement --}}
    <div class="letter-body">
        This is to certify that <b>{{ $certificate->user->getIdName() }}</b>
        (ID: {{ $certificate->user->getIdNumber() ?? 'N/A' }})
        is a <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
        This confirms that the member's membership is valid, current, and compliant with the Association's requirements at the date of issue.
    </div>

    {{-- Signatory + Verification --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card signatory-card">
                    <div class="card-title">Authorised Signatory</div>
                    <div class="sig-box">{!! $signatureHtml !!}</div>
                    <div class="sig-name">{{ $signatory['name'] }}</div>
                    <div class="sig-title">{{ $signatory['title'] }}</div>
                    <div class="sig-date">Issued {{ $certificate->issued_at->format('d F Y') }}</div>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">Verify Certificate</div>
                    <table style="width:100%; border-collapse:collapse; margin-top:4px;">
                        <tr>
                            <td style="width:85px; vertical-align:top; padding:0;">
                                <div class="qr-box">
                                    <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                                </div>
                            </td>
                            <td class="verify-text" style="vertical-align:top;">
                                <strong>Scan to verify</strong>
                                Scan the QR code or visit the link below to confirm this certificate.
                                <br/>
                                <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top:8px; text-align:center; font-size:9px; color:#6a6a6a;">
        This is an electronically generated document. It can be verified by scanning the QR code above.
    </div>
@endsection
