@extends('emails.layout')

@php $user = $endorsement->user; @endphp

@section('title', 'Endorsement Update — NRAPA')
@section('heading', 'Endorsement Request Update')
@section('subtitle', 'Your endorsement request could not be approved')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        Unfortunately, your endorsement request for
        <strong>{{ $endorsement->request_type_label }}</strong> could not be approved at this time.
    </p>

    {{-- Reason --}}
    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #991b1b;">Reason</span>
                </td>
            </tr>
        </table>
        <p class="tx" style="color: #991b1b; margin: 0; font-size: 14px; line-height: 1.6;">{{ $reason }}</p>
    </div>

    {{-- Request details --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #374151;">Request Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $endorsement->request_type_label }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Purpose</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $endorsement->purpose_label }}</td>
            </tr>
            @if($endorsement->firearm)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Firearm</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ trim(($endorsement->firearm->make ?? '') . ' ' . ($endorsement->firearm->model ?? '')) ?: 'N/A' }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- What to do --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What You Can Do</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Review the reason</strong> above and address any issues mentioned.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Submit a new request</strong> once you&rsquo;ve resolved the issues.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Contact us</strong> if you believe this was made in error.
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/endorsements" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">View My Endorsements</span>
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
    This email was sent to {{ $user->email }} regarding your NRAPA endorsement request.
@endsection
