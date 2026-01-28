@extends('documents.base')

@php
    $verificationUrl = isset($certificate) && $certificate->qr_code 
        ? route('certificates.verify', ['qr_code' => $certificate->qr_code])
        : '#';
    $qrCodeUrl = \App\Helpers\QrCodeHelper::generateUrl($verificationUrl, 120);
@endphp

@push('document-styles')
<style>
    .doc-sheet { width: 86mm; height: 54mm; min-height: 54mm; }
    .doc-page { padding: 8mm; min-height: 54mm; }
</style>
@endpush

@section('content')
<div class="doc-card-inner">
    <div class="doc-card-top">
        <div class="doc-card-logo">
            @if(isset($logo_url))
                <img src="{{ $logo_url }}" alt="NRAPA">
            @else
                <div style="width:100%; height:100%; background:linear-gradient(135deg, #0f4c81 0%, #3b82f6 100%); display:grid; place-items:center; color:#fff; font-weight:bold; font-size:7pt;">NRAPA</div>
            @endif
        </div>
        <div>
            <div class="doc-card-org">NRAPA</div>
            <div class="doc-card-meta">Membership Card</div>
        </div>
    </div>
    
    <div class="doc-card-row">
        <div>
            <div class="doc-card-name">{{ $certificate->user->name }}</div>
            <div class="doc-card-small" style="margin-top:2px;">
                #{{ $certificate->membership->membership_number ?? 'N/A' }}
            </div>
            @if($certificate->membership->type)
            <div class="doc-card-small" style="margin-top:4px;">
                {{ $certificate->membership->type->name }}
            </div>
            @endif
        </div>
        @if($certificate->qr_code)
        <div class="doc-card-qr">
            <img src="{{ $qrCodeUrl }}" alt="QR Code">
        </div>
        @endif
    </div>
    
    <div style="margin-top:auto; display:flex; justify-content:space-between; align-items:flex-end; font-size:7.5pt; color:rgba(255,255,255,.92);">
        <div>
            <div>FCA Status: <strong style="color:#fff;">Active</strong></div>
            <div style="margin-top:2px;">
                Enrolled: {{ $certificate->membership->activated_at?->format('M Y') ?? $certificate->membership->applied_at?->format('M Y') ?? 'N/A' }}
            </div>
        </div>
        <div style="text-align:right;">
            <div>
                @if($certificate->membership->expires_at)
                    Expires: {{ $certificate->membership->expires_at->format('M Y') }}
                @else
                    <strong style="color:#fff;">Lifetime</strong>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
