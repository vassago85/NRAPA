@extends('documents.layouts.nrapa-official')

@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml($certificate->commissioner_oaths_scan_path);
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
    
    // Determine dedicated status type from certificate type or user's approved applications
    $certTypeSlug = $certificate->certificateType->slug ?? '';
    $isHunting = str_contains($certTypeSlug, 'hunter') || str_contains($certTypeSlug, 'hunting');
    $isSport = str_contains($certTypeSlug, 'sport');
    
    // If not determined from certificate type, check user's approved applications
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
    <div class="h1">{{ strtoupper($dedicatedTitle) }}</div>
</div>

<hr class="sep"/>

@php
    // Check activities and documents status
    $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($certificate->user);
    $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($certificate->user);
    $hasValidDocs = count($missingDocs) === 0;
    $hasValidActivities = $activityCheck['met'];
@endphp

<div class="grid">
    <section class="card">
        <div class="h2">Member Details</div>
        <div style="height:4px"></div>
        <div class="kv">
            <div class="k">Full Name</div><div class="v">{{ $certificate->user->getIdName() }}</div>
            <div class="k">ID / Passport</div><div class="v">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</div>
            <div class="k">Member No.</div><div class="v">{{ $membership->membership_number ?? 'N/A' }}</div>
            <div class="k">Membership Type</div><div class="v">{{ $membership->type->name ?? 'N/A' }}</div>
            <div class="k">Valid Until</div><div class="v">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</div>
        </div>
    </section>

    <section class="card">
        <div class="h2">Compliance Status</div>
        <div style="height:4px"></div>
        <div class="kv">
            <div class="k">Documents</div>
            <div class="v">@if($hasValidDocs)<span style="color: var(--emerald);">✓ Valid</span>@else<span style="color: var(--red);">✗ Missing</span>@endif</div>
            <div class="k">Activities</div>
            <div class="v">@if($hasValidActivities)<span style="color: var(--emerald);">✓ Met ({{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }})</span>@else<span style="color: var(--red);">✗ {{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }}</span>@endif</div>
            <div class="k">Dedicated Status</div>
            <div class="v"><span style="color: var(--emerald);">✓ {{ $dedicatedTitle }}</span></div>
            <div class="k">Effective Date</div><div class="v">{{ $statusEffectiveDate }}</div>
        </div>
    </section>
</div>

<div style="height:4px"></div>

<section class="notice" style="font-size:9px; line-height:1.3;">
    I, <b>{{ $signatory['name'] }}</b>, {{ $signatory['title'] }} of NRAPA, declare that the above member is a <b>Dedicated Member in good standing</b>.
    Dedicated Status has been awarded in accordance with the Firearms Control Act (Act 60 of 2000, as amended).
    This certificate confirms that at the time of issue, the member's documents are valid and activity requirements are up to date.
</section>

<div style="height:4px"></div>

<div class="sig-grid">
    <section class="sig">
        <div class="h2" style="font-size:10px;">Commissioner of Oaths</div>
        <div style="height:4px"></div>
        <div class="placeholder-white oaths-scan" style="height:80px;">
            {!! $commissionerHtml !!}
        </div>
    </section>

    <section class="sig">
        <div class="h2" style="font-size:10px;">Authorised Signatory</div>
        <div style="height:4px"></div>
        <div class="placeholder-white signature-box">
            {!! $signatureHtml !!}
        </div>
        <div class="line"></div>
        <div style="font-weight:700; font-size:11px;">{{ $signatory['name'] }}</div>
        <div class="small">{{ $signatory['title'] }} | Issued: {{ $certificate->issued_at->format('d M Y') }}</div>
        <div style="height:6px"></div>
        <div style="display:flex; gap:8px; align-items:center;">
            <div class="qr" style="width:50px; height:50px;"><img src="{{ $qrCodeUrl }}" alt="QR"/></div>
            <div class="small">Scan to verify</div>
        </div>
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
