@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml($certificate->commissioner_oaths_scan_path);
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();

    // Determine dedicated status type
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

    // Check activities and documents status
    $activityCheck = \App\Models\EndorsementRequest::checkActivityRequirements($certificate->user);
    $missingDocs = \App\Models\EndorsementRequest::getMissingRequiredDocuments($certificate->user);
    $hasValidDocs = count($missingDocs) === 0;
    $hasValidActivities = $activityCheck['met'];
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
        <span class="doc-badge">{{ $dedicatedTitle }}</span>
    </div>

    {{-- Orange Accent Stripe --}}
    <div class="doc-accent"></div>

    {{-- Title --}}
    <div class="doc-title">
        <h1>{{ $dedicatedTitle }} Certificate</h1>
        <div class="doc-subtitle">Firearms Control Act (Act 60 of 2000, as amended)</div>
    </div>

    <hr class="sep" style="margin: 0 16px;"/>

    {{-- Content --}}
    <div style="padding: 8px 16px;">
        {{-- Details Grid --}}
        <div class="doc-grid">
            <div class="doc-section">
                <div class="doc-section-title">Member Details</div>
                <div class="doc-field">
                    <div class="doc-field-label">Full Name</div>
                    <div class="doc-field-value name">{{ $certificate->user->getIdName() }}</div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">ID / Passport Number</div>
                    <div class="doc-field-value mono">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</div>
                </div>
                <div class="doc-field-row">
                    <div class="doc-field">
                        <div class="doc-field-label">Member No.</div>
                        <div class="doc-field-value mono">{{ $membership->membership_number ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label">Membership Type</div>
                        <div class="doc-field-value">{{ $membership->type->name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Valid Until</div>
                    <div class="doc-field-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</div>
                </div>
            </div>

            <div class="doc-section">
                <div class="doc-section-title">Compliance Status</div>
                <div class="doc-field">
                    <div class="doc-field-label">Documents</div>
                    <div class="doc-field-value">
                        @if ($hasValidDocs)
                            <span style="color: var(--emerald);">&#10003; Valid</span>
                        @else
                            <span style="color: var(--red);">&#10007; Missing</span>
                        @endif
                    </div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Activities</div>
                    <div class="doc-field-value">
                        @if ($hasValidActivities)
                            <span style="color: var(--emerald);">&#10003; Met ({{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }})</span>
                        @else
                            <span style="color: var(--red);">&#10007; {{ $activityCheck['approved_count'] }}/{{ $activityCheck['required'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Dedicated Status</div>
                    <div class="doc-field-value"><span style="color: var(--emerald);">&#10003; {{ $dedicatedTitle }}</span></div>
                </div>
                <div class="doc-field">
                    <div class="doc-field-label">Effective Date</div>
                    <div class="doc-field-value">{{ $statusEffectiveDate }}</div>
                </div>
            </div>
        </div>

        <div style="height:6px"></div>

        {{-- Declaration --}}
        <div class="doc-notice" style="font-size:9px; line-height:1.35;">
            I, <b>{{ $signatory['name'] }}</b>, {{ $signatory['title'] }} of NRAPA, declare that the above member is a <b>Dedicated Member in good standing</b>.
            Dedicated Status has been awarded in accordance with the Firearms Control Act (Act 60 of 2000, as amended).
            This certificate confirms that at the time of issue, the member's documents are valid and activity requirements are up to date.
        </div>

        <div style="height:6px"></div>

        {{-- Commissioner + Signatory + QR --}}
        <div class="doc-grid">
            <div class="doc-section" style="padding: 8px 12px;">
                <div class="doc-field-label" style="margin-bottom:4px;">Commissioner of Oaths</div>
                <div class="placeholder-white oaths-scan" style="height:75px;">
                    {!! $commissionerHtml !!}
                </div>
            </div>

            <div class="doc-section" style="padding: 8px 12px;">
                <div class="doc-field-label" style="margin-bottom:4px;">Authorised Signatory</div>
                <div class="placeholder-white signature-box">
                    {!! $signatureHtml !!}
                </div>
                <div style="height:1px; background:var(--line); margin:4px 0;"></div>
                <div style="font-weight:700; font-size:11px;">{{ $signatory['name'] }}</div>
                <div class="small">{{ $signatory['title'] }}</div>
                <div style="height:6px"></div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <div class="doc-qr-box" style="width:45px; height:45px;">
                        <img src="{{ $qrCodeUrl }}" alt="QR" />
                    </div>
                    <div class="doc-qr-text" style="font-size:8px;">Scan to verify<br/><a href="{{ $verifyUrl }}" style="font-size:7px;">{{ $verifyUrl }}</a></div>
                </div>
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
