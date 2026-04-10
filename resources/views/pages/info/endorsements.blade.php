@extends('layouts.info')

@section('title', 'Firearm Endorsement Letters | NRAPA South Africa')
@section('description', 'How endorsement letters work for NRAPA members: lawful purposes, SAPS requirements, and applying through the NRAPA member portal—not legal advice.')
@section('heading', 'Endorsement letters')
@section('breadcrumb', 'Endorsements')

@section('content')
    <p>Endorsement letters are official NRAPA documents that may support certain firearm licence applications or administrative steps where an accredited association must confirm member status or discipline alignment. Availability, templates, and issuance rules are defined in the member portal and NRAPA policies.</p>

    <h2>How it works</h2>
    <ol class="step-list">
        <li>
            <strong>Log in to the member portal</strong>
            <p>Start endorsement requests from your dashboard after registration.</p>
        </li>
        <li>
            <strong>Submit your request</strong>
            <p>Select the endorsement type and provide the required details for your application.</p>
        </li>
        <li>
            <strong>Staff review</strong>
            <p>Submissions are reviewed against membership status, declared purpose, and internal compliance checks.</p>
        </li>
        <li>
            <strong>Issuance</strong>
            <p>Approved endorsement letters are generated with a verifiable reference code.</p>
        </li>
    </ol>

    <div class="info-card">
        <h4>Not legal advice</h4>
        <p>NRAPA cannot guarantee SAPS outcomes. Where you face refusals, delays, or complex legal questions, consult an attorney familiar with firearms law.</p>
    </div>

    <h2>Related services</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.membership-benefits') }}">Membership benefits</a></li>
        <li><a href="https://motivations.ranyati.co.za">Ranyati Motivations</a></li>
        <li><a href="https://ranyati.co.za">Ranyati Group</a></li>
    </ul>
@endsection
