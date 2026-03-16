@extends('emails.layout')

@section('title', 'Action Required: Accept Terms & Conditions')
@section('heading', 'Action Required')
@section('subtitle', 'Please accept NRAPA Terms & Conditions')

@section('content')
    <h2 class="hd" style="color: #111827; margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">Hello {{ $user->name }},</h2>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">
        You must accept the <strong>NRAPA Membership Terms &amp; Conditions</strong> before your membership can be activated and before any certificates or letters can be issued.
    </p>

    {{-- Warning --}}
    <div class="bx-warning" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 0 0 24px 0; border-radius: 0 8px 8px 0;">
        <p style="margin: 0; color: #92400e; font-size: 14px; font-weight: 600;">
            &#9888;&#65039; Your membership status cannot become "Member in Good Standing" until you accept the Terms &amp; Conditions.
        </p>
    </div>

    {{-- CTA button --}}
    <div style="text-align: center; margin: 0 0 24px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ $acceptUrl }}" target="_blank"
                       style="display: inline-block; padding: 14px 28px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: 600; font-family: Arial, sans-serif; font-size: 16px;">
                        Accept Terms &amp; Conditions
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p class="tx-muted" style="color: #6b7280; font-size: 14px; margin: 0 0 20px 0;">
        If the button above doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $acceptUrl }}" style="color: #0B4EA2; text-decoration: underline;">{{ $acceptUrl }}</a>
    </p>

    <hr class="divider" style="border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p class="tx" style="color: #374151; margin: 0 0 8px 0; font-size: 14px;">
        <strong>What happens after acceptance?</strong>
    </p>
    <ul style="margin: 0 0 20px 0; padding-left: 20px; color: #6b7280; font-size: 14px; line-height: 1.8;">
        <li class="tx-muted" style="color: #6b7280;">Your membership can be activated (subject to payment/approval)</li>
        <li class="tx-muted" style="color: #6b7280;">You can request endorsement letters</li>
        <li class="tx-muted" style="color: #6b7280;">Certificates can be issued to you</li>
        <li class="tx-muted" style="color: #6b7280;">You'll have full access to the member portal</li>
    </ul>

    <p class="tx-muted" style="color: #6b7280; font-size: 14px; margin: 0 0 20px 0;">
        If you have any questions, please contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 0;">Best regards,<br><strong>NRAPA Administration</strong></p>
@endsection

@section('footer')
    National Rifle &amp; Pistol Association of South Africa (NRAPA)<br>
    nrapa.ranyati.co.za
@endsection
