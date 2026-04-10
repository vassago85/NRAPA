@extends('layouts.info')

@section('title', 'Dedicated Hunter South Africa | NRAPA')
@section('description', 'Dedicated hunter status in South Africa: SAPS-accredited association membership through NRAPA, lawful hunting activity, and compliance with the Firearms Control Act.')
@section('heading', 'Dedicated hunter in South Africa')
@section('breadcrumb', 'Dedicated hunter')

@section('content')
    <p>Dedicated hunter status links lawful hunting activity to membership in a SAPS-accredited hunting or combined association. NRAPA supports members who pursue this pathway with structured administration and proof-of-activity tools.</p>

    <h2>Responsibilities of the hunter</h2>
    <ul class="checklist">
        <li>Hunt lawfully and respect provincial seasons and property rights</li>
        <li>Hold valid licences for all firearms used</li>
        <li>Maintain evidence of activity that SAPS may expect at renewal or inspection</li>
        <li>Submit annual dedicated activities reports to NRAPA</li>
    </ul>

    <h2>How NRAPA helps</h2>
    <ul class="checklist">
        <li>Accredited association membership with SAPS recognition</li>
        <li>Guidance on internal procedures and compliance</li>
        <li>Member portal for activity submissions and certificates</li>
        <li>QR-verified certificates for third-party verification</li>
    </ul>

    <h2>Related pages</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure</a></li>
        <li><a href="{{ route('register') }}">Register for NRAPA</a></li>
        <li><a href="https://motivations.ranyati.co.za/firearm-licence-motivation-hunting">Hunting motivations (Ranyati Motivations)</a></li>
    </ul>
@endsection
