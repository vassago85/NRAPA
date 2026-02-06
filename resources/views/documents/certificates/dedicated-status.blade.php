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
        <div class="far-numbers" style="margin-top: 8px; font-size: 11px; color: var(--text);">
            <span><b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}</span>
            <span style="margin-left: 12px;"><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>

<div class="titlebar">
    <div class="h1">{{ strtoupper($dedicatedTitle) }}</div>
</div>

<hr class="sep"/>

<section class="card">
    <div class="h2">Member</div>
    <div style="height:10px"></div>
    <div class="kv">
        <div class="k">Full Name</div><div class="v">{{ $certificate->user->getIdName() }}</div>
        <div class="k">ID / Passport</div><div class="v">{{ $certificate->user->id_number ?? 'N/A' }}</div>
        <div class="k">Member Number</div><div class="v">{{ $membership->membership_number ?? 'N/A' }}</div>
        <div class="k">Membership Type</div><div class="v">{{ $membership->type->name ?? 'N/A' }}</div>
        <div class="k">Status Effective Date</div><div class="v">{{ $statusEffectiveDate }}</div>
        <div class="k">Membership Valid Until</div><div class="v">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</div>
        <div class="k">Verification Link</div><div class="v"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
    </div>
</section>

<div style="height:6px"></div>

@php
    // Check activities and documents status
    $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($certificate->user);
    $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($certificate->user);
    $hasValidDocs = count($missingDocs) === 0;
    $hasValidActivities = $activityCheck['met'];
@endphp

<section class="card">
    <div class="h2">Compliance Status</div>
    <div style="height:10px"></div>
    <div class="kv">
        <div class="k">Required Documents</div>
        <div class="v">
            @if($hasValidDocs)
                <span style="color: var(--emerald); font-weight: 600;">✓ Valid</span>
            @else
                <span style="color: var(--red); font-weight: 600;">✗ Missing</span>
            @endif
        </div>
        <div class="k">Activity Requirements</div>
        <div class="v">
            @if($hasValidActivities)
                <span style="color: var(--emerald); font-weight: 600;">✓ Met</span>
                <div class="small" style="margin-top: 2px;">{{ $activityCheck['approved_count'] }} approved activities ({{ $activityCheck['required'] }} required)</div>
            @else
                <span style="color: var(--red); font-weight: 600;">✗ Not Met</span>
                <div class="small" style="margin-top: 2px;">{{ $activityCheck['approved_count'] }} of {{ $activityCheck['required'] }} required</div>
            @endif
        </div>
        <div class="k">Dedicated Status</div>
        <div class="v">
            <span style="color: var(--emerald); font-weight: 600;">✓ Approved</span>
            <div class="small" style="margin-top: 2px;">{{ strtoupper($dedicatedTitle) }}</div>
        </div>
    </div>
</section>

<div style="height:6px"></div>

<section class="notice">
    I, the undersigned, <b>{{ $signatory['name'] }}</b>, in my capacity as <b>{{ $signatory['title'] }}</b> of the National Rifle &amp; Pistol Association (NRAPA),
    hereby declare that the above member is a <b>Dedicated Member in good standing</b> of this Association.
    Dedicated Status has been awarded to the holder of this certificate in accordance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
    <br/><br/>
    This certificate confirms that at the time of issue, the member's required documents are valid and their activity requirements are up to date. Continued validity of Dedicated Status is subject to the member maintaining membership in good standing and meeting the ongoing activity requirements applicable to their Dedicated Status.
</section>

<div style="height:6px"></div>

<div class="sig-grid">
    <section class="sig">
        <div class="h2">Commissioner of Oaths (Scan Upload)</div>
        <div class="small" style="margin-top:6px;">Dashboard upload should place the scan here. Placeholder must remain white.</div>
        <div style="height:10px"></div>
        <div class="placeholder-white oaths-scan">
            {!! $commissionerHtml !!}
        </div>
    </section>

    <section class="sig">
        <div class="h2">Authorised NRAPA Signatory</div>
        <div style="height:10px"></div>

        <div class="placeholder-white signature-box">
            {!! $signatureHtml !!}
        </div>
        <div class="small" style="margin-top:6px;">Signature placeholder must remain white.</div>

        <div class="line"></div>

        <div style="font-weight:700; font-size:13px;">{{ $signatory['name'] }}</div>
        <div class="small">{{ $signatory['title'] }}</div>
        <div class="small" style="margin-top:8px;">Issued: {{ $certificate->issued_at->format('d F Y') }}</div>

        <div style="height:10px"></div>

        <div class="h2">Verification QR</div>
        <div style="height:8px"></div>
        <div style="display:flex; gap:12px; align-items:center;">
            <div class="qr"><img src="{{ $qrCodeUrl }}" alt="QR Code"/></div>
            <div class="small">Scan to verify the member and certificate status.</div>
        </div>
    </section>
</div>

<div class="footer">
    <div style="flex: 1;">
        <div class="footer-contact">
            <span class="footer-contact-item"><b>TEL:</b> {{ $contact['tel'] }}</span>
            @if($contact['fax'])
            <span class="footer-contact-item"><b>FAX:</b> {{ $contact['fax'] }}</span>
            @endif
            <span class="footer-contact-item"><b>E-MAIL:</b> {{ $contact['email'] }}</span>
            <span class="footer-contact-item"><b>ADDRESS:</b> {{ $contact['physical_address'] }}</span>
        </div>
        <div style="margin-top: 8px; font-size: 10px; color: var(--muted);">
            This document is generated electronically and is valid without a physical signature when verified via QR code.
        </div>
    </div>
</div>
@endsection
