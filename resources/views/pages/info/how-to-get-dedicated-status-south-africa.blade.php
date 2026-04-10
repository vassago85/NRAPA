@extends('layouts.info')

@section('title', 'How to Get Dedicated Status in South Africa | NRAPA')
@section('description', 'A practical overview of dedicated sport shooter or dedicated hunter status: accredited association membership, competency, SAPS applications, and ongoing compliance—NRAPA under Ranyati Group.')
@section('heading', 'How to get dedicated status in South Africa')
@section('breadcrumb', 'How to get dedicated status')

@section('content')
    <p>Dedicated status is not a separate “permit” you buy off the shelf. It is a regulatory concept tied to your membership and activity with a SAPS-accredited association, your competency certificates, safe storage, and the merits of each licence application or renewal decided by SAPS.</p>

    <h2>1. Choose the correct pathway</h2>
    <p>Decide whether you are pursuing <a href="{{ route('info.dedicated-sport-shooter-south-africa') }}">dedicated sport shooter</a> or <a href="{{ route('info.dedicated-hunter-south-africa') }}">dedicated hunter</a> status (some members may need clarity from SAPS or professional advice if their use spans both).</p>

    <h2>2. Join an accredited association</h2>
    <p>NRAPA is accredited for dedicated allocations. <a href="{{ route('register') }}">Register</a>, select the appropriate membership, and complete verification steps described in the portal.</p>

    <h2>3. Meet competency and statutory requirements</h2>
    <p>Obtain and maintain the relevant SAPS competency certificates, renew on time, and ensure your safe meets applicable standards.</p>

    <h2>4. Prepare your SAPS paperwork</h2>
    <p>Applications and renewals require motivations and supporting documents. Many members use <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> for professionally structured motivations alongside NRAPA membership evidence.</p>

    <h2>5. Stay active and documented</h2>
    <p>Dedicated status typically depends on continued lawful activity. Use NRAPA’s tools to log events, targets, and scores as required—see <a href="{{ route('info.dedicated-procedure') }}">dedicated procedure</a> for operational detail.</p>

    <p>Parent brand: <a href="https://ranyati.co.za">Ranyati Group</a>.</p>
@endsection
