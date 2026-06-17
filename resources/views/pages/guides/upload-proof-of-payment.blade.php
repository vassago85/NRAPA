@extends('layouts.guides')

@section('title', 'How to Upload Proof of Payment')
@section('description', 'How to upload your Proof of Payment (POP) for your NRAPA membership so your membership can be activated.')
@section('heading', 'How to upload your proof of payment')
@section('breadcrumb', 'Upload proof of payment')

@section('content')
    <p>These steps explain how to upload your Proof of Payment (POP) for your NRAPA membership.</p>

    <ol class="step-list">
        <li>
            <h3>Open the payment details from your Dashboard</h3>
            <p>After logging into your profile, select <strong>Renew Your Membership</strong>. You'll be redirected back to your <strong>Dashboard</strong>, where a yellow payment notification block appears. Click <strong>View Full Details</strong> to continue.</p>
            <img src="{{ asset('guide_images/payment-proof-of-payment/image001.png') }}" alt="Payment awaiting confirmation block on the dashboard">
        </li>
        <li>
            <h3>Upload your proof of payment</h3>
            <p>On the next page you'll see the payment details block. Use the <strong>Upload Proof of Payment</strong> area to add your POP — drop a file or click to browse (JPG, PNG, or PDF, max 5&nbsp;MB).</p>
            <img src="{{ asset('guide_images/payment-proof-of-payment/image002.png') }}" alt="Payment details and Upload Proof of Payment area">
        </li>
        <li>
            <h3>Submission &amp; verification</h3>
            <p>Once your POP has uploaded successfully, it is submitted for verification and processing. Your membership will be activated once payment is confirmed (1–3 business days).</p>
        </li>
    </ol>

    <div class="info-card">
        <h4>Having trouble?</h4>
        <p>If you encounter any issues or receive an error message during the upload process, please let us know via the <a href="{{ route('messages.index') }}">Messages</a> section or contact NRAPA support.</p>
    </div>
@endsection
