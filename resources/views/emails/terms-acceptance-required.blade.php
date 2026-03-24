@extends('emails.layout')

@section('title', 'Action Required — NRAPA')
@section('heading', 'Action Required')
@section('subtitle', 'Please accept NRAPA Terms & Conditions')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        You must accept the <strong>NRAPA Membership Terms &amp; Conditions</strong> before your membership can be activated and before any certificates or letters can be issued.
    </p>

    {{-- Warning banner --}}
    <div class="bx-warning" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px 20px; margin: 0 0 28px 0; border-radius: 0 10px 10px 0;">
        <span style="font-size: 20px; vertical-align: middle; margin-right: 8px;">&#9888;&#65039;</span>
        <span class="tx" style="color: #92400e; font-size: 14px; font-weight: 700; vertical-align: middle;">Your membership cannot become &ldquo;Member in Good Standing&rdquo; until you accept the Terms &amp; Conditions.</span>
    </div>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ $acceptUrl }}" target="_blank"
                   style="display: inline-block; padding: 16px 48px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 16px;">
                    <span style="color: #ffffff;">Accept Terms &amp; Conditions</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; font-size: 13px; margin: 0 0 28px 0; text-align: center;">
        If the button doesn&rsquo;t work, copy and paste this link into your browser:<br>
        <a href="{{ $acceptUrl }}" style="color: #0B4EA2; text-decoration: underline; font-size: 12px; word-break: break-all;">{{ $acceptUrl }}</a>
    </p>

    {{-- What happens after --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What Happens After Acceptance?</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 0 24px 0;">
        <tr>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 14px; vertical-align: top;">
                <span style="font-size: 20px; display: block; margin-bottom: 4px;">&#9989;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block;">Membership Activated</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5;">Subject to payment/approval</span>
            </td>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 14px; vertical-align: top;">
                <span style="font-size: 20px; display: block; margin-bottom: 4px;">&#128220;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block;">Certificates Issued</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5;">Download anytime</span>
            </td>
        </tr>
        <tr>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 14px; vertical-align: top;">
                <span style="font-size: 20px; display: block; margin-bottom: 4px;">&#128221;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block;">Endorsement Requests</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5;">Submit online</span>
            </td>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 14px; vertical-align: top;">
                <span style="font-size: 20px; display: block; margin-bottom: 4px;">&#127380;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block;">Full Portal Access</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5;">All member features unlocked</span>
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
    This email was sent to {{ $user->email }} regarding your NRAPA membership. &middot; nrapa.ranyati.co.za
@endsection
