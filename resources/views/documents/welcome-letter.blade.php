@extends('documents.base')

@php
    $variant = 'doc--welcome';
    $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
@endphp

@section('content')
<div class="doc-header">
    <div class="doc-logo">
        @if(isset($logo_url))
            <img src="{{ $logo_url }}" alt="NRAPA">
        @else
            <div style="width:100%; height:100%; background:linear-gradient(135deg, #0f4c81 0%, #3b82f6 100%); display:grid; place-items:center; color:#fff; font-weight:bold; font-size:10pt;">NRAPA</div>
        @endif
    </div>
    <div class="doc-org">
        <h1>National Rifle & Pistol Association</h1>
        <div class="sub">of South Africa</div>
        <div class="doc-badge">
            <span class="dot"></span>
            <span>FAR Accredited | SAPS Recognised</span>
        </div>
        <div style="margin-top: 8px; font-size: 11px; color: var(--text);">
            <span><b>FAR Sport Shooting:</b> {{ $farNumbers['sport'] }}</span>
            <span style="margin-left: 12px;"><b>FAR Hunting:</b> {{ $farNumbers['hunting'] }}</span>
        </div>
    </div>
</div>

<div class="doc-title">
    <h2>Welcome Letter</h2>
    <div class="note">Informational</div>
</div>

<div class="doc-block">
    <div style="margin-bottom:16px;">
        <p class="doc-label" style="font-size:10pt; margin-bottom:4px;">{{ now()->format('d F Y') }}</p>
        <p class="doc-value" style="font-size:11pt; margin-bottom:4px;">{{ $user->getIdName() }}</p>
        @if($user->physical_address)
            <p class="doc-label" style="font-size:9.5pt; line-height:1.4;">{{ $user->physical_address }}</p>
        @endif
        @if($user->postal_address && $user->postal_address !== $user->physical_address)
            <p class="doc-label" style="font-size:9.5pt; line-height:1.4; margin-top:4px;">{{ $user->postal_address }}</p>
        @endif
    </div>
</div>

<div class="doc-block">
    <p class="doc-para">
        <strong>Dear {{ $user->getIdFirstNames() }},</strong>
    </p>
    
    <p class="doc-para" style="margin-top:12px;">
        On behalf of the National Rifle & Pistol Association of South Africa, I would like to extend a warm welcome to you as a new member of our Association.
    </p>
    
    <p class="doc-para" style="margin-top:12px;">
        Your membership has been activated and you are now part of a community dedicated to promoting responsible firearm ownership, marksmanship, and the advancement of shooting sports in South Africa.
    </p>
    
    @if($membership)
    <div class="doc-block" style="margin-top:16px; border-left:3px solid var(--accent); padding-left:12px;">
        <h3 style="margin-bottom:8px;">Your Membership Details</h3>
        <div class="doc-row">
            <span class="doc-label">Membership Number:</span>
            <span class="doc-value">{{ $membership->membership_number }}</span>
        </div>
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Membership Type:</span>
            <span class="doc-value">{{ $membership->type->name ?? 'N/A' }}</span>
        </div>
        @if($membership->expires_at)
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Valid Until:</span>
            <span class="doc-value">{{ $membership->expires_at->format('d F Y') }}</span>
        </div>
        @else
        <div class="doc-row" style="margin-top:6px;">
            <span class="doc-label">Membership:</span>
            <span class="doc-value">Lifetime</span>
        </div>
        @endif
    </div>
    @endif
    
    <p class="doc-para" style="margin-top:16px;">
        As a member of NRAPA, you have access to a range of benefits and services, including:
    </p>
    
    <ul class="doc-list">
        <li>Endorsement letters for firearm licence applications</li>
        <li>Dedicated status certification (subject to meeting requirements)</li>
        <li>Activity tracking and compliance management</li>
        <li>Access to member resources and support</li>
        <li>Participation in NRAPA events and activities</li>
    </ul>
    
    <p class="doc-para" style="margin-top:12px;">
        We encourage you to explore your member portal where you can manage your membership, submit activities, request endorsements, and access important documents.
    </p>
    
    <p class="doc-para" style="margin-top:12px;">
        If you have any questions or need assistance, please do not hesitate to contact our administration team.
    </p>
    
    <p class="doc-para" style="margin-top:12px;">
        Once again, welcome to NRAPA. We look forward to supporting you in your shooting journey.
    </p>
</div>

<div class="doc-block" style="margin-top:auto;">
    <p class="doc-para" style="margin-bottom:8px;">Yours sincerely,</p>
    <div class="doc-signature">
        <div class="doc-value">NRAPA Administration</div>
    </div>
</div>

<div class="doc-footer">
    <div>
        <strong>National Rifle & Pistol Association of South Africa</strong><br>
        nrapa.ranyati.co.za
    </div>
    <div style="text-align:right;">
        This is an informational document. For official certificates, please refer to your member portal.
    </div>
</div>
@endsection
