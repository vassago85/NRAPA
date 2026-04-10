@extends('layouts.info')

@section('title', 'About NRAPA - National Rifle & Pistol Association of South Africa')
@section('description', 'NRAPA is a SAPS-accredited association providing dedicated sport shooter and hunter status, firearm licence administration, and compliance management for South African firearm owners.')
@section('heading', 'About NRAPA')
@section('breadcrumb', 'About')

@section('content')
    <div class="info-card">
        <h4>Key Facts</h4>
        <p>SAPS-accredited with FAR numbers <strong>1300122</strong> (Sport Shooting) and <strong>1300127</strong> (Hunting). Part of <a href="https://ranyati.co.za">Ranyati Group</a>.</p>
    </div>

    <p>National Rifle and Pistol Association (NRAPA) is accredited by the S.A. Police Services with the designated powers to allocate Dedicated Sport and Hunter status to its members.</p>
    <p>NRAPA's purpose is to provide a one-stop service with regards to the administration of the Firearms Control Act. We make it simpler for the general public to comply with the requirements of the Act.</p>

    <h2>What We Do</h2>
    <ul class="checklist">
        <li>Promoting practical shooting (IDPA, IPSC formats), handguns, semi-auto rifles, manually operated rifles and shotguns</li>
        <li>Promoting gong shooting, long-range matches (NRL SA, Practical Precision Rifle)</li>
        <li>Managing administrative records for members — activity logging and digital compliance records in the portal</li>
        <li>Supporting members in lawful sport shooting, including club and range events (postal-style leagues, precision, practical)</li>
        <li>Registered with SAPS, affiliating with sport shooting clubs since 2015</li>
    </ul>

    <h2>Shooting Disciplines</h2>
    <p>NRAPA supports both <strong>IPSC</strong> (International Practical Shooting Confederation) and <strong>IDPA</strong> (International Defensive Pistol Association) formats. IPSC is competition-focused, emphasising speed, accuracy and power across varied scenarios. IDPA is more defensive-related, simulating real-world self-defence situations with concealment and cover.</p>

    <h2>Code of Conduct — Firearm Safety</h2>
    <h3>The Three Fundamental Rules</h3>
    <ol>
        <li><strong>ALWAYS</strong> keep gun unloaded until ready to use</li>
        <li><strong>ALWAYS</strong> keep finger off trigger until ready to shoot</li>
        <li><strong>ALWAYS</strong> keep gun pointed in safe direction</li>
    </ol>
    <h3>Six Additional Rules</h3>
    <ul class="checklist">
        <li>Never use alcohol or drugs before or while handling firearms</li>
        <li>Wear eye and ear protection when shooting</li>
        <li>Use only the correct ammunition for your firearm</li>
        <li>Be sure your firearm is safe to operate</li>
        <li>Know how to use your firearm safely</li>
        <li>Know your target and what is beyond it</li>
    </ul>

    <h2>Membership Categories</h2>
    <ul>
        <li><strong>Ordinary Membership</strong> — 18 years and older</li>
        <li><strong>Dedicated Membership</strong> — Must complete the dedicated course, maintain status, participate in a minimum of 2 activities annually; NRAPA must inform SAPS of non-compliance</li>
        <li><strong>Senior Membership</strong> — 61 years and older</li>
        <li><strong>Junior Membership</strong> — Under 18 years</li>
    </ul>

    <h2>Related</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.membership-benefits') }}">Membership benefits</a></li>
        <li><a href="{{ route('info.how-to-get-dedicated-status-south-africa') }}">How to get dedicated status</a></li>
        <li><a href="{{ route('register') }}">Register now</a></li>
    </ul>
@endsection
