@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = null;
    $verifyUrl = '#';
    if (isset($certificate) && $certificate) {
        $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
        $verifyUrl = $certificate->getVerificationUrl();
    }

    $user = $certificate->user ?? $user ?? null;
    $membership = $certificate->membership ?? $membership ?? null;
    if ($membership && !$membership->relationLoaded('type')) {
        $membership->loadMissing('type');
    }

    $signatory = isset($certificate) ? \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate) : [
        'name' => \App\Models\SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
        'title' => \App\Models\SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
    ];
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(
        isset($certificate) ? $certificate->signatory_signature_path : \App\Helpers\DocumentDataHelper::getDefaultSignaturePath()
    );

    $title = 'Welcome Letter — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Welcome Letter</div>
    <div class="doc-banner-subtitle">Informational</div>
</div>
@endsection

@section('content')
    @if (!$user)
        <div class="letter-body" style="color:#c0392b;">
            Unable to load member details for this welcome letter.
        </div>
    @else
        {{-- Info grid: Member + Membership details --}}
        <table class="layout-table">
            <tr>
                <td class="half">
                    <div class="card">
                        <div class="card-title">Member Details</div>
                        <table class="kv-table">
                            <tr><td class="kv-label">Full Name</td><td class="kv-value">{{ $user->getIdName() }}</td></tr>
                            <tr><td class="kv-label">ID / Passport</td><td class="kv-value">{{ $user->getIdNumber() ?? 'N/A' }}</td></tr>
                            @if ($membership)
                            <tr><td class="kv-label">Membership No.</td><td class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</td></tr>
                            <tr><td class="kv-label">Membership Type</td><td class="kv-value">{{ $membership->type?->name ?? 'N/A' }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
                <td class="half">
                    <div class="card">
                        <div class="card-title">Letter Details</div>
                        <table class="kv-table">
                            @if (isset($certificate) && $certificate->certificate_number)
                            <tr><td class="kv-label">Reference</td><td class="kv-value">{{ $certificate->certificate_number }}</td></tr>
                            @endif
                            <tr><td class="kv-label">Date Issued</td><td class="kv-value">{{ now()->format('d F Y') }}</td></tr>
                            @if ($membership && $membership->expires_at)
                            <tr><td class="kv-label">Valid Until</td><td class="kv-value">{{ $membership->expires_at->format('d F Y') }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Letter body --}}
        <div class="letter-body">
            Dear {{ $user->getIdName() }},<br/><br/>

            On behalf of the National Rifle &amp; Pistol Association of South Africa, I would like to extend a warm welcome
            to you as a new member of our Association.<br/><br/>

            Your membership has been activated and you are now part of a community dedicated to promoting
            responsible firearm ownership, marksmanship, and the advancement of shooting sports in South Africa.

            @if ($membership)
            <table class="kv-table" style="margin:10px 0; padding:10px 14px; background:#f2f8ff; border:1px solid #d0dff0; border-radius:4px;">
                <tr><td class="kv-label" style="color:#0B4EA2;">Membership Number:</td><td class="kv-value" style="font-weight:700;">{{ $membership->membership_number ?? 'N/A' }}</td></tr>
                <tr><td class="kv-label" style="color:#0B4EA2;">Membership Type:</td><td class="kv-value">{{ $membership->type?->name ?? 'N/A' }}</td></tr>
                <tr><td class="kv-label" style="color:#0B4EA2;">Valid Until:</td><td class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</td></tr>
            </table>
            @endif

            As a member of NRAPA, you have access to a range of benefits and services, including:<br/>
            <ul style="margin:4px 0 8px 0; padding-left:18px; line-height:1.6;">
                <li>Endorsement letters for firearm licence applications</li>
                <li>Dedicated status certification (subject to meeting requirements)</li>
                <li>Activity tracking and compliance management</li>
                <li>Access to member resources and support</li>
                <li>Participation in NRAPA events and activities</li>
            </ul>

            We encourage you to explore your member portal where you can manage your membership, submit
            activities, request endorsements, and access important documents.<br/><br/>

            If you have any questions or need assistance, please do not hesitate to contact our administration team.<br/><br/>

            Once again, welcome to NRAPA. We look forward to supporting you in your shooting journey.
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
                        <div class="sig-date">Issued {{ now()->format('d F Y') }}</div>
                    </div>
                </td>
                <td class="half">
                    @if ($qrCodeUrl)
                    <div class="card">
                        <div class="card-title">Verify Document</div>
                        <table style="width:100%; border-collapse:collapse; margin-top:4px;">
                            <tr>
                                <td style="width:85px; vertical-align:top; padding:0;">
                                    <div class="qr-box">
                                        <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                                    </div>
                                </td>
                                <td class="verify-text" style="vertical-align:top;">
                                    <strong>Scan to verify</strong>
                                    Scan the QR code or visit the link below to confirm this document.
                                    <br/>
                                    <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        <div style="margin-top:8px; text-align:center; font-size:9px; color:#6a6a6a;">
            This is an electronically generated document. It can be verified by scanning the QR code above.
        </div>
    @endif
@endsection
