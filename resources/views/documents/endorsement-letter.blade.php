@extends('documents.base')

@section('content')
<div style="text-align: center; margin-bottom: 3rem;">
    <h2 style="font-size: 32px; font-weight: bold; color: #1e40af; margin-bottom: 1rem;">ENDORSEMENT LETTER</h2>
    <h3 style="font-size: 24px; font-weight: normal; color: #374151; margin-bottom: 2rem;">Firearm Licence Application</h3>
</div>

<div style="margin-bottom: 2rem;">
    <p style="margin-bottom: 1rem;"><strong>Reference:</strong> {{ $request->letter_reference ?? 'N/A' }}</p>
    <p style="margin-bottom: 1rem;"><strong>Date:</strong> {{ $request->issued_at?->format('d F Y') ?? now()->format('d F Y') }}</p>
</div>

<div style="margin-bottom: 2rem;">
    <p>To Whom It May Concern,</p>
</div>

<div style="margin-bottom: 2rem; line-height: 1.8;">
    <p style="margin-bottom: 1rem;">
        This letter serves to confirm that <strong>{{ $user->name }}</strong> (Membership Number: {{ $membership?->membership_number ?? 'N/A' }}) 
        is a member in good standing of the National Rifle & Pistol Association of South Africa (NRAPA).
    </p>
    
    @if($request->firearm)
        <p style="margin-bottom: 1rem;">
            We hereby endorse the application for a Section 16 firearm licence for the following firearm:
        </p>
        
        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
            <p style="margin-bottom: 0.5rem;"><strong>Firearm Type:</strong> {{ $request->firearm->firearm_type_label ?? 'N/A' }}</p>
            <p style="margin-bottom: 0.5rem;"><strong>Make:</strong> {{ $request->firearm->make ?? 'N/A' }}</p>
            <p style="margin-bottom: 0.5rem;"><strong>Model:</strong> {{ $request->firearm->model ?? 'N/A' }}</p>
            <p style="margin-bottom: 0.5rem;"><strong>Calibre:</strong> {{ $request->firearm->calibre?->name ?? 'N/A' }}</p>
            @if($request->firearm->action_type)
                <p style="margin-bottom: 0.5rem;"><strong>Action:</strong> {{ ucfirst(str_replace('_', ' ', $request->firearm->action_type)) }}</p>
            @endif
            @if($request->components && $request->components->isNotEmpty())
                <p style="margin-bottom: 0.5rem;"><strong>Serial Numbers:</strong></p>
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    @foreach($request->components as $component)
                        <li>{{ ucfirst($component->type) }}: {{ $component->serial ?? 'N/A' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <p style="margin-bottom: 1rem;">
        The member has demonstrated their commitment to responsible firearm ownership and participation in dedicated status activities 
        as required by NRAPA membership standards.
    </p>

    <p style="margin-bottom: 1rem;">
        This endorsement is valid for the purpose of supporting the member's application for a Section 16 firearm licence 
        with the South African Police Service (SAPS).
    </p>
</div>

<div style="margin-top: 3rem;">
    <p style="margin-bottom: 2rem;">Yours sincerely,</p>
    <p style="margin-bottom: 0.5rem;"><strong>NRAPA Administration</strong></p>
    <p style="margin-bottom: 0.5rem;">National Rifle & Pistol Association of South Africa</p>
    <p>www.nrapa.co.za</p>
</div>
@endsection
