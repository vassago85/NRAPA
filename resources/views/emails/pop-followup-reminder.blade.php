@extends('emails.layout')

@section('title', 'Proof of Payment Outstanding — NRAPA')
@section('heading', 'Proof of Payment Outstanding')
@section('subtitle', 'Your membership is waiting for payment confirmation')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        We previously notified you that your <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> requires proof of payment before it can be approved. We haven't received this yet.
    </p>

    @if($membership->approval_revoked_reason)
    <div class="bx-danger" style="background-color: #fffbeb; border: 1px solid #fbbf24; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400e;">Original Message from Admin</span>
                </td>
            </tr>
        </table>
        <p class="tx" style="color: #78350f; margin: 0; font-size: 14px; line-height: 1.6;">{{ $membership->approval_revoked_reason }}</p>
    </div>
    @endif

    {{-- What you need to do --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What You Need To Do</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Click the button below</strong> to go to your membership page where you can upload your proof of payment.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                Upload a clear photo or PDF of your <strong>proof of payment</strong> (bank transfer confirmation, deposit slip, or EFT receipt).
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                Once uploaded, an administrator will review and <strong>approve your membership</strong>.
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 16px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/membership" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">Upload Proof of Payment</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 24px 0; font-size: 13px; text-align: center;">
        This link will take you to your membership page where you can upload your proof of payment.
    </p>

    {{-- Banking details reminder --}}
    <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #0369a1;">Payment Reference</span>
                </td>
            </tr>
        </table>
        <p class="tx" style="color: #0c4a6e; margin: 0; font-size: 14px; line-height: 1.6;">
            Please use your membership number <strong>{{ $membership->membership_number }}</strong> as your payment reference so we can match your payment.
        </p>
    </div>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This is an automated follow-up sent to {{ $membership->user->email }} because proof of payment for your NRAPA membership has not been received.
@endsection
