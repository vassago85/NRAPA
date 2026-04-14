@extends('emails.layout')

@section('title', 'Welcome to NRAPA Digital')
@section('heading', 'Welcome to NRAPA Digital!')
@section('subtitle', 'Your membership has been migrated to our new platform')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
        Great news! Your <strong>{{ $membership->type?->name ?? 'NRAPA' }}</strong> membership has been migrated to our
        <strong>brand-new digital platform</strong>. Everything you need as a member is now available online &mdash;
        faster, easier, and always at your fingertips.
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

    {{-- Features heading --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What&rsquo;s New For You</span>
            </td>
        </tr>
    </table>

    {{-- Features grid (2x2 table) --}}
    <table role="presentation" style="width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 0 24px 0;">
        <tr>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 16px; vertical-align: top;">
                <span style="font-size: 22px; display: block; margin-bottom: 6px;">&#127380;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block; margin-bottom: 4px;">Digital Membership Card</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5; line-height: 1.4;">QR-scannable card on your phone &mdash; no more paper cards.</span>
            </td>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 16px; vertical-align: top;">
                <span style="font-size: 22px; display: block; margin-bottom: 6px;">&#128220;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block; margin-bottom: 4px;">Instant Certificates</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5; line-height: 1.4;">Good Standing, Dedicated Status &amp; more &mdash; download anytime.</span>
            </td>
        </tr>
        <tr>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 16px; vertical-align: top;">
                <span style="font-size: 22px; display: block; margin-bottom: 6px;">&#9989;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block; margin-bottom: 4px;">Endorsement Requests</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5; line-height: 1.4;">Submit endorsement requests online and track their progress.</span>
            </td>
            <td class="bx-nrapa" style="width: 50%; background-color: #E8F1FB; border: 1px solid #c7d9f0; border-radius: 8px; padding: 16px; vertical-align: top;">
                <span style="font-size: 22px; display: block; margin-bottom: 6px;">&#127919;</span>
                <span class="tx" style="font-weight: 700; font-size: 13px; color: #0B4EA2; display: block; margin-bottom: 4px;">Activity Tracking</span>
                <span class="tx" style="font-size: 12px; color: #4a6fa5; line-height: 1.4;">Log shooting activities and stay compliant with ease.</span>
            </td>
        </tr>
    </table>

    {{-- Login credentials --}}
    <div class="bx-warning" style="background-color: #FFFBEB; border: 1px solid #D97706; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400E;">Your Login Details</span>
                </td>
            </tr>
            @if($user->hasPlaceholderEmail() && $user->phone)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Phone Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $user->phone }}</td>
            </tr>
            @else
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Email</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-size: 14px;">{{ $user->email }}</td>
            </tr>
            @if($user->phone)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Phone Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $user->phone }}</td>
            </tr>
            @endif
            @endif
            <tr>
                <td class="tx" style="padding: 5px 0; color: #92400E; font-size: 14px;">Temporary Password</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #92400E; font-family: 'Courier New', monospace; font-size: 15px;">{{ $defaultPassword }}</td>
            </tr>
        </table>
        <p class="tx" style="color: #92400E; margin: 14px 0 0 0; font-size: 12px; font-style: italic;">
            @if($user->hasPlaceholderEmail())
                Sign in using your <strong>phone number</strong> and the password above. Please change your password immediately after your first sign-in.
            @else
                You can sign in using your <strong>email or phone number</strong>. Please change your password immediately after your first sign-in.
            @endif
        </p>
    </div>

    {{-- Getting started steps --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">Get Started in 4 Easy Steps</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Sign in</strong> with your {{ $user->hasPlaceholderEmail() ? 'phone number' : 'email or phone number' }} and password above.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Change your password</strong> to something secure.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Upload your ID document</strong> &mdash; required before certificates can be issued.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">4</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Accept Terms &amp; Conditions</strong> to complete your profile.
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
                    <span style="color: #ffffff;">Explore My Account</span>
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

    {{-- Account deletion notice --}}
    <div style="margin: 28px 0 0 0; border-top: 1px solid #e5e7eb; padding-top: 20px;">
        <p class="tx" style="color: #6b7280; margin: 0; font-size: 12px; text-align: center; line-height: 1.6;">
            If you no longer wish to be a member, you can delete your account at any time from your profile settings after signing in. All your personal data will be permanently removed.
        </p>
    </div>
@endsection

@section('footer')
    You received this email because your membership was migrated to the new NRAPA digital platform.
@endsection
