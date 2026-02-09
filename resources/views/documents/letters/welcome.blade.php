@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();

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

    $activeTerms = \App\Models\TermsVersion::active();
    $signatory = isset($certificate) ? \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate) : [
        'name' => \App\Models\SystemSetting::get('default_signatory_name', 'NRAPA Administration'),
        'title' => \App\Models\SystemSetting::get('default_signatory_title', 'Authorised Signatory'),
    ];
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
        <span class="doc-badge">Welcome Letter</span>
    </div>

    {{-- Orange Accent Stripe --}}
    <div class="doc-accent"></div>

    {{-- Letter Content --}}
    <div style="padding: 20px 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            {{-- Date and Reference --}}
            <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--muted); margin-bottom:14px;">
                <div><b>Date:</b> {{ now()->format('d F Y') }}</div>
                @if (isset($certificate) && $certificate->certificate_number)
                    <div><b>Ref:</b> {{ $certificate->certificate_number }}</div>
                @endif
            </div>

            @if (!$user)
                <p style="color: var(--red); font-size: 12px;">Unable to load member details for this welcome letter.</p>
            @else
                {{-- Addressee --}}
                <div class="doc-section" style="margin-bottom:14px; padding: 14px 18px;">
                    <div class="doc-field" style="margin-bottom: 10px;">
                        <div class="doc-field-label" style="font-size: 9px;">Member Name</div>
                        <div class="doc-field-value name" style="font-size: 15px;">{{ $user->name ?? 'Member' }}</div>
                    </div>
                    <div class="doc-field-row" style="gap: 16px;">
                        @if ($user->getIdNumber())
                            <div class="doc-field">
                                <div class="doc-field-label" style="font-size: 9px;">ID / Passport</div>
                                <div class="doc-field-value mono" style="font-size: 12px;">{{ $user->getIdNumber() }}</div>
                            </div>
                        @endif
                        @if (!empty($user->email))
                            <div class="doc-field">
                                <div class="doc-field-label" style="font-size: 9px;">Email</div>
                                <div class="doc-field-value" style="font-size: 12px;">{{ $user->email }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Letter Body --}}
                <div class="body" style="font-size: 12px; line-height: 1.6;">
                    <p>Dear {{ $user->name ? (explode(' ', trim($user->name))[0] ?? 'Member') : 'Member' }},</p>

                    @if ($membership)
                        <p>Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association. Your membership details are as follows:</p>

                        <div class="doc-section" style="margin: 10px 0; padding: 14px 18px;">
                            <div class="doc-field-row" style="gap: 16px; margin-bottom: 10px;">
                                <div class="doc-field">
                                    <div class="doc-field-label" style="font-size: 9px;">Membership Number</div>
                                    <div class="doc-field-value mono" style="font-size: 12px;">{{ $membership->membership_number ?? 'N/A' }}</div>
                                </div>
                                <div class="doc-field">
                                    <div class="doc-field-label" style="font-size: 9px;">Membership Type</div>
                                    <div class="doc-field-value" style="font-size: 12px;">{{ $membership->type?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="doc-field-row" style="gap: 16px; margin-bottom: 10px;">
                                <div class="doc-field">
                                    <div class="doc-field-label" style="font-size: 9px;">Status</div>
                                    <div class="doc-field-value" style="font-size: 12px;"><span style="color:var(--emerald);">Member in Good Standing</span></div>
                                </div>
                                <div class="doc-field">
                                    <div class="doc-field-label" style="font-size: 9px;">Start Date</div>
                                    <div class="doc-field-value" style="font-size: 12px;">{{ $membership->activated_at?->format('d F Y') ?? $membership->applied_at?->format('d F Y') ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="doc-field">
                                <div class="doc-field-label" style="font-size: 9px;">Valid Until</div>
                                <div class="doc-field-value" style="font-size: 12px;">{{ $membership->expires_at ? $membership->expires_at->format('d F Y') : 'Lifetime' }}</div>
                            </div>
                        </div>
                    @else
                        <p>Thank you for joining the National Rifle and Pistol Association (NRAPA). We welcome you to the Association.</p>
                    @endif

                    @if ($qrCodeUrl)
                        <p>Your certificate(s) include a QR code for verification. If a third party needs to confirm your status, they can scan the QR code or use your verification link.</p>

                        <div style="display:flex; gap:14px; align-items:center; margin:10px 0; padding:12px 16px; border:1px solid var(--line); border-radius:8px;">
                            <div class="doc-qr-box" style="width:80px; height:80px;">
                                <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:12px;">Verification</div>
                                <div style="font-size: 10px; color: var(--muted);"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
                                <div style="font-size: 10px; color: var(--muted);">Status: <b>Member in Good Standing</b></div>
                            </div>
                        </div>
                    @endif

                    <p><b>Terms &amp; Conditions</b></p>
                    <p style="margin-top:4px;">
                        By being a member, you agree to the NRAPA Membership Terms &amp; Conditions{{ $activeTerms ? ' (Version ' . $activeTerms->version . ')' : '' }}.
                        A copy of the Terms &amp; Conditions is available in your member portal and should be retained for your records.
                    </p>

                    <p><b>Keeping your records up to date</b></p>
                    <ul class="ul" style="margin: 6px 0 10px 20px;">
                        <li>Keep your contact details current so NRAPA can reach you.</li>
                        <li>Maintain activity evidence required for your FCA Dedicated Status (where applicable).</li>
                        <li>Retain copies of certificates and confirmations for your records.</li>
                    </ul>

                    <p>If you require assistance with endorsements or have any queries, please contact us at <b>{{ $contact['email'] }}</b>{{ $contact['tel'] ? ' or ' . $contact['tel'] : '' }}.</p>
                </div>
            @endif
        </div>

        <div>
            @if ($user)
            <div class="body" style="font-size: 12px; line-height: 1.6;">
                <p>Kind regards,</p>

                <div style="height:10px"></div>
                <div style="font-weight:800; font-size: 13px;">{{ $signatory['name'] }}</div>
                <div style="font-size: 11px; color: var(--muted);">{{ $signatory['title'] }}</div>
            </div>
            @endif

            <div style="margin-top: 16px; text-align: center; font-size: 9px; color: var(--muted);">
                This letter is generated electronically. For official verification, refer to NRAPA channels.
            </div>
        </div>
    </div>

    {{-- Blue Footer --}}
    <div class="doc-footer" style="padding: 10px 24px;">
        <div>
            <span style="font-size: 9px;">{{ $contact['email'] }} | {{ $contact['tel'] }}</span>
            @if ($contact['fax'])
                <span style="font-size: 9px;"> | Fax: {{ $contact['fax'] }}</span>
            @endif
        </div>
        <div class="doc-footer-far" style="font-size: 8px;">
            FAR Sport: <span class="far-sport">{{ $farNumbers['sport'] }}</span>
            &nbsp;|&nbsp; Hunting: <span class="far-hunting">{{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>
@endsection
