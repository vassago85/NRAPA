@extends('layouts.info')

@section('title', 'Dedicated Sport Shooter South Africa | NRAPA')
@section('description', 'What dedicated sport shooter status means in South Africa, how SAPS-accredited associations like NRAPA fit in, and how to stay compliant with the Firearms Control Act.')
@section('heading', 'Dedicated sport shooter in South Africa')
@section('breadcrumb', 'Dedicated sport shooter')

@section('content')
    <p>Dedicated sport shooter status is an administrative pathway under the Firearms Control Act for members of SAPS-accredited sport shooting associations. NRAPA is accredited for this purpose and provides membership, activity recording, and certificates aligned with regulatory expectations.</p>

    <h2>Who it is for</h2>
    <p>Competitors and active participants in recognised shooting disciplines — such as practical shooting (IPSC/IDPA-style), precision rifle, gong shooting, and related formats — who need association-backed evidence of ongoing sport shooting activity.</p>

    <h2>What NRAPA provides</h2>
    <ul class="checklist">
        <li>Membership administration and digital records</li>
        <li>Processes for dedicated status aligned with SAPS accreditation</li>
        <li>Activity logging, scorecards, and certificate generation</li>
        <li>QR-verified certificates for third-party verification</li>
    </ul>

    <h2>Next steps</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure</a></li>
        <li><a href="{{ route('info.shooting-exercises') }}">Shooting disciplines (reference)</a></li>
        <li><a href="{{ route('register') }}">Register for NRAPA</a></li>
        <li><a href="https://motivations.ranyati.co.za/firearm-licence-motivation-sport-shooting">Sport shooting motivations (Ranyati Motivations)</a></li>
    </ul>
@endsection
