@extends('documents.base')

@section('content')
<div style="text-align: center; margin-bottom: 2rem;">
    <div style="display: inline-block; padding: 2rem 4rem; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border-radius: 12px; color: white; margin-bottom: 2rem;">
        <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 0.5rem;">NRAPA</h2>
        <p style="font-size: 14px; opacity: 0.9;">Membership Card</p>
    </div>
</div>

<div style="max-width: 600px; margin: 0 auto; padding: 2rem; background: rgba(30, 64, 175, 0.05); border-radius: 8px; border: 2px solid #1e40af;">
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Member Name</p>
        <p style="font-size: 24px; font-weight: bold; color: #1e40af;">{{ $certificate->user->name }}</p>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Membership Number</p>
        <p style="font-size: 20px; font-weight: bold; font-family: monospace; color: #1e40af;">{{ $certificate->membership->membership_number ?? 'N/A' }}</p>
    </div>
    
    @if($certificate->membership->type)
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Membership Type</p>
        <p style="font-size: 16px; font-weight: bold; color: #374151;">{{ $certificate->membership->type->name }}</p>
    </div>
    @endif
    
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">FCA Status</p>
        <p style="font-size: 16px; font-weight: bold; color: #374151;">Active Member</p>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Enrolment Date</p>
        <p style="font-size: 16px; font-weight: bold; color: #374151;">{{ $certificate->membership->activated_at?->format('d M Y') ?? $certificate->membership->applied_at?->format('d M Y') ?? 'N/A' }}</p>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Expiry</p>
        <p style="font-size: 16px; font-weight: bold; color: #374151;">
            @if($certificate->membership->expires_at)
                {{ $certificate->membership->expires_at->format('d M Y') }}
            @else
                Lifetime
            @endif
        </p>
    </div>
</div>

<div style="margin-top: 2rem; text-align: center; font-size: 10px; color: #9ca3af;">
    <p>This card is valid as long as membership remains active and in good standing.</p>
    @if($certificate->certificate_number)
        <p style="margin-top: 0.5rem;">Card Number: {{ $certificate->certificate_number }}</p>
    @endif
</div>

@endsection
