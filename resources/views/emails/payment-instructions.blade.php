@extends('emails.layout')

@section('title', 'Payment Instructions — NRAPA')
@section('heading', 'Almost There!')
@section('subtitle', 'Complete your payment to activate your membership')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        Thank you for registering with NRAPA. Your <strong>{{ $membership->type->name }}</strong> application is one step away from activation &mdash; just complete the EFT payment below.
    </p>

    {{-- Amount due --}}
    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 24px; margin: 0 0 24px 0; text-align: center;">
        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #166534; display: block; margin-bottom: 8px;">Amount to Pay</span>
        <span class="tx" style="font-size: 32px; font-weight: 700; color: #166534; display: block; line-height: 1;">R{{ number_format($membership->amount_due, 2) }}</span>
    </div>

    {{-- Payment reference --}}
    <div class="bx-warning" style="background-color: #fef3c7; border: 2px solid #f59e0b; border-radius: 10px; padding: 24px; margin: 0 0 24px 0; text-align: center;">
        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400e; display: block; margin-bottom: 10px;">Your Payment Reference</span>
        <p class="ref-value" style="margin: 0 0 10px 0; background-color: #ffffff; border: 2px dashed #f59e0b; border-radius: 6px; padding: 15px; font-family: 'Courier New', monospace; font-size: 24px; font-weight: 700; color: #111827; letter-spacing: 2px;">
            {{ $reference }}
        </p>
        <span class="tx" style="font-size: 12px; color: #92400e;">Use this exact reference when making your EFT payment</span>
    </div>

    {{-- Bank details --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">Bank Account Details</span>
            </td>
        </tr>
    </table>

    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Bank</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $bankAccount['bank_name'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Account Name</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $bankAccount['account_name'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Account Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $bankAccount['account_number'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Branch Code</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $bankAccount['branch_code'] ?: 'To be confirmed' }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Account Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $bankAccount['account_type'] ?: 'To be confirmed' }}</td>
            </tr>
        </table>
    </div>

    {{-- How it works --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">How It Works</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Make the EFT payment</strong> using the bank details and reference above.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Upload your proof of payment</strong> on your membership page for faster processing.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #059669; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px;">&#10003;</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Membership activated</strong> &mdash; once payment is confirmed (allow 1&ndash;3 business days).
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/membership" target="_blank"
                   style="display: inline-block; padding: 16px 48px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 16px;">
                    <span style="color: #ffffff;">Upload Proof of Payment</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Best regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} because you registered for a NRAPA membership.
@endsection
