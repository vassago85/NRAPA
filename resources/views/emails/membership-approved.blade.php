@extends('emails.layout')

@section('title', 'Membership Approved — NRAPA')
@section('heading', 'Congratulations!')
@section('subtitle', 'Your NRAPA membership is now active')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        We&rsquo;re thrilled to welcome you to the <strong>National Rifle &amp; Pistol Association of South Africa</strong>.
        Your <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> has been approved and is now active.
    </p>

    {{-- Membership card --}}
    <div class="bx-nrapa" style="background-color: #E8F1FB; border: 1px solid #0B4EA2; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #0B4EA2;">Your Membership</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #0B4EA2; font-size: 14px;">Member Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #0B4EA2; font-family: 'Courier New', monospace; font-size: 15px;">{{ $membership->membership_number }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #0B4EA2; font-size: 14px;">Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #0B4EA2; font-size: 14px;">{{ $membership->type?->name ?? 'Standard' }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #0B4EA2; font-size: 14px;">Activated</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #0B4EA2; font-size: 14px;">{{ now()->format('d F Y') }}</td>
            </tr>
            @if($membership->expires_at)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #0B4EA2; font-size: 14px;">Valid Until</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #0B4EA2; font-size: 14px;">{{ $membership->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx" style="padding: 5px 0; color: #0B4EA2; font-size: 14px;">Status</td>
                <td style="padding: 5px 0; text-align: right;">
                    <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">ACTIVE</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Digital membership card --}}
    <div class="bx-nrapa-card" style="background-color: #ffffff; border: 2px solid #F58220; border-radius: 10px; padding: 24px; margin: 0 0 28px 0; text-align: center;">
        <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#127380;</span>
        <span class="hd" style="font-size: 16px; font-weight: 700; color: #F58220; display: block; margin-bottom: 6px;">Your Digital Membership Card</span>
        <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 14px;">Your QR-scannable membership card is ready. Access it anytime from your phone or dashboard.</p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                    <a href="{{ $cardUrl }}" target="_blank"
                       style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                        <span style="color: #ffffff;">View My Card</span>
                    </a>
                </td>
            </tr>
        </table>
    </div>

    {{-- What's Next heading --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What&rsquo;s Next?</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #059669; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px;">&#10003;</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Welcome Letter</strong> &mdash; your welcome letter is ready under Certificates in your account.
            </td>
        </tr>
        @if($membership->type?->allows_dedicated_status)
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Knowledge Test</strong> &mdash; complete your dedicated status test to unlock endorsement requests.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Virtual Safe</strong> &mdash; register your firearms for licence renewal reminders and to link firearms to your Loading Bench data.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Activities</strong> &mdash; log your shooting activities to maintain your dedicated status.
            </td>
        </tr>
        @else
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Virtual Safe</strong> &mdash; register your firearms for licence renewal reminders.
            </td>
        </tr>
        @endif
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Welcome aboard!<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} because your NRAPA membership has been approved.
@endsection
