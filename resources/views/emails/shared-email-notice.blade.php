@extends('emails.layout')

@section('title', 'NRAPA – Login Details for ' . $user->name)
@section('heading', 'Important: Your Login Details')
@section('subtitle', 'Multiple members registered with this email address')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
        Your <strong>{{ $membership->type?->name ?? 'NRAPA' }}</strong> membership has been migrated to our new digital platform.
    </p>

    {{-- Explanation --}}
    <div class="bx-info" style="background-color: #EEF2FF; border: 1px solid #6366F1; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #4338CA;">Why am I receiving this?</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 0; color: #4338CA; font-size: 14px; line-height: 1.6;">
                    Multiple members were registered under the email address <strong>{{ $originalEmail }}</strong>.
                    To ensure each member has their own unique account, your account has been set up to use your
                    <strong>phone number</strong> for signing in.
                </td>
            </tr>
        </table>
    </div>

    {{-- Login credentials --}}
    <div class="bx-warning" style="background-color: #FFFBEB; border: 1px solid #D97706; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400E;">Your Login Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Phone Number (your username)</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $user->phone }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Temporary Password</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $defaultPassword }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Member Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $membership->membership_number }}</td>
            </tr>
        </table>
    </div>

    {{-- Steps --}}
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
                <strong>Sign in</strong> with your <strong>phone number</strong> ({{ $user->phone }}) and the temporary password above.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Update your email address</strong> in your account settings to your own personal email. This will allow you to receive notifications and reset your password in future.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Change your password</strong> to something secure and personal.
            </td>
        </tr>
    </table>

    {{-- CTA Button --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ $loginUrl }}" target="_blank"
                   style="display: inline-block; padding: 16px 48px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 16px; letter-spacing: 0.3px; mso-padding-alt: 0; text-align: center;">
                    <!--[if mso]><i style="mso-font-width: -100%; mso-text-raise: 24pt;">&nbsp;</i><![endif]-->
                    <span style="color: #ffffff;">Sign In Now</span>
                    <!--[if mso]><i style="mso-font-width: -100%;">&nbsp;</i><![endif]-->
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Welcome aboard!<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    You received this email because your membership was migrated to the new NRAPA digital platform.
    This email was sent to {{ $originalEmail }} on behalf of {{ $user->name }}.
@endsection
