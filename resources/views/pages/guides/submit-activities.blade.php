@extends('layouts.guides')

@section('title', 'How to Submit Activities')
@section('description', 'How to log a hunting or sport-shooting activity on NRAPA Online to support your dedicated status.')
@section('heading', 'How to submit activities')
@section('breadcrumb', 'Submit activities')

@section('content')
    <p>Follow these steps to log a hunting or sport-shooting activity for your dedicated status.</p>

    <ol class="step-list">
        <li>
            <h3>Open the NRAPA website</h3>
            <p>Open your web browser and navigate to <a href="https://nrapa.ranyati.co.za">nrapa.ranyati.co.za</a>.</p>
            <img src="{{ asset('guide_images/submit-activities/image002.jpg') }}" alt="NRAPA website home page">
        </li>
        <li>
            <h3>Log into your profile</h3>
            <p>In the top right-hand corner of the website, click <strong>Log In</strong>.</p>
        </li>
        <li>
            <h3>Enter your login details</h3>
            <p>On the login screen, enter your <strong>email address</strong> and <strong>password</strong>, then click <strong>Log In</strong> to continue.</p>
            <img src="{{ asset('guide_images/submit-activities/image003.jpg') }}" alt="NRAPA login screen">
        </li>
        <li>
            <h3>Your NRAPA Profile Dashboard</h3>
            <p>Once logged in, you'll land on your <strong>NRAPA Profile Dashboard</strong>. Menu options appear along the top of the page and in the left-hand side panel — these let you manage your profile and activities.</p>
            <img src="{{ asset('guide_images/submit-activities/image004.jpg') }}" alt="NRAPA member dashboard">
            <p>The top navigation menu:</p>
            <img src="{{ asset('guide_images/submit-activities/image005.png') }}" alt="Top navigation menu">
            <p>The left-hand member menu:</p>
            <img src="{{ asset('guide_images/submit-activities/image006.png') }}" alt="Left-hand member menu">
        </li>
        <li>
            <h3>Upload a shooting activity</h3>
            <ol>
                <li>Click <strong>Activities</strong> from the menu.</li>
                <li>Once the Activities screen opens, go to the top right-hand corner.</li>
                <li>Click <strong>Submit Activity</strong>.</li>
                <li>Complete all the relevant fields and submit your activity.</li>
            </ol>
            <img src="{{ asset('guide_images/submit-activities/image008.png') }}" alt="Activities page with the Submit Activity button">
        </li>
    </ol>

    <div class="info-card">
        <h4>Tip</h4>
        <p>Be sure to record the calibre used. If your firearm in the Virtual Safe doesn't have a calibre saved, you'll be asked to choose one when submitting — this keeps your activity records complete for dedicated-status purposes.</p>
    </div>
@endsection
