@extends('layouts.guides')

@section('title', 'How to Request an Endorsement')
@section('description', 'How to submit an endorsement request on NRAPA Online for your Section 16 firearm licence applications.')
@section('heading', 'How to request an endorsement')
@section('breadcrumb', 'Request endorsement')

@section('content')
    <p>Follow these steps to submit an endorsement request.</p>

    <ol class="step-list">
        <li>
            <h3>Open the NRAPA website</h3>
            <p>Open your web browser and navigate to <a href="https://nrapa.ranyati.co.za">nrapa.ranyati.co.za</a>.</p>
            <img src="{{ asset('guide_images/request-endorsement/image009.png') }}" alt="NRAPA website home page">
        </li>
        <li>
            <h3>Log into your profile</h3>
            <p>In the top right-hand corner of the website, click <strong>Log In</strong>.</p>
        </li>
        <li>
            <h3>Enter your login details</h3>
            <p>On the login screen, enter your <strong>email address</strong> and <strong>password</strong>, then click <strong>Log In</strong> to continue.</p>
            <img src="{{ asset('guide_images/request-endorsement/image011.png') }}" alt="NRAPA login screen">
        </li>
        <li>
            <h3>Access your NRAPA Profile Dashboard</h3>
            <p>Once logged in, you'll land on your <strong>NRAPA Profile Dashboard</strong>. Menu options appear along the top of the page and in the left-hand side panel.</p>
            <img src="{{ asset('guide_images/request-endorsement/image013.png') }}" alt="NRAPA member dashboard">
        </li>
        <li>
            <h3>Submit an endorsement request</h3>
            <ol>
                <li>Click <strong>Endorsements</strong> in the left menu, or <strong>Dedicated Status</strong> in the top menu.</li>
                <li>Once the screen opens, go to the top right-hand corner.</li>
                <li>Click <strong>Request Endorsement</strong>.</li>
                <li>Complete all the relevant fields carefully.</li>
                <li>Upload all supporting documentation where required.</li>
                <li>Click <strong>Submit</strong> to send your endorsement request for processing.</li>
            </ol>
            <img src="{{ asset('guide_images/request-endorsement/image015.png') }}" alt="Dedicated Status page with the Request Endorsement button">
        </li>
    </ol>

    <h2>Important notes</h2>
    <p>Before submitting, please make sure that:</p>
    <ul class="checklist">
        <li>All information provided is accurate and complete.</li>
        <li>Supporting documents are clear and legible.</li>
        <li>Your membership and Dedicated Status (where applicable) are up to date.</li>
    </ul>
@endsection
