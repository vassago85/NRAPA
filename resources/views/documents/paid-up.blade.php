@extends('documents.base')

@section('content')
<div style="text-align: center; margin-bottom: 3rem;">
    <h2 style="font-size: 28px; font-weight: bold; color: #1e40af; margin-bottom: 1rem;">PROOF OF PAID-UP MEMBERSHIP</h2>
    <h3 style="font-size: 20px; font-weight: normal; color: #374151; margin-bottom: 2rem;">CERTIFICATE</h3>
</div>

<div style="margin-bottom: 3rem; padding: 2rem; background: rgba(30, 64, 175, 0.05); border-radius: 8px; border: 2px solid #1e40af;">
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        This is to certify that
    </p>
    
    <div style="text-align: center; margin: 2rem 0;">
        <p style="font-size: 28px; font-weight: bold; color: #1e40af; margin-bottom: 0.5rem;">
            {{ $certificate->user->name }}
        </p>
        @if($certificate->user->id_number)
            <p style="font-size: 14px; color: #6b7280;">ID Number: {{ $certificate->user->id_number }}</p>
        @endif
    </div>
    
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        Membership Number: <strong>{{ $certificate->membership->membership_number ?? 'N/A' }}</strong>
    </p>
    
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        is a <strong>paid-up member in good standing</strong> of the National Rifle & Pistol Association of South Africa.
    </p>
    
    @if($certificate->membership->type)
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        Membership Type: <strong>{{ $certificate->membership->type->name }}</strong>
    </p>
    @endif
    
    @if($certificate->membership->expires_at)
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        Membership Valid Until: <strong>{{ $certificate->membership->expires_at->format('d F Y') }}</strong>
    </p>
    @else
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        Membership Type: <strong>Lifetime</strong>
    </p>
    @endif
    
    <p style="font-size: 16px; line-height: 1.8; text-align: justify; margin-bottom: 1.5rem;">
        This certificate confirms that the member's account is current and all membership fees have been paid.
        The member is in good standing with the Association.
    </p>
</div>

<div style="margin-top: 4rem; display: flex; justify-content: space-between; align-items: flex-end;">
    <div style="flex: 1;">
        <div style="border-top: 2px solid #1e40af; padding-top: 0.5rem; width: 250px;">
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Issued Date</p>
            <p style="font-size: 14px; font-weight: bold;">{{ $certificate->issued_at->format('d F Y') }}</p>
            @if($certificate->valid_until)
                <p style="font-size: 12px; color: #6b7280; margin-top: 0.5rem;">Valid Until</p>
                <p style="font-size: 14px; font-weight: bold;">{{ $certificate->valid_until->format('d F Y') }}</p>
            @endif
        </div>
    </div>
    
    <div style="flex: 1; text-align: right;">
        <div style="border-top: 2px solid #1e40af; padding-top: 0.5rem; display: inline-block; min-width: 250px;">
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Authorised Signatory</p>
            <p style="font-size: 14px; font-weight: bold;">NRAPA Administration</p>
        </div>
    </div>
</div>

@if($certificate->certificate_number)
<div style="margin-top: 2rem; text-align: center; font-size: 10px; color: #9ca3af;">
    Certificate Number: {{ $certificate->certificate_number }}
</div>
@endif

@endsection
