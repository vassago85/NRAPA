@extends('documents.base')

@section('content')
<div style="margin-bottom: 2rem;">
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 1rem;">{{ now()->format('d F Y') }}</p>
    
    <div style="margin-bottom: 2rem;">
        <p style="font-size: 16px; font-weight: bold; color: #1e40af; margin-bottom: 0.5rem;">{{ $user->name }}</p>
        @if($user->physical_address)
            <p style="font-size: 14px; color: #374151; line-height: 1.6;">{{ $user->physical_address }}</p>
        @endif
        @if($user->postal_address && $user->postal_address !== $user->physical_address)
            <p style="font-size: 14px; color: #374151; line-height: 1.6; margin-top: 0.5rem;">{{ $user->postal_address }}</p>
        @endif
    </div>
</div>

<div style="margin-bottom: 2rem;">
    <p style="font-size: 16px; font-weight: bold; color: #1e40af; margin-bottom: 1rem;">Dear {{ explode(' ', $user->name)[0] }},</p>
</div>

<div style="margin-bottom: 2rem; line-height: 1.8;">
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        On behalf of the National Rifle & Pistol Association of South Africa, I would like to extend a warm welcome to you as a new member of our Association.
    </p>
    
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        Your membership has been activated and you are now part of a community dedicated to promoting responsible firearm ownership, marksmanship, and the advancement of shooting sports in South Africa.
    </p>
    
    @if($membership)
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        Your membership details are as follows:
    </p>
    
    <div style="margin: 1.5rem 0; padding: 1.5rem; background: rgba(30, 64, 175, 0.05); border-left: 4px solid #1e40af;">
        <p style="font-size: 14px; margin-bottom: 0.5rem;"><strong>Membership Number:</strong> {{ $membership->membership_number }}</p>
        <p style="font-size: 14px; margin-bottom: 0.5rem;"><strong>Membership Type:</strong> {{ $membership->type->name ?? 'N/A' }}</p>
        @if($membership->expires_at)
            <p style="font-size: 14px;"><strong>Valid Until:</strong> {{ $membership->expires_at->format('d F Y') }}</p>
        @else
            <p style="font-size: 14px;"><strong>Membership:</strong> Lifetime</p>
        @endif
    </div>
    @endif
    
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        As a member of NRAPA, you have access to a range of benefits and services, including:
    </p>
    
    <ul style="font-size: 16px; margin-left: 2rem; margin-bottom: 1rem; line-height: 2;">
        <li>Endorsement letters for firearm licence applications</li>
        <li>Dedicated status certification (subject to meeting requirements)</li>
        <li>Activity tracking and compliance management</li>
        <li>Access to member resources and support</li>
        <li>Participation in NRAPA events and activities</li>
    </ul>
    
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        We encourage you to explore your member portal where you can manage your membership, submit activities, request endorsements, and access important documents.
    </p>
    
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        If you have any questions or need assistance, please do not hesitate to contact our administration team.
    </p>
    
    <p style="font-size: 16px; margin-bottom: 1rem; text-align: justify;">
        Once again, welcome to NRAPA. We look forward to supporting you in your shooting journey.
    </p>
</div>

<div style="margin-top: 3rem;">
    <p style="font-size: 16px; margin-bottom: 0.5rem;">Yours sincerely,</p>
    <p style="font-size: 16px; font-weight: bold; color: #1e40af; margin-bottom: 2rem;">NRAPA Administration</p>
    
    <p style="font-size: 14px; color: #6b7280;">
        National Rifle & Pistol Association of South Africa<br>
        FAR Accredited | SAPS Recognised
    </p>
</div>

@endsection
