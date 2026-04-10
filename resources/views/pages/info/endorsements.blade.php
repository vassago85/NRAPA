@extends('layouts.info')

@section('title', 'Firearm Endorsement Letters | NRAPA South Africa')
@section('description', 'How endorsement letters work for NRAPA members: lawful purposes, SAPS requirements, and applying through the NRAPA member portal—not legal advice.')
@section('heading', 'Endorsement letters')
@section('breadcrumb', 'Endorsements')

@section('content')
    <p>Endorsement letters are official NRAPA documents that may support certain firearm licence applications or administrative steps where an accredited association must confirm member status or discipline alignment. Availability, templates, and issuance rules are defined in the member portal and NRAPA policies.</p>

    <h2>Member portal</h2>
    <p>Logged-in members can start endorsement requests from the dashboard. Staff review submissions against membership status, declared purpose, and internal compliance checks. This public page is informational only; it does not replace portal workflows.</p>

    <h2>Not legal advice</h2>
    <p>NRAPA cannot guarantee SAPS outcomes. Where you face refusals, delays, or complex legal questions, consult an attorney familiar with firearms law.</p>

    <h2>Related services</h2>
    <p><a href="{{ route('info.membership-benefits') }}">Membership benefits</a> · <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> for motivations · <a href="https://ranyati.co.za">Ranyati Group</a></p>
@endsection
