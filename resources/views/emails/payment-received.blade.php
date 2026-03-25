@extends('emails.layout')

@section('title', 'Payment Received — NRAPA')
@section('heading', 'Payment Received!')
@section('subtitle', 'We have confirmed your payment')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        We have received and verified your payment. Your membership has been activated. Thank you!
    </p>

    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #166534;">Payment Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Membership Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ $membership->type?->name ?? 'Membership' }}</td>
            </tr>
            @if($membership->payment_reference)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Reference</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px; font-family: monospace;">{{ $membership->payment_reference }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Amount</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">R{{ number_format($membership->change_amount ?? $membership->amount_due, 2) }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Status</td>
                <td style="padding: 5px 0; text-align: right;">
                    <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">PAID</span>
                </td>
            </tr>
        </table>
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/membership" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">View My Membership</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} regarding your NRAPA membership payment.
@endsection
