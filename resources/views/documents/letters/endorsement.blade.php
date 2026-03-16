@extends('documents.layouts.nrapa-official')

@php
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getEndorsementQrCodeUrl($request, 200);
    $verifyUrl = $request->letter_reference
        ? url('/verify/endorsement/' . $request->letter_reference)
        : ($request->uuid ? url('/verify/endorsement/' . $request->uuid) : '#');
    $signatory = \App\Helpers\DocumentDataHelper::getEndorsementSignatoryInfo($request);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(\App\Helpers\DocumentDataHelper::getDefaultSignaturePath());
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml(\App\Helpers\DocumentDataHelper::getDefaultCommissionerScanPath());

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

    $title = 'Endorsement Letter — NRAPA';
@endphp

@section('document-banner')
<div class="doc-banner">
    <div class="doc-banner-title">Endorsement Letter</div>
    <div class="doc-banner-subtitle">Issued for firearm licence application purposes</div>
</div>
@endsection

@section('content')
    {{-- Info grid: Member + Letter details --}}
    <div class="info-grid">
        <div class="card">
            <div class="card-title">Applicant / Member</div>
            <div class="kv-row">
                <span class="kv-label">Full Name</span>
                <span class="kv-value">{{ $user->getIdName() }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">ID / Passport</span>
                <span class="kv-value">{{ $user->getIdNumber() ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Membership Number</span>
                <span class="kv-value">{{ $membership->membership_number ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Membership Status</span>
                <span class="kv-value" style="color:#1f6b3a; font-weight:600;">Member in Good Standing</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Dedicated Status</span>
                <span class="kv-value">{{ $request->dedicated_status_label }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Dedicated Category</span>
                <span class="kv-value">{{ $request->dedicated_category_label }}</span>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Letter Details</div>
            <div class="kv-row">
                <span class="kv-label">Endorsement Ref</span>
                <span class="kv-value">{{ $request->letter_reference ?? 'N/A' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Issued Date</span>
                <span class="kv-value">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Endorsed items --}}
    @php
        $hasComponents = $request->components && $request->components->isNotEmpty();
        $endorsedHeading = $firearm && $hasComponents
            ? 'Endorsed Firearm & Components'
            : ($firearm ? 'Endorsed Firearm' : ($hasComponents && $request->components->count() > 1 ? 'Endorsed Components' : 'Endorsed Component'));
    @endphp
    @if ($firearm || $hasComponents)
    <div class="card components-card">
        <div class="card-title">{{ $endorsedHeading }}</div>
        @if($firearm)
        @php
            $makeName = $firearm->make_display ?? '';
            $modelName = $firearm->model_display ?? '';
            $calibreName = $firearm->calibre_display ?? '';
            $actionLabel = $firearm->action_type ? ucfirst(str_replace('_', ' ', $firearm->action_type)) : null;
        @endphp
        <div class="firearm-grid">
            <div class="kv-row">
                <span class="kv-label">Type</span>
                <span class="kv-value" style="font-weight:700; font-size:13px;">{{ $firearm->firearm_type_label ?? 'Firearm' }}</span>
            </div>
            @if(trim($makeName . ' ' . $modelName))
            <div class="kv-row">
                <span class="kv-label">Make / Model</span>
                <span class="kv-value" style="font-weight:700; font-size:13px;">{{ trim($makeName . ' ' . $modelName) }}</span>
            </div>
            @endif
            @if($calibreName)
            <div class="kv-row">
                <span class="kv-label">Calibre</span>
                <span class="kv-value" style="font-weight:700; font-size:13px;">{{ $calibreName }}</span>
            </div>
            @endif
            @if($actionLabel)
            <div class="kv-row">
                <span class="kv-label">Action</span>
                <span class="kv-value" style="font-weight:700; font-size:13px;">{{ $actionLabel }}</span>
            </div>
            @endif
            @foreach($firearm->serial_numbers as $type => $info)
            <div class="kv-row">
                <span class="kv-label">{{ ucfirst($type) }} Serial</span>
                <span class="kv-value" style="font-weight:700; font-size:13px;">{{ $info['serial'] }}@if($info['make']) <span style="font-weight:400; color:var(--muted);">({{ $info['make'] }})</span>@endif</span>
            </div>
            @endforeach
        </div>
        @endif
        @if($hasComponents)
        @foreach ($request->components as $component)
        <div class="component-item">
            <div>
                <span class="component-type">{{ $component->component_type_label }}</span>
                @if ($component->component_make || $component->component_model)
                    &mdash; {{ trim(($component->component_make ?? '') . ' ' . ($component->component_model ?? '')) }}
                @endif
            </div>
            <div class="component-detail">
                @if ($component->component_type === 'barrel' && $component->diameter)
                    Diameter: {{ $component->diameter }}
                @elseif ($component->calibre_display)
                    Calibre: {{ $component->calibre_display }}
                @endif
                @if ($component->component_serial)
                    &nbsp;| Serial: {{ $component->component_serial }}
                @endif
            </div>
        </div>
        @endforeach
        @endif
    </div>
    @endif

    {{-- Letter body --}}
    <div class="letter-body">
        To whom it may concern,<br/><br/>
        This letter serves to confirm that <b>{{ $user->getIdName() }}</b>
        (ID/Passport: <b>{{ $user->getIdNumber() ?? 'N/A' }}</b>) is a
        <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
        <br/><br/>
        This endorsement is issued for the following purpose(s):
        <span class="purpose-line">{{ $purposeText }}</span>
        Issued under the member's <b>{{ $request->dedicated_category_label }}</b> status.
        <br/><br/>
        The Association confirms that the firearm or component(s) described above is suitable for the stated purpose in accordance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
    </div>

    {{-- Bottom: Signatory + Commissioner --}}
    <div class="bottom-grid">
        <div class="card signatory-card">
            <div class="card-title">Authorised NRAPA Signatory</div>
            <div class="sig-box">{!! $signatureHtml !!}</div>
            <div style="font-size:8px; color:var(--muted); margin-top:2px;">Placeholder must remain white.</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $signatory['name'] }}</div>
            <div class="sig-title">{{ $signatory['title'] }}</div>
            <div class="sig-date">Issued at {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
        </div>

        <div class="card commissioner-card">
            <div class="card-title">Commissioner of Oaths</div>
            <div class="commissioner-box">
                @if($commissionerHtml && trim(strip_tags($commissionerHtml)))
                    {!! $commissionerHtml !!}
                @else
                    Commissioner of Oaths scan
                @endif
            </div>
            <div class="commissioner-sub">Upload commissioned scan in admin dashboard. Placeholder must remain white.</div>
        </div>
    </div>

    {{-- Verification row --}}
    <div class="verify-row">
        <div style="display:flex; gap:10px; align-items:flex-start;">
            <div class="qr-box">
                <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
            </div>
            <div class="verify-text">
                <strong>Verify this endorsement</strong>
                Scan the QR code or visit the link below.
                <br/>
                <a href="{{ $verifyUrl }}" style="color:var(--blue); word-break:break-all; font-size:8px;">{{ $verifyUrl }}</a>
            </div>
        </div>
    </div>
@endsection
