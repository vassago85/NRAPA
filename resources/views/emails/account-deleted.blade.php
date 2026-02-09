@extends('emails.layout')

@section('title', 'NRAPA Account Deleted')
@section('heading', 'NRAPA')
@section('subtitle', 'Account Notification')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $userName }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">We regret to inform you that your NRAPA account associated with the email address <strong>{{ $userEmail }}</strong> has been deleted.</p>

    {{-- Reason --}}
    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #991b1b; margin: 0 0 8px 0; font-size: 16px;">Reason for Deletion</h3>
        <p style="color: #7f1d1d; margin: 0;">{{ $reason }}</p>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">This action was taken by an administrator ({{ $deletedBy }}) on {{ now()->format('d F Y') }} at {{ now()->format('H:i') }}.</p>

    {{-- What this means --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #111827; margin: 0 0 12px 0; font-size: 16px;">What This Means</h3>
        <ul style="color: #4b5563; margin: 0; padding-left: 20px;">
            <li class="tx" style="margin-bottom: 6px; color: #4b5563;">You will no longer be able to log in to the NRAPA platform</li>
            <li class="tx" style="margin-bottom: 6px; color: #4b5563;">Your membership and associated benefits have been cancelled</li>
            <li class="tx" style="color: #4b5563;">Any pending endorsement requests have been cancelled</li>
        </ul>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you believe this action was taken in error, or if you have any questions, please contact us for further assistance.</p>

    <p class="tx" style="color: #374151; margin: 0;">Best regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $userEmail }} regarding your NRAPA account.
@endsection
