@extends('layouts.info')

@section('title', 'NRAPA Information & Guides')
@section('description', 'Official NRAPA guides: dedicated sport shooter and hunter status in South Africa, membership benefits, endorsements, and how dedicated status fits into the Firearms Control Act.')
@section('heading', 'Information & guides')
@section('breadcrumb', 'Info & guides')
@section('short_breadcrumb', '1')

@section('content')
    <p>Use these pages to understand how NRAPA supports lawful firearm owners under the Firearms Control Act. NRAPA is a SAPS-accredited association for dedicated sport shooter and dedicated hunter pathways, operated within the <a href="https://ranyati.co.za">Ranyati Group</a> ecosystem.</p>

    <h2>Dedicated status &amp; membership</h2>
    <ul>
        <li><a href="{{ route('info.dedicated-sport-shooter-south-africa') }}">Dedicated sport shooter (South Africa)</a></li>
        <li><a href="{{ route('info.dedicated-hunter-south-africa') }}">Dedicated hunter (South Africa)</a></li>
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure (detailed)</a></li>
        <li><a href="{{ route('info.membership-benefits') }}">Membership benefits</a></li>
    </ul>

    <h2>Endorsements &amp; administration</h2>
    <ul>
        <li><a href="{{ route('info.endorsements') }}">Endorsement letters — what they are</a></li>
        <li><a href="{{ route('info.firearm-licence-process') }}">Firearm licence process overview</a></li>
        <li><a href="{{ route('info.minimum-requirements') }}">Minimum requirements</a></li>
    </ul>

    <h2>Training &amp; activities</h2>
    <ul>
        <li><a href="{{ route('info.shooting-exercises') }}">Shooting exercises &amp; activities</a></li>
    </ul>

    <h2>General</h2>
    <ul>
        <li><a href="{{ route('info.about') }}">About NRAPA</a></li>
        <li><a href="{{ route('info.faq') }}">Frequently asked questions</a></li>
    </ul>

    <h2>Sister services</h2>
    <p>For professional licence motivations, see <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a>. For compliant firearm storage, see <a href="https://storage.ranyati.co.za">Ranyati Storage</a>.</p>
@endsection
