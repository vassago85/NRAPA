@extends('layouts.guides')

@section('title', 'How to Sign Up')
@section('description', 'How to create your NRAPA account: open the website, register your details, choose a strong password, and verify your email address.')
@section('heading', 'How to sign up')
@section('breadcrumb', 'How to sign up')

@section('content')
    <p>Creating an NRAPA account takes a couple of minutes. You'll need your name, a valid email address, and a South African mobile number.</p>

    <ol class="step-list">
        <li>
            <h3>Open the NRAPA website</h3>
            <p>Go to your web browser and navigate to <a href="https://nrapa.ranyati.co.za">nrapa.ranyati.co.za</a>.</p>
            <img src="{{ asset('guide_images/submit-activities/image002.jpg') }}" alt="NRAPA website home page">
        </li>
        <li>
            <h3>Click “Register”</h3>
            <p>In the top right-hand corner of the website, click <strong>Register</strong> (or <strong>Become a Member</strong>) to open the sign-up form.</p>
        </li>
        <li>
            <h3>Enter your details</h3>
            <p>Complete the registration form:</p>
            <ul class="checklist">
                <li><strong>Full name</strong> — your name as it should appear on your membership.</li>
                <li><strong>Email address</strong> — a valid address you can access (used to log in and for verification).</li>
                <li><strong>Phone number</strong> — your South African mobile number (10 digits, starting with 0).</li>
            </ul>
        </li>
        <li>
            <h3>Choose a strong password</h3>
            <p>Enter and confirm a password that meets all the requirements:</p>
            <ul class="checklist">
                <li>At least 12 characters</li>
                <li>Upper and lowercase letters</li>
                <li>At least one number</li>
                <li>At least one symbol (e.g. ! @ # $ % ^ &amp; *)</li>
            </ul>
            <p>Then click <strong>Create account</strong>.</p>
        </li>
        <li>
            <h3>Verify your email address</h3>
            <p>We'll send a verification link to the email address you registered with. Open that email and click the link to confirm your address — you can do this on any device. Once verified, you can log in and continue setting up your membership.</p>
            <div class="info-card">
                <h4>Didn't get the email?</h4>
                <p>Check your spam or junk folder. If it still hasn't arrived, you can request a new verification link from the prompt shown after logging in.</p>
            </div>
        </li>
    </ol>

    <h2>What's next?</h2>
    <p>After signing up and verifying your email, the next steps are choosing your membership package and uploading your proof of payment. See the <a href="{{ route('guides.upload-proof-of-payment') }}">Upload proof of payment</a> guide once you're logged in.</p>
@endsection
