@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getQrCodeUrl($certificate, 200);
    $verifyUrl = $certificate->getVerificationUrl();
    $signatory = \App\Helpers\DocumentDataHelper::getSignatoryInfo($certificate);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml($certificate->signatory_signature_path);
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
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
        <span class="doc-badge">Member in Good Standing</span>
    </div>

    {{-- Orange Accent Stripe --}}
    <div class="doc-accent"></div>

    {{-- Title --}}
    <div class="doc-title" style="padding: 16px 24px;">
        <h1 style="font-size: 18px;">Proof of Membership in Good Standing</h1>
        <div class="doc-subtitle" style="font-size: 10px; margin-top: 4px;">FAR Sport: {{ $farNumbers['sport'] }} &nbsp;|&nbsp; FAR Hunting: {{ $farNumbers['hunting'] }}</div>
    </div>

    <hr class="sep" style="margin: 0 24px;"/>

    {{-- Content --}}
    <div style="padding: 16px 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            {{-- Details Grid --}}
            <div class="doc-grid" style="gap: 16px;">
                <div class="doc-section" style="padding: 14px 18px;">
                    <div class="doc-section-title" style="font-size: 11px; margin-bottom: 12px;">Member Details</div>
                    <div class="doc-field" style="margin-bottom: 10px;">
                        <div class="doc-field-label" style="font-size: 9px;">Member Name</div>
                        <div class="doc-field-value name" style="font-size: 15px;">{{ $certificate->user->getIdName() }}</div>
                    </div>
                    <div class="doc-field" style="margin-bottom: 10px;">
                        <div class="doc-field-label" style="font-size: 9px;">ID / Passport Number</div>
                        <div class="doc-field-value mono" style="font-size: 12px;">{{ $certificate->user->getIdNumber() ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field-row" style="gap: 16px;">
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 9px;">Membership No.</div>
                            <div class="doc-field-value mono" style="font-size: 12px;">{{ $certificate->membership->membership_number ?? 'N/A' }}</div>
                        </div>
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 9px;">Membership Type</div>
                            <div class="doc-field-value" style="font-size: 12px;">{{ $certificate->membership->type->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="doc-section" style="padding: 14px 18px;">
                    <div class="doc-section-title" style="font-size: 11px; margin-bottom: 12px;">Certificate Details</div>
                    <div class="doc-field" style="margin-bottom: 10px;">
                        <div class="doc-field-label" style="font-size: 9px;">Certificate Number</div>
                        <div class="doc-field-value mono" style="font-size: 12px;">{{ $certificate->certificate_number }}</div>
                    </div>
                    <div class="doc-field" style="margin-bottom: 10px;">
                        <div class="doc-field-label" style="font-size: 9px;">Date Issued</div>
                        <div class="doc-field-value" style="font-size: 12px;">{{ $certificate->issued_at->format('d F Y') }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 9px;">Valid Until</div>
                        <div class="doc-field-value" style="font-size: 12px;">{{ $certificate->valid_until ? $certificate->valid_until->format('d F Y') : 'Lifetime' }}</div>
                    </div>
                </div>
            </div>

            <div style="height:16px"></div>

            {{-- Certification Statement --}}
            <div class="doc-notice" style="padding: 14px 18px; font-size: 12px; line-height: 1.6;">
                This is to certify that <b>{{ $certificate->user->getIdName() }}</b> (ID: {{ $certificate->user->getIdNumber() ?? 'N/A' }})
                is a <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
                This confirms that the member's membership is valid, current, and compliant with the Association's requirements at the date of issue.
            </div>
        </div>

        <div>
            <div style="height:16px"></div>

            {{-- QR + Signatory --}}
            <div class="doc-grid" style="gap: 16px;">
                <div class="doc-qr-section" style="padding: 14px 18px;">
                    <div class="doc-qr-box" style="width: 90px; height: 90px;">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code" />
                    </div>
                    <div class="doc-qr-text" style="font-size: 10px;">
                        <span class="verify-label" style="font-size: 12px;">Verify Certificate</span>
                        Scan the QR code or visit:<br/>
                        <a href="{{ $verifyUrl }}" style="font-size: 9px;">{{ $verifyUrl }}</a>
                    </div>
                </div>

                <div class="doc-signatory" style="padding: 14px 18px;">
                    <div class="doc-field-label" style="font-size: 9px;">Authorised Signatory</div>
                    <div style="height:6px"></div>
                    <div class="placeholder-white signature-box" style="height: 48px;">
                        {!! $signatureHtml !!}
                    </div>
                    <div class="sig-line"></div>
                    <div class="sig-name" style="font-size: 13px;">{{ $signatory['name'] }}</div>
                    <div class="sig-title" style="font-size: 11px;">{{ $signatory['title'] }}</div>
                </div>
            </div>

            <div style="margin-top: 12px; text-align: center; font-size: 9px; color: var(--muted);">
                This document is generated electronically and is valid without a physical signature when verified via QR code.
            </div>
        </div>
    </div>

    {{-- Blue Footer --}}
    <div class="doc-footer" style="padding: 10px 24px;">
        <div>
            <span class="doc-footer-cert" style="font-size: 10px;">{{ $certificate->certificate_number }}</span>
            &nbsp;&mdash;&nbsp;
            <span style="font-size: 9px;">{{ $contact['email'] }} | {{ $contact['tel'] }}</span>
        </div>
        <div class="doc-footer-far" style="font-size: 8px;">
            FAR Sport: <span class="far-sport">{{ $farNumbers['sport'] }}</span>
            &nbsp;|&nbsp; Hunting: <span class="far-hunting">{{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>
@endsection
