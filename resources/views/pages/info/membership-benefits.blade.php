@extends('layouts.info')

@section('title', 'NRAPA Membership Benefits | Dedicated Status & Compliance')
@section('description', 'Benefits of NRAPA membership: SAPS-accredited dedicated sport shooter and hunter administration, digital certificates, activities tracking, and integration with Ranyati Group services.')
@section('heading', 'NRAPA membership benefits')
@section('breadcrumb', 'Membership benefits')

@section('content')
    <p>NRAPA membership is designed for South African firearm owners who want accredited association backing, structured compliance tools, and transparent record-keeping under the Firearms Control Act.</p>

    <h2>Core benefits</h2>
    <ul>
        <li><strong>Accredited association:</strong> NRAPA operates with SAPS accreditation relevant to dedicated sport shooter and dedicated hunter pathways (see <a href="{{ route('info.about') }}">About NRAPA</a> for FAR references).</li>
        <li><strong>Digital administration:</strong> Member portal for documents, certificates, activities, and endorsements where applicable.</li>
        <li><strong>QR-verified certificates:</strong> Where offered, certificates support verification by third parties.</li>
        <li><strong>Ecosystem access:</strong> Straightforward referrals to <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> and <a href="https://storage.ranyati.co.za">Ranyati Storage</a> when members need motivations or safe custody.</li>
    </ul>

    <h2>Who should join</h2>
    <p>Active sport shooters, hunters, and those building a compliant licensing history who value a single accredited hub for administration—not casual owners seeking shortcuts around the Act.</p>

    <p><a href="{{ route('register') }}">Register for NRAPA</a> · <a href="{{ route('info.faq') }}">FAQ</a></p>
@endsection
