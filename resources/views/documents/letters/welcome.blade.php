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

    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
    $activeTerms = \App\Models\TermsVersion::active();
    $signatory = isset($certificate) ? \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate) : [
        'name' => \App\Models\SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
        'title' => \App\Models\SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
    ];

    $title = 'Welcome Letter — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Welcome Letter</div>
    <div class="doc-banner-subtitle">Membership confirmation and onboarding</div>
</div>
@endsection

@section('content')
    @if (!$user)
        <div class="letter-body" style="color:#c0392b;">
            Unable to load member details for this welcome letter.
        </div>
    @else
        {{-- Member details card --}}
        <div class="info-grid" style="grid-template-columns:1fr;">
            <div class="card">
                <div class="card-title">Member Details</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 24px;">
                    <div class="kv-row">
                        <span class="kv-label">Full Name</span>
                        <span class="kv-value">{{ $user->name ?? 'Member' }}</span>
                    </div>
                    @if ($user->getIdNumber())
                    <div class="kv-row">
                        <span class="kv-label">ID / Passport</span>
                        <span class="kv-value">{{ $user->getIdNumber() }}</span>
                    </div>
                    @endif
                    @if (!empty($user->email))
                    <div class="kv-row">
                        <span class="kv-label">Email</span>
                        <span class="kv-value">{{ $user->email }}</span>
                    </div>
                    @endif
                    <div class="kv-row">
                        <span class="kv-label">Date</span>
                        <span class="kv-value">{{ now()->format('d F Y') }}</span>
                    </div>
                    @if (isset($certificate) && $certificate->certificate_number)
                    <div class="kv-row">
                        <span class="kv-label">Reference</span>
                        <span class="kv-value">{{ $certificate->certificate_number }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Letter body --}}
        <div class="letter-body">
            Dear {{ $user->name ? (explode(' ', trim($user->name))[0] ?? 'Member') : 'Member' }},<br/><br/>

            Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association.

            @if ($membership)
                Your membership details are as follows:

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 24px; margin:8px 0; padding:8px 12px; background:#f2f2f2; border-radius:4px;">
                    <div class="kv-row" style="border-bottom-color:#ddd;">
                        <span class="kv-label">Membership No.</span>
                        <span class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</span>
                    </div>
                    <div class="kv-row" style="border-bottom-color:#ddd;">
                        <span class="kv-label">Type</span>
                        <span class="kv-value">{{ $membership->type?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="kv-row" style="border-bottom-color:#ddd;">
                        <span class="kv-label">Status</span>
                        <span class="kv-value" style="color:var(--status-green);">Member in Good Standing</span>
                    </div>
                    <div class="kv-row" style="border-bottom-color:#ddd;">
                        <span class="kv-label">Start Date</span>
                        <span class="kv-value">{{ $membership->activated_at?->format('d F Y') ?? $membership->applied_at?->format('d F Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Valid Until</span>
                        <span class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</span>
                    </div>
                </div>
            @endif

            <br/>
            @if ($qrCodeUrl)
                Your certificate(s) include a QR code for verification. If a third party needs to confirm your status, they can scan the QR code or use your verification link.
            @endif

            <br/><b>Terms &amp; Conditions</b><br/>
            By being a member, you agree to the NRAPA Membership Terms &amp; Conditions{{ $activeTerms ? ' (Version ' . $activeTerms->version . ')' : '' }}.
            A copy is available in your member portal and should be retained for your records.

            <br/><br/><b>Keeping your records up to date</b>
            <ul>
                <li>Keep your contact details current so NRAPA can reach you.</li>
                <li>Maintain activity evidence required for your FCA Dedicated Status (where applicable).</li>
                <li>Retain copies of certificates and confirmations for your records.</li>
            </ul>

            If you require assistance with endorsements or have any queries, please contact us at
            <b>{{ $contact['email'] }}</b>{{ $contact['tel'] ? ' or ' . $contact['tel'] : '' }}.

            <br/><br/>
            Kind regards,<br/><br/>
            <span style="font-weight:700; font-size:13px; color:var(--blue);">{{ $signatory['name'] }}</span><br/>
            <span style="font-size:10px; color:var(--muted);">{{ $signatory['title'] }}</span>
        </div>

        @if ($qrCodeUrl)
        {{-- Verification row --}}
        <div class="verify-row">
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <div class="qr-box">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                </div>
                <div class="verify-text">
                    <strong>Verify your membership</strong>
                    Scan the QR code or visit the link below.
                    <br/>
                    <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                </div>
            </div>
        </div>
        @endif

        <div style="margin-top:8px; text-align:center; font-size:9px; color:var(--muted);">
            This letter is generated electronically. For official verification, refer to NRAPA channels.
        </div>
    @endif
@endsection
