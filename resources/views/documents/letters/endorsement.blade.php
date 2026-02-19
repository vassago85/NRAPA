@extends('documents.layouts.nrapa-official')

@section('content')
@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getEndorsementQrCodeUrl($request, 200);
    $verifyUrl = $request->letter_reference
        ? url('/verify/endorsement/' . $request->letter_reference)
        : ($request->uuid ? url('/verify/endorsement/' . $request->uuid) : '#');
    $signatory = \App\Helpers\DocumentDataHelper::getEndorsementSignatoryInfo($request);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(\App\Helpers\DocumentDataHelper::getDefaultSignaturePath());
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml(\App\Helpers\DocumentDataHelper::getDefaultCommissionerScanPath());
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();

    $user = $request->user;
    $membership = $user->activeMembership;
    $firearm = $request->firearm;

    $purposeText = match($request->purpose) {
        'section_16_application' => 'Section 16 firearm licence application',
        'status_confirmation' => 'Status confirmation for regulatory purposes',
        'licence_renewal' => 'Firearm licence renewal application',
        'additional_firearm' => 'Application for additional firearm',
        'other' => $request->purpose_other_text ?? 'Other purpose',
        default => 'Firearm licence application',
    };
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
        <span class="doc-badge">Endorsement</span>
    </div>

    {{-- Orange Accent Stripe --}}
    <div class="doc-accent"></div>

    {{-- Title --}}
    <div class="doc-title" style="padding: 10px 24px;">
        <h1 style="font-size: 16px;">Endorsement Letter</h1>
        <div class="doc-subtitle" style="font-size: 9px; margin-top: 2px;">Issued for firearm licence application purposes | FAR Sport: {{ $farNumbers['sport'] }} | FAR Hunting: {{ $farNumbers['hunting'] }}</div>
    </div>

    <hr class="sep" style="margin: 0 24px;"/>

    {{-- Content --}}
    <div style="padding: 10px 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            {{-- Member + Letter Details --}}
            <div class="doc-grid" style="gap: 12px;">
                <div class="doc-section" style="padding: 10px 14px;">
                    <div class="doc-section-title" style="font-size: 10px; margin-bottom: 8px;">Applicant / Member</div>
                    <div class="doc-field" style="margin-bottom: 6px;">
                        <div class="doc-field-label" style="font-size: 8px;">Full Name</div>
                        <div class="doc-field-value name" style="font-size: 13px;">{{ $user->getIdName() }}</div>
                    </div>
                    <div class="doc-field" style="margin-bottom: 6px;">
                        <div class="doc-field-label" style="font-size: 8px;">ID / Passport</div>
                        <div class="doc-field-value mono" style="font-size: 11px;">{{ $user->getIdNumber() ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field-row" style="gap: 12px; margin-bottom: 6px;">
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 8px;">Member No.</div>
                            <div class="doc-field-value mono" style="font-size: 11px;">{{ $membership->membership_number ?? 'N/A' }}</div>
                        </div>
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 8px;">Status</div>
                            <div class="doc-field-value" style="font-size: 11px;"><span style="color:var(--emerald);">Good Standing</span></div>
                        </div>
                    </div>
                    <div class="doc-field-row" style="gap: 12px;">
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 8px;">Dedicated Status</div>
                            <div class="doc-field-value" style="font-size: 11px;">{{ $request->dedicated_status_label }}</div>
                        </div>
                        <div class="doc-field">
                            <div class="doc-field-label" style="font-size: 8px;">Category</div>
                            <div class="doc-field-value" style="font-size: 11px;">{{ $request->dedicated_category_label }}</div>
                        </div>
                    </div>
                </div>

                <div class="doc-section" style="padding: 10px 14px;">
                    <div class="doc-section-title" style="font-size: 10px; margin-bottom: 8px;">Letter Details</div>
                    <div class="doc-field" style="margin-bottom: 6px;">
                        <div class="doc-field-label" style="font-size: 8px;">Endorsement Reference</div>
                        <div class="doc-field-value mono" style="font-size: 11px;">{{ $request->letter_reference ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field" style="margin-bottom: 6px;">
                        <div class="doc-field-label" style="font-size: 8px;">Date Issued</div>
                        <div class="doc-field-value" style="font-size: 11px;">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Verification</div>
                        <div class="doc-field-value"><a href="{{ $verifyUrl }}" style="font-size:9px;">{{ $verifyUrl }}</a></div>
                    </div>
                </div>
            </div>

            <div style="height:8px"></div>

            {{-- Firearm Details --}}
            @if ($firearm)
            <div class="doc-section" style="padding: 10px 14px;">
                <div class="doc-section-title" style="font-size: 10px; margin-bottom: 8px;">Firearm Details</div>
                <div class="doc-field-row" style="gap: 12px; margin-bottom: 6px;">
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Make</div>
                        <div class="doc-field-value" style="font-size: 11px;">{{ $firearm->make_display ?? $firearm->make ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Model</div>
                        <div class="doc-field-value" style="font-size: 11px;">{{ $firearm->model_display ?? $firearm->model ?? 'N/A' }}</div>
                    </div>
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Calibre</div>
                        <div class="doc-field-value" style="font-size: 11px;">{{ $firearm->calibre_display ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="doc-field-row" style="gap: 12px;">
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Action</div>
                        <div class="doc-field-value" style="font-size: 11px;">{{ $firearm->action ?? 'N/A' }}</div>
                    </div>
                    @php
                        $serial = 'N/A';
                        if ($firearm->components && $firearm->components->isNotEmpty()) {
                            $serial = $firearm->components->where('component_type', 'receiver')->first()?->serial_number
                                ?? $firearm->components->where('component_type', 'frame')->first()?->serial_number
                                ?? $firearm->components->where('component_type', 'barrel')->first()?->serial_number
                                ?? 'N/A';
                        }
                    @endphp
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Serial Number</div>
                        <div class="doc-field-value mono" style="font-size: 11px;">{{ $serial }}</div>
                    </div>
                    @if (!empty($firearm->barrel_serial_number))
                    <div class="doc-field">
                        <div class="doc-field-label" style="font-size: 8px;">Barrel Serial</div>
                        <div class="doc-field-value mono" style="font-size: 11px;">{{ $firearm->barrel_serial_number }}</div>
                    </div>
                    @endif
                </div>
            </div>
            <div style="height:8px"></div>
            @endif

            {{-- Component Endorsements --}}
            @if ($request->components && $request->components->isNotEmpty())
            <div class="doc-section" style="padding: 10px 14px;">
                <div class="doc-section-title" style="font-size: 10px; margin-bottom: 8px;">Component Endorsements</div>
                @foreach ($request->components as $component)
                <div class="doc-field" style="padding:4px 0; border-bottom:1px solid var(--line);">
                    <div class="doc-field-label" style="font-size: 8px;">{{ $component->component_type_label }}</div>
                    <div class="doc-field-value" style="font-size: 11px;">
                        @if ($component->component_make || $component->component_model)
                            {{ trim(($component->component_make ?? '') . ' ' . ($component->component_model ?? '')) }}
                        @endif
                        @if ($component->component_serial)
                            <span style="font-size:9px; color:var(--muted);"> (Serial: {{ $component->component_serial }})</span>
                        @endif
                    </div>
                    @if ($component->component_type === 'barrel' && ($component->diameter || $component->calibre_display))
                        <div style="font-size: 9px; color: var(--muted);">{{ $component->diameter ? 'Diameter: ' . $component->diameter : 'Calibre: ' . $component->calibre_display }}</div>
                    @elseif ($component->component_type === 'action')
                        @if ($component->bolt_face_label ?? $component->bolt_face)
                            <div style="font-size: 9px; color: var(--muted);">Bolt face: {{ $component->bolt_face_label ?? $component->bolt_face }}</div>
                        @endif
                        @if ($component->action_type_label ?? $component->action_type)
                            <div style="font-size: 9px; color: var(--muted);">Action type: {{ $component->action_type_label ?? $component->action_type }}</div>
                        @endif
                    @elseif ($component->calibre_display)
                        <div style="font-size: 9px; color: var(--muted);">Calibre: {{ $component->calibre_display }}</div>
                    @endif
                    @if ($component->component_description)
                        <div style="font-size: 9px; color: var(--muted);">{{ $component->component_description }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            <div style="height:8px"></div>
            @endif

            {{-- Endorsement Statement --}}
            <div class="doc-notice" style="padding: 10px 14px; font-size: 11px; line-height: 1.5;">
                To whom it may concern,<br/><br/>
                This letter serves to confirm that <b>{{ $user->getIdName() }}</b> (ID/Passport: <b>{{ $user->getIdNumber() ?? 'N/A' }}</b>) is a
                <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
                <br/><br/>
                <b>This endorsement is issued for:</b> {{ $purposeText }}<br/>
                Issued under the member's {{ $request->dedicated_category_label }} status.
                <br/><br/>
                The Association supports the member's application for the {{ $firearm ? 'firearm' : 'component(s)' }} described above, subject to compliance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
            </div>
        </div>

        <div>
            <div style="height:10px"></div>

            {{-- QR + Signatory + Commissioner in one row --}}
            <div style="display: flex; gap: 12px;">
                <div class="doc-qr-section" style="padding: 10px 14px; flex: 0 0 auto;">
                    <div class="doc-qr-box" style="width: 70px; height: 70px;">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                    </div>
                    <div class="doc-qr-text" style="font-size: 9px;">
                        <span class="verify-label" style="font-size: 10px;">Verify Endorsement</span>
                        Scan QR code or visit:<br/>
                        <a href="{{ $verifyUrl }}" style="font-size: 8px;">{{ $verifyUrl }}</a>
                    </div>
                </div>

                <div class="doc-signatory" style="padding: 10px 14px; flex: 1;">
                    <div class="doc-field-label" style="font-size: 8px;">Authorised Signatory</div>
                    <div style="height:4px"></div>
                    <div class="placeholder-white signature-box" style="height: 36px;">
                        {!! $signatureHtml !!}
                    </div>
                    <div class="sig-line"></div>
                    <div class="sig-name" style="font-size: 11px;">{{ $signatory['name'] }}</div>
                    <div class="sig-title" style="font-size: 10px;">{{ $signatory['title'] }}</div>
                    <div style="font-size: 9px; color: var(--muted); margin-top:2px;">Issued: {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
                </div>

                <div class="doc-notice" style="padding: 10px 14px; flex: 1;">
                    <div class="doc-field-label" style="font-size: 8px; margin-bottom:4px;">Commissioner of Oaths</div>
                    <div class="placeholder-white oaths-scan" style="height:70px;">
                        {!! $commissionerHtml !!}
                    </div>
                </div>
            </div>

            <div style="margin-top: 8px; text-align: center; font-size: 8px; color: var(--muted);">
                This document is generated electronically and is valid without a physical signature when verified via QR code.
            </div>
        </div>
    </div>

    {{-- Blue Footer --}}
    <div class="doc-footer" style="padding: 8px 24px;">
        <div>
            <span class="doc-footer-cert" style="font-size: 9px;">{{ $request->letter_reference ?? 'N/A' }}</span>
            &nbsp;&mdash;&nbsp;
            <span style="font-size: 8px;">{{ $contact['email'] }} | {{ $contact['tel'] }}</span>
        </div>
        <div class="doc-footer-far" style="font-size: 7px;">
            FAR Sport: <span class="far-sport">{{ $farNumbers['sport'] }}</span>
            &nbsp;|&nbsp; Hunting: <span class="far-hunting">{{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>
@endsection
