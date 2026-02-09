@extends('emails.layout')

@section('title', 'NRAPA Membership Payment Instructions')
@section('heading', 'NRAPA Membership')
@section('subtitle', 'Payment Instructions')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">Thank you for registering with NRAPA. To activate your <strong>{{ $membership->type->name }}</strong>, please make an EFT payment using the details below:</p>

    {{-- Bank details --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #111827; margin: 0 0 12px 0; font-size: 16px;">Bank Account Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Bank:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-size: 14px;">{{ $bankAccount['bank_name'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Account Name:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-size: 14px;">{{ $bankAccount['account_name'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Account Number:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $bankAccount['account_number'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Branch Code:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $bankAccount['branch_code'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Account Type:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-size: 14px;">{{ $bankAccount['account_type'] ?: 'To be confirmed' }}</td>
            </tr>
        </table>
    </div>

    {{-- Amount --}}
    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Amount to Pay:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; font-size: 1.25em; color: #166534;">R{{ number_format($membership->amount_due, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Payment reference --}}
    <div class="bx-warning" style="background-color: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 0 0 20px 0; text-align: center;">
        <p style="margin: 0 0 10px 0; color: #92400e; font-weight: bold; font-size: 14px;">YOUR PAYMENT REFERENCE</p>
        <p class="ref-value" style="margin: 0; background-color: #ffffff; border: 2px dashed #f59e0b; border-radius: 6px; padding: 15px; font-family: 'Courier New', monospace; font-size: 24px; font-weight: bold; color: #111827; letter-spacing: 2px;">
            {{ $reference }}
        </p>
        <p style="margin: 10px 0 0 0; color: #92400e; font-size: 12px;">
            Use this exact reference when making your EFT payment
        </p>
    </div>

    {{-- Important notes --}}
    <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 15px; margin: 0 0 20px 0;">
        <p style="margin: 0 0 8px 0; color: #92400e;"><strong>Important:</strong></p>
        <ul style="color: #92400e; margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 4px;">Please use the exact reference shown above when making payment</li>
            <li style="margin-bottom: 4px;">Allow 1-3 business days for payment verification</li>
            <li>Your membership will be activated once payment is confirmed</li>
        </ul>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you have any questions, please don't hesitate to contact us.</p>

    <p class="tx" style="color: #374151; margin: 0;">Best regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} because you registered for a NRAPA membership.
@endsection
