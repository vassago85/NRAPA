@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = null;
    $verifyUrl = null;
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

    $issuedAt = isset($certificate) && $certificate->issued_at ? $certificate->issued_at : now();

    $title = 'Welcome Letter — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Welcome Letter</div>
    <div class="doc-banner-subtitle">Welcome to the NRAPA Family</div>
</div>
@endsection

@section('content')
    @if (!$user)
        <div class="letter-body" style="color:#c0392b;">
            Unable to load member details for this welcome letter.
        </div>
    @else
        {{-- Info grid: Member + Letter details --}}
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
                                <tr><td class="kv-label">Membership Type</td><td class="kv-value">
                                    {{ $membership->type?->name ?? 'N/A' }}
                                    @if ($membership->type?->dedicated_type)
                                        <br/><span style="font-size:9px; color:#6a6a6a;">
                                            @if ($membership->type->dedicated_type === 'both')
                                                (Dedicated Hunter &amp; Sport Shooter)
                                            @elseif ($membership->type->dedicated_type === 'hunter')
                                                (Dedicated Hunter)
                                            @else
                                                (Dedicated Sport Shooter)
                                            @endif
                                        </span>
                                    @endif
                                </td></tr>
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
                            <tr><td class="kv-label">Date Issued</td><td class="kv-value">{{ $issuedAt->format('d F Y') }}</td></tr>
                            @if ($membership)
                                <tr><td class="kv-label">Valid Until</td><td class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Welcome statement --}}
        <div class="letter-body">
            Dear <b>{{ $user->getIdName() }}</b>,
            <br/><br/>
            On behalf of the National Rifle &amp; Pistol Association of South Africa, I extend a warm welcome to you as a new member.
            Your membership has been activated and you are now part of a community dedicated to promoting responsible firearm ownership,
            marksmanship, and the advancement of shooting sports in South Africa.
            <br/><br/>
            We encourage you to log in to your member portal to manage your membership, submit activities, request endorsements, and access
            important documents. If you have any questions or need assistance, please contact our administration team. We look forward to
            supporting you in your shooting journey.
        </div>

        {{-- Bottom: Signatory + Member Benefits --}}
        <table class="layout-table">
            <tr>
                <td class="half">
                    <div class="card signatory-card">
                        <div class="card-title">Authorised Signatory</div>
                        <div class="sig-box">{!! $signatureHtml !!}</div>
                        <div class="sig-name">{{ $signatory['name'] }}</div>
                        <div class="sig-title">{{ $signatory['title'] }}</div>
                        <div class="sig-date">Issued {{ $issuedAt->format('d F Y') }}</div>
                    </div>
                </td>
                <td class="half">
                    <div class="card">
                        <div class="card-title">Member Benefits</div>
                        <table class="kv-table">
                            <tr><td class="kv-value" style="padding:2px 0;">&#10003; Endorsement letters for firearm licence applications</td></tr>
                            <tr><td class="kv-value" style="padding:2px 0;">&#10003; Dedicated status certification (subject to requirements)</td></tr>
                            <tr><td class="kv-value" style="padding:2px 0;">&#10003; Activity tracking and compliance management</td></tr>
                            <tr><td class="kv-value" style="padding:2px 0;">&#10003; Access to member resources and support</td></tr>
                            <tr><td class="kv-value" style="padding:2px 0;">&#10003; Participation in NRAPA events and activities</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Verification strip (full width, horizontal) --}}
        @if ($qrCodeUrl && $verifyUrl)
            @include('documents.partials.verify-strip', [
                'qrCodeUrl' => $qrCodeUrl,
                'verifyUrl' => $verifyUrl,
                'verifyStripTitle' => 'Verify This Document',
                'verifyStripBlurb' => 'Scan the QR code or visit the link below to confirm this welcome letter.',
            ])
        @endif
    @endif
@endsection
