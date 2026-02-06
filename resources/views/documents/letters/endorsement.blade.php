@extends('documents.layouts.nrapa-official')

@php
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
    $logoUrl = \App\Helpers\DocumentDataHelper::getLogoUrl();
    $qrCodeUrl = \App\Helpers\DocumentDataHelper::getEndorsementQrCodeUrl($request, 200);
    $verifyUrl = $request->letter_reference 
        ? url('/verify/endorsement/' . $request->letter_reference)
        : ($request->uuid ? url('/verify/endorsement/' . $request->uuid) : '#');
    $signatory = \App\Helpers\DocumentDataHelper::getEndorsementSignatoryInfo($request);
    $signatureHtml = \App\Helpers\DocumentDataHelper::getSignatureImageHtml(null); // Endorsements use system default
    $commissionerHtml = \App\Helpers\DocumentDataHelper::getCommissionerScanHtml(null); // Can be added later
    $contact = \App\Helpers\DocumentDataHelper::getContactInfo();
    
    $user = $request->user;
    $membership = $user->activeMembership;
    $firearm = $request->firearm;
    
    // Get endorsement purpose text
    $purposeText = match($request->purpose) {
        'section_16_application' => 'Section 16 firearm licence application',
        'status_confirmation' => 'Status confirmation for regulatory purposes',
        'licence_renewal' => 'Firearm licence renewal application',
        'additional_firearm' => 'Application for additional firearm',
        'other' => $request->purpose_other_text ?? 'Other purpose',
        default => 'Firearm licence application',
    };
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

<div class="titlebar">
    <div class="h1">ENDORSEMENT LETTER</div>
    <div class="small">Issued for firearm licence application purposes</div>
</div>

<hr class="sep"/>

<div class="grid">
    <section class="card">
        <div class="h2">Applicant / Member</div>
        <div style="height:10px"></div>
        <div class="kv">
            <div class="k">Full Name</div><div class="v">{{ $user->getIdName() }}</div>
            <div class="k">ID / Passport</div><div class="v">{{ $user->id_number ?? 'N/A' }}</div>
            <div class="k">Membership Number</div><div class="v">{{ $membership->membership_number ?? 'N/A' }}</div>
            <div class="k">Membership Status</div><div class="v">Member in Good Standing</div>
            <div class="k">Dedicated Status</div><div class="v">{{ $request->dedicated_status_label }}</div>
            <div class="k">Dedicated Category</div><div class="v">{{ $request->dedicated_category_label }}</div>
        </div>
    </section>

    <section class="card">
        <div class="h2">Letter Details</div>
        <div style="height:10px"></div>
        <div class="kv">
            <div class="k">Endorsement Ref</div><div class="v">{{ $request->letter_reference ?? 'N/A' }}</div>
            <div class="k">Issued Date</div><div class="v">{{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div class="k">Verification Link</div><div class="v"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
        </div>
    </section>
</div>

<div style="height:6px"></div>

@if($firearm)
<section class="card">
    <div class="h2">Firearm Details</div>
    <div style="height:10px"></div>
    <div class="kv">
        <div class="k">Make</div><div class="v">{{ $firearm->make_display ?? $firearm->make ?? 'N/A' }}</div>
        <div class="k">Model</div><div class="v">{{ $firearm->model_display ?? $firearm->model ?? 'N/A' }}</div>
        <div class="k">Calibre</div><div class="v">{{ $firearm->calibre_display ?? 'N/A' }}</div>
        <div class="k">Action</div><div class="v">{{ $firearm->action ?? 'N/A' }}</div>
        @if($firearm->components && $firearm->components->isNotEmpty())
            @php
                $serial = $firearm->components->where('component_type', 'receiver')->first()?->serial_number 
                    ?? $firearm->components->where('component_type', 'frame')->first()?->serial_number
                    ?? $firearm->components->where('component_type', 'barrel')->first()?->serial_number
                    ?? 'N/A';
            @endphp
            <div class="k">Serial Number</div><div class="v">{{ $serial }}</div>
        @else
            <div class="k">Serial Number</div><div class="v">N/A</div>
        @endif
        @if(!empty($firearm->barrel_serial_number))
            <div class="k">Barrel Serial Number</div><div class="v">{{ $firearm->barrel_serial_number }}</div>
        @endif
    </div>
</section>

<div style="height:6px"></div>
@endif

@if($request->components && $request->components->isNotEmpty())
<section class="card">
    <div class="h2">Component Endorsements</div>
    <div style="height:10px"></div>
    <div class="kv">
        @foreach($request->components as $component)
            <div class="k">{{ $component->component_type_label }}</div>
            <div class="v">
                @if($component->component_make || $component->component_model)
                    {{ trim(($component->component_make ?? '') . ' ' . ($component->component_model ?? '')) }}
                @endif
                @if($component->component_type === 'barrel')
                    @if($component->diameter)
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">Diameter: {{ $component->diameter }}</span>
                    @elseif($component->calibre_display)
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">Calibre: {{ $component->calibre_display }}</span>
                    @endif
                @elseif($component->calibre_display)
                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">Calibre: {{ $component->calibre_display }}</span>
                @endif
                @if($component->component_serial)
                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">Serial: {{ $component->component_serial }}</span>
                @endif
                @if($component->component_description)
                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $component->component_description }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>

<div style="height:6px"></div>
@endif

<section class="notice">
    To whom it may concern,<br/><br/>
    This letter serves to confirm that <b>{{ $user->getIdName() }}</b> (ID/Passport: <b>{{ $user->id_number ?? 'N/A' }}</b>) is a
    <b>member in good standing</b> of the National Rifle &amp; Pistol Association of South Africa (NRAPA).
    <br/><br/>
    <b>This endorsement is issued for the following purpose(s):</b><br/>
    Section 16 firearm licence application<br/>
    Issued under the member&apos;s {{ $request->dedicated_category_label }} status
    <br/><br/>
    The Association supports the member&apos;s application for the firearm described above, issued under the member&apos;s compliant dedicated status, subject to compliance with the Firearms Control Act (Act 60 of 2000, as amended) and relevant Regulations.
</section>

<div style="height:6px"></div>

<div class="sig-grid">
    <section class="sig">
        <div class="h2">Verification</div>
        <div style="height:8px"></div>
        <div style="display:flex; gap:12px; align-items:center;">
            <div class="qr"><img src="{{ $qrCodeUrl }}" alt="QR Code"/></div>
            <div>
                <div style="font-size:12px; font-weight:700;">Verify this endorsement</div>
                <div class="small">Scan the QR code or visit the verification link above.</div>
                <div class="small" style="margin-top:6px;">Online status shown: <b>Member in Good Standing</b></div>
            </div>
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
        <div class="small" style="margin-top:8px;">Issued at {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</div>
    </section>
</div>

<div style="height:6px"></div>

<section class="sig">
    <div class="h2">Commissioner of Oaths (Scan Upload - Optional)</div>
    <div class="small" style="margin-top:6px;">If required, upload the commissioned scan in the admin dashboard. Placeholder must remain white.</div>
    <div style="height:10px"></div>
    <div class="placeholder-white oaths-scan">
        {!! $commissionerHtml !!}
    </div>
</section>

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
