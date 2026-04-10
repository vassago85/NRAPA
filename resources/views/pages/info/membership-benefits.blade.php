@extends('layouts.info')

@section('title', 'NRAPA Membership Benefits | Dedicated Status & Compliance')
@section('description', 'Benefits of NRAPA membership: SAPS-accredited dedicated sport shooter and hunter administration, digital certificates, activities tracking, and integration with Ranyati Group services.')
@section('heading', 'NRAPA membership benefits')
@section('breadcrumb', 'Membership benefits')

@section('content')
    <p>NRAPA membership is designed for South African firearm owners who want accredited association backing, structured compliance tools, and transparent record-keeping under the Firearms Control Act.</p>

    <h2>Core benefits</h2>
    <ul class="checklist">
        <li><strong>Accredited association</strong> — NRAPA operates with SAPS accreditation relevant to dedicated sport shooter and dedicated hunter pathways (see <a href="{{ route('info.about') }}">About NRAPA</a> for FAR references)</li>
        <li><strong>Digital administration</strong> — Member portal for documents, certificates, activities, and endorsements</li>
        <li><strong>QR-verified certificates</strong> — Certificates support verification by third parties via QR code scan</li>
        <li><strong>Ecosystem access</strong> — Straightforward referrals to <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> and <a href="https://storage.ranyati.co.za">Ranyati Storage</a> when members need motivations or safe custody</li>
        <li><strong>Activity tracking</strong> — Log events, targets, and scores to maintain your dedicated status</li>
        <li><strong>Endorsement letters</strong> — Request and receive endorsement letters through the portal</li>
    </ul>

    <h2>Who should join</h2>
    <p>Active sport shooters, hunters, and those building a compliant licensing history who value a single accredited hub for administration — not casual owners seeking shortcuts around the Act.</p>

    <h2>Get started</h2>
    <ul class="link-list">
        <li><a href="{{ route('register') }}">Register for NRAPA</a></li>
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('info.faq') }}">FAQ</a></li>
    </ul>
@endsection
