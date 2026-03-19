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
        {{-- Member details card (full width) --}}
        <div class="card" style="margin-top:4px; width:95%; margin-left:auto; margin-right:auto;">
            <div class="card-title">Member Details</div>
            <table class="kv-table">
                <tr>
                    <td class="kv-label" style="width:25%;">Full Name</td>
                    <td class="kv-value">{{ $user->name ?? 'Member' }}</td>
                    @if ($user->getIdNumber())
                    <td class="kv-label" style="width:25%;">ID / Passport</td>
                    <td class="kv-value">{{ $user->getIdNumber() }}</td>
                    @endif
                </tr>
                <tr>
                    @if (!empty($user->email))
                    <td class="kv-label">Email</td>
                    <td class="kv-value">{{ $user->email }}</td>
                    @endif
                    <td class="kv-label">Date</td>
                    <td class="kv-value">{{ now()->format('d F Y') }}</td>
                </tr>
                @if (isset($certificate) && $certificate->certificate_number)
                <tr>
                    <td class="kv-label">Reference</td>
                    <td class="kv-value">{{ $certificate->certificate_number }}</td>
                    <td></td><td></td>
                </tr>
                @endif
            </table>
        </div>

        {{-- Letter body --}}
        <div class="letter-body">
            Dear {{ $user->name ? (explode(' ', trim($user->name))[0] ?? 'Member') : 'Member' }},<br/><br/>

            Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association.

            @if ($membership)
                Your membership details are as follows:

                <table class="kv-table" style="margin:8px 0; padding:8px 12px; background:#f2f2f2; border-radius:4px;">
                    <tr>
                        <td class="kv-label">Membership No.</td>
                        <td class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</td>
                        <td class="kv-label">Type</td>
                        <td class="kv-value">{{ $membership->type?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Status</td>
                        <td class="kv-value" style="color:#1f6b3a;">Member in Good Standing</td>
                        <td class="kv-label">Start Date</td>
                        <td class="kv-value">{{ $membership->activated_at?->format('d F Y') ?? $membership->applied_at?->format('d F Y') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Valid Until</td>
                        <td class="kv-value">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</td>
                        <td></td><td></td>
                    </tr>
                </table>
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
            <span style="font-weight:700; font-size:13px; color:#1f4e8c;">{{ $signatory['name'] }}</span><br/>
            <span style="font-size:10px; color:#6a6a6a;">{{ $signatory['title'] }}</span>
        </div>

        @if ($qrCodeUrl)
        {{-- Verification row --}}
        <div class="verify-card">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:85px; vertical-align:top; padding:0;">
                        <div class="qr-box">
                            <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                        </div>
                    </td>
                    <td class="verify-text" style="vertical-align:top;">
                        <strong>Verify your membership</strong>
                        Scan the QR code or visit the link below.
                        <br/>
                        <a href="{{ $verifyUrl }}" style="word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <div style="margin-top:8px; text-align:center; font-size:9px; color:#6a6a6a;">
            This letter is generated electronically. For official verification, refer to NRAPA channels.
        </div>
    @endif
@endsection
