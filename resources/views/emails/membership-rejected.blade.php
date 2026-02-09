@extends('emails.layout')

@section('title', 'NRAPA Membership Application Update')
@section('heading', 'Application Update')
@section('subtitle', 'Your membership application could not be approved')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">Thank you for your interest in joining NRAPA. Unfortunately, your application for <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> could not be approved at this time.</p>

    {{-- Reason --}}
    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #991b1b; margin: 0 0 8px 0; font-size: 16px;">Reason</h3>
        <p style="color: #991b1b; margin: 0;">{{ $reason }}</p>
    </div>

    {{-- What you can do --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #1e40af; margin: 0 0 8px 0; font-size: 16px;">What You Can Do</h3>
        <ul style="color: #1e40af; margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 6px;">Review the reason above and address any issues</li>
            <li style="margin-bottom: 6px;">You may submit a new application once the issues have been resolved</li>
            <li>Contact us if you believe this was made in error</li>
        </ul>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you have any questions or need assistance, please don't hesitate to contact us.</p>

    <p class="tx" style="color: #374151; margin: 0;">Kind regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} regarding your NRAPA membership application.
@endsection
