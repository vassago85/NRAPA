@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
    
    // For welcome letters, we might not have a certificate with QR code
    // Use membership or create a verification URL if available
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

    // Get active terms version for reference
    $activeTerms = \App\Models\TermsVersion::active();
    $signatory = isset($certificate) ? \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate) : [
        'name' => \App\Models\SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
        'title' => \App\Models\SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
    ];
@endphp
<div class="letterhead">
    <div class="header" style="grid-template-columns: 64px 1fr; gap: 14px;">
        @if($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="NRAPA Logo" />
        @else
            <div class="logo" style="background: var(--blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; border-radius: 4px;">NRAPA</div>
        @endif
        <div class="org">
            <div class="org-title">NATIONAL RIFLE &amp; PISTOL ASSOCIATION</div>
            <div class="org-sub">of South Africa</div>
            <div class="accreditation-badge" style="margin-top:4px;">
                <span class="accreditation-dot"></span>
                <span>FAR Accredited | SAPS Recognised</span>
            </div>
            <div class="far-numbers" style="margin-top: 8px; font-size: 11px; color: var(--text);">
                <span><b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}</span>
                <span style="margin-left: 12px;"><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
            </div>
        </div>
    </div>

    <div class="addr">
        <div>{{ $contact['postal_address'] }}</div>
        <div style="height:8px;"></div>
        <div><b>TEL:</b> {{ $contact['tel'] }}</div>
        @if($contact['fax'])
        <div><b>FAX:</b> {{ $contact['fax'] }}</div>
        @endif
        <div><b>E-MAIL:</b> {{ $contact['email'] }}</div>
        <div style="height:8px;"></div>
        <div><b>ADDRESS:</b> {{ $contact['physical_address'] }}</div>
    </div>
</div>

<div style="height:12px"></div>

<div class="meta">
    <div><b>Date:</b> {{ now()->format('d F Y') }}</div>
    @if(isset($certificate) && $certificate->certificate_number)
    <div><b>Reference:</b> {{ $certificate->certificate_number }}</div>
    @endif
</div>

<hr class="sep"/>

<div class="body">
    @if(!$user)
        <p class="text-red-600">Unable to load member details for this welcome letter.</p>
    @else
    <p><b>{{ $user->name ?? 'Member' }}</b><br/>
    @if($user->getIdNumber())
    ID/Passport: {{ $user->getIdNumber() }}<br/>
    @endif
    @if(!empty($user->email))
    Email: {{ $user->email }}<br/>
    @endif
    @if(!empty($user->phone))
    Phone: {{ $user->phone }}
    @endif
    </p>

    <p>Dear {{ $user->name ? (explode(' ', trim($user->name))[0] ?? 'Member') : 'Member' }},</p>

    @if($membership)
    <p>Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association. Your membership details are as follows:</p>

    <div class="callout">
        <div class="kv" style="grid-template-columns: 200px 1fr;">
            <div class="k">Membership Number</div><div class="v">{{ $membership->membership_number ?? 'N/A' }}</div>
            <div class="k">Membership Type</div><div class="v">{{ $membership->type?->name ?? 'N/A' }}</div>
            <div class="k">Membership Status</div><div class="v">Member in Good Standing</div>
            <div class="k">Start Date of Membership</div><div class="v">{{ $membership->activated_at?->format('d F Y') ?? $membership->applied_at?->format('d F Y') ?? 'N/A' }}</div>
            <div class="k">Valid Until</div><div class="v">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</div>
        </div>
    </div>
    @else
    <p>Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association.</p>
    @endif

    <div style="height:10px"></div>

    @if($qrCodeUrl)
    <p>Your certificate(s) include a QR code for verification. If a third party needs to confirm your status, they can scan the QR code or use your verification link.</p>

    <div class="callout">
        <div class="qrline">
            <div class="qr"><img src="{{ $qrCodeUrl }}" alt="QR Code"/></div>
            <div>
                <div style="font-weight:800;">Verification</div>
                <div class="small"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
                <div class="small">Status shown online: <b>Member in Good Standing</b></div>
            </div>
        </div>
    </div>

    <div style="height:10px"></div>
    @endif

    <p><b>Terms & Conditions</b></p>
    <p style="margin-top:8px;">
        By being a member, you agree to the NRAPA Membership Terms &amp; Conditions{{ $activeTerms ? ' (Version ' . $activeTerms->version . ')' : '' }}.
        A copy of the Terms & Conditions is available in your member portal and should be retained for your records.
    </p>

    <p><b>Keeping your records up to date</b></p>
    <ul class="ul">
        <li>Keep your contact details current so NRAPA can reach you.</li>
        <li>Maintain activity evidence required for your FCA Dedicated Status (where applicable).</li>
        <li>Retain copies of certificates and confirmations for your records.</li>
    </ul>

    <p>If you require assistance with endorsements or have any queries, please contact us at <b>{{ $contact['email'] }}</b>@if($contact['tel']) or {{ $contact['tel'] }}@endif.</p>

    <p>Kind regards,</p>

    <div style="height:10px"></div>

    <div style="font-weight:800;">{{ $signatory['name'] }}</div>
    <div class="small">{{ $signatory['title'] }}</div>
    @endif
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
            This letter is generated electronically. For official verification, refer to NRAPA channels.
        </div>
    </div>
</div>
@endsection
