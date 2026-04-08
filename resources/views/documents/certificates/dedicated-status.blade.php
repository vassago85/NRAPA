@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);

    $certTypeSlug = $certificate->certificateType->slug ?? '';
    $isOccasional = str_contains($certTypeSlug, 'occasional');
    $isBoth = str_contains($certTypeSlug, 'both');
    $isHunting = str_contains($certTypeSlug, 'hunter') || str_contains($certTypeSlug, 'hunting');
    $isSport = str_contains($certTypeSlug, 'sport');

    if (!$isOccasional && !$isBoth && !$isHunting && !$isSport) {
        $approvedApps = $certificate->user->dedicatedStatusApplications()
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->get();
        $isHunting = $approvedApps->contains('dedicated_type', 'hunter');
        $isSport = $approvedApps->contains('dedicated_type', 'sport') || $approvedApps->contains('dedicated_type', 'sport_shooter');
        $isBoth = $isHunting && $isSport;
    }

    $isDedicated = $isBoth || $isHunting || $isSport;
    $sectionNumber = ($isDedicated && !$isOccasional) ? 16 : 15;

    if ($isOccasional) {
        $dedicatedTitle = $isHunting ? 'Occasional Hunter' : ($isSport ? 'Occasional Sport Shooter' : 'Occasional Member');
    } elseif ($isBoth) {
        $dedicatedTitle = 'Dedicated Hunter & Sport Shooter';
    } elseif ($isHunting) {
        $dedicatedTitle = 'Dedicated Hunter';
    } elseif ($isSport) {
        $dedicatedTitle = 'Dedicated Sport Shooter';
    } else {
        $dedicatedTitle = 'Dedicated Member';
    }

    $membership = $certificate->membership;
    $statusEffectiveDate = $membership->approved_at?->format('d F Y') ?? $membership->activated_at?->format('d F Y') ?? 'N/A';

    $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($certificate->user);
    $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($certificate->user);
    $hasValidDocs = count($missingDocs) === 0;
    $hasValidActivities = $activityCheck['met'];

    $title = $dedicatedTitle . ' Certificate — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">{{ $dedicatedTitle }} Certificate</div>
    <div class="doc-banner-subtitle">Firearms Control Act (Act 60 of 2000) — Section {{ $sectionNumber }}</div>
</div>
@endsection

@section('content')
    {{-- Info grid: Member + Compliance details --}}
    <table class="layout-table">
        <tr>
            <td class="half">
                <div class="card">
                    <div class="card-title">Member Details</div>
                    <table class="kv-table">
                        <tr><td class="kv-label">Full Name</td><td class="kv-value">{{ $certificate->user->getIdName() }}</td></tr>
                        <tr><td class="kv-label">ID / Passport</td><td class="kv-value">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Member No.</td><td class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Membership Type</td><td class="kv-value">{{ $membership->type->name ?? 'N/A' }}</td></tr>
                        <tr><td class="kv-label">Valid Until</td><td class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</td></tr>
                    </table>
                </div>
            </td>
            <td class="half">
                <div class="card">
                    <div class="card-title">Compliance Status</div>
                    <table class="kv-table">
                        <tr>
                            <td class="kv-label">Documents</td>
                            <td class="kv-value">
                                @if ($hasValidDocs)
                                    <span style="color:#1f6b3a;">&#10003; Valid</span>
                                @else
                                    <span style="color:#c0392b;">&#10007; Missing</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="kv-label">Activities</td>
                            <td class="kv-value">
                                @if ($hasValidActivities)
                                    <span style="color:#1f6b3a;">&#10003; Met ({{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }})</span>
                                @else
                                    <span style="color:#c0392b;">&#10007; {{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="kv-label">{{ $isOccasional ? 'Occasional Status' : 'Dedicated Status' }}</td>
                            <td class="kv-value" style="color:#1f6b3a;">&#10003; {{ $dedicatedTitle }}</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Section</td>
                            <td class="kv-value">Section {{ $sectionNumber }} — Firearms Control Act</td>
                        </tr>
                        <tr>
                            <td class="kv-label">Effective Date</td>
                            <td class="kv-value">{{ $statusEffectiveDate }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Declaration --}}
    <div class="letter-body">
        @if ($isOccasional)
            I, <b>{{ $signatory['name'] }}</b>, {{ $signatory['title'] }} of NRAPA, confirm that the above member holds active membership and is recognised as an <b>{{ $dedicatedTitle }}</b> under Section {{ $sectionNumber }} of the Firearms Control Act (Act 60 of 2000).
        @else
            I, <b>{{ $signatory['name'] }}</b>, {{ $signatory['title'] }} of NRAPA, declare that the above member is a <b>{{ $dedicatedTitle }} in good standing</b>.
            Dedicated Status has been awarded in accordance with Section {{ $sectionNumber }} of the Firearms Control Act (Act 60 of 2000).
            This certificate confirms that at the time of issue, the member's documents are valid and activity requirements are up to date.
        @endif
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
