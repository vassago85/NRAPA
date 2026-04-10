@extends('layouts.info')

@section('title', 'How to Get Dedicated Status in South Africa | NRAPA')
@section('description', 'A practical overview of dedicated sport shooter or dedicated hunter status: accredited association membership, competency, SAPS applications, and ongoing compliance—NRAPA under Ranyati Group.')
@section('heading', 'How to get dedicated status in South Africa')
@section('breadcrumb', 'How to get dedicated status')

@section('content')
    <p>Dedicated status is not a separate "permit" you buy off the shelf. It is a regulatory concept tied to your membership and activity with a SAPS-accredited association, your competency certificates, safe storage, and the merits of each licence application or renewal decided by SAPS.</p>

    <ol class="step-list">
        <li>
            <strong>Choose the correct pathway</strong>
            <p>Decide whether you are pursuing <a href="{{ route('info.dedicated-sport-shooter-south-africa') }}">dedicated sport shooter</a> or <a href="{{ route('info.dedicated-hunter-south-africa') }}">dedicated hunter</a> status (some members may need clarity from SAPS or professional advice if their use spans both).</p>
        </li>
        <li>
            <strong>Join an accredited association</strong>
            <p>NRAPA is accredited for dedicated allocations. <a href="{{ route('register') }}">Register</a>, select the appropriate membership, and complete verification steps described in the portal.</p>
        </li>
        <li>
            <strong>Meet competency and statutory requirements</strong>
            <p>Obtain and maintain the relevant SAPS competency certificates, renew on time, and ensure your safe meets applicable standards.</p>
        </li>
        <li>
            <strong>Prepare your SAPS paperwork</strong>
            <p>Applications and renewals require motivations and supporting documents. Many members use <a href="https://motivations.ranyati.co.za">Ranyati Motivations</a> for professionally structured motivations alongside NRAPA membership evidence.</p>
        </li>
        <li>
            <strong>Stay active and documented</strong>
            <p>Dedicated status typically depends on continued lawful activity. Use the member portal to log qualifying activities — see <a href="{{ route('info.dedicated-procedure') }}">dedicated procedure</a> for operational detail.</p>
        </li>
    </ol>

    <h2>Related</h2>
    <ul class="link-list">
        <li><a href="{{ route('info.dedicated-procedure') }}">Dedicated procedure (step-by-step)</a></li>
        <li><a href="{{ route('info.minimum-requirements') }}">Minimum requirements</a></li>
        <li><a href="{{ route('register') }}">Register for NRAPA</a></li>
        <li><a href="https://ranyati.co.za">Ranyati Group</a></li>
    </ul>
@endsection
