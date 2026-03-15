@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml($certificate->commissioner_oaths_scan_path);

    $certTypeSlug = $certificate->certificateType->slug ?? '';
    $isHunting = str_contains($certTypeSlug, 'hunter') || str_contains($certTypeSlug, 'hunting');
    $isSport = str_contains($certTypeSlug, 'sport');

    if (!$isHunting && !$isSport) {
        $approvedApps = $certificate->user->dedicatedStatusApplications()
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->get();
        $isHunting = $approvedApps->contains('dedicated_type', 'hunter');
        $isSport = $approvedApps->contains('dedicated_type', 'sport_shooter');
    }

    $dedicatedTitle = $isHunting ? 'Dedicated Hunter' : ($isSport ? 'Dedicated Sport Shooter' : 'Dedicated Member');

    $membership = $certificate->membership;
    $statusEffectiveDate = $membership->activated_at?->format('d F Y') ?? $membership->applied_at?->format('d F Y') ?? 'N/A';

    $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($certificate->user);
    $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($certificate->user);
    $hasValidDocs = count($missingDocs) === 0;
    $hasValidActivities = $activityCheck['met'];

    $title = $dedicatedTitle . ' Certificate — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">{{ $dedicatedTitle }} Certificate</div>
    <div class="doc-banner-subtitle">Firearms Control Act (Act 60 of 2000, as amended)</div>
</div>
@endsection

@section('content')
    {{-- Info grid: Member + Compliance details --}}
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
                <span class="kv-label">Member No.</span>
                <span class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Membership Type</span>
                <span class="kv-value">{{ $membership->type->name ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Valid Until</span>
                <span class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</span>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Compliance Status</div>
            <div class="kv-row">
                <span class="kv-label">Documents</span>
                <span class="kv-value">
                    @if ($hasValidDocs)
                        <span style="color:var(--status-green);">&#10003; Valid</span>
                    @else
                        <span style="color:#c0392b;">&#10007; Missing</span>
                    @endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Activities</span>
                <span class="kv-value">
                    @if ($hasValidActivities)
                        <span style="color:var(--status-green);">&#10003; Met ({{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }})</span>
                    @else
                        <span style="color:#c0392b;">&#10007; {{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }}</span>
                    @endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Dedicated Status</span>
                <span class="kv-value" style="color:var(--status-green);">&#10003; {{ $dedicatedTitle }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Effective Date</span>
                <span class="kv-value">{{ $statusEffectiveDate }}</span>
            </div>
        </div>
    </div>

    {{-- Declaration --}}
    <div class="letter-body">
        I, <b>{{ $signatory['name'] }}</b>, {{ $signatory['title'] }} of NRAPA, declare that the above member is a <b>Dedicated Member in good standing</b>.
        Dedicated Status has been awarded in accordance with the Firearms Control Act (Act 60 of 2000, as amended).
        This certificate confirms that at the time of issue, the member's documents are valid and activity requirements are up to date.
    </div>

    {{-- Commissioner + Signatory --}}
    <div class="bottom-grid">
        <div class="card commissioner-card">
            <div class="card-title">Commissioner of Oaths</div>
            <div class="commissioner-box">
                @if($commissionerHtml && trim(strip_tags($commissionerHtml)))
                    {!! $commissionerHtml !!}
                @else
                    Commissioner of Oaths scan
                @endif
            </div>
            <div class="commissioner-sub">Upload commissioned scan in admin dashboard.</div>
        </div>

        <div class="card signatory-card">
            <div class="card-title">Authorised Signatory</div>
            <div class="sig-box">{!! $signatureHtml !!}</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $signatory['name'] }}</div>
            <div class="sig-title">{{ $signatory['title'] }}</div>
            <div class="sig-date">Issued {{ $certificate->issued_at->format('d F Y') }}</div>
        </div>
    </div>

    {{-- Verification row --}}
    <div class="verify-row">
        <div style="display:flex; gap:10px; align-items:flex-start;">
            <div class="qr-box">
                <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
            </div>
            <div class="verify-text">
                <strong>Verify this certificate</strong>
                Scan the QR code or visit the link below.
                <br/>
                <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
            </div>
        </div>
    </div>

    <div style="margin-top:8px; text-align:center; font-size:9px; color:var(--muted);">
        This document is generated electronically and is valid without a physical signature when verified via QR code.
    </div>
@endsection
