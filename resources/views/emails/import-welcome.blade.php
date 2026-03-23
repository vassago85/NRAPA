@extends('emails.layout')

@section('title', 'Welcome to NRAPA')
@section('heading', 'Welcome to NRAPA!')
@section('subtitle', 'Your account has been created')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">You have been registered as a member of the National Rifle &amp; Pistol Association of South Africa (NRAPA). Your <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> is now active.</p>

    {{-- Membership details --}}
    <div class="bx-nrapa" style="background-color: #E8F1FB; border: 1px solid #0B4EA2; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #0B4EA2; margin: 0 0 12px 0; font-size: 16px;">Membership Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #0B4EA2; font-size: 14px;">Membership Number:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #0B4EA2; font-family: 'Courier New', monospace; font-size: 14px;">{{ $membership->membership_number }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #0B4EA2; font-size: 14px;">Membership Type:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #0B4EA2; font-size: 14px;">{{ $membership->type?->name ?? 'Standard' }}</td>
            </tr>
            @if($membership->expires_at)
            <tr>
                <td class="tx" style="padding: 6px 0; color: #0B4EA2; font-size: 14px;">Valid Until:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #0B4EA2; font-size: 14px;">{{ $membership->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Login credentials --}}
    <div class="bx-warning" style="background-color: #FFFBEB; border: 1px solid #D97706; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #92400E; margin: 0 0 8px 0; font-size: 16px;">Your Login Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #92400E; font-size: 14px;">Email:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #92400E; font-size: 14px;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #92400E; font-size: 14px;">Temporary Password:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #92400E; font-family: 'Courier New', monospace; font-size: 14px;">{{ $defaultPassword }}</td>
            </tr>
        </table>
        <p style="color: #92400E; margin: 12px 0 0 0; font-size: 13px;">Please change your password immediately after signing in.</p>
    </div>

    <h3 class="hd" style="color: #111827; margin: 0 0 12px 0; font-size: 16px;">Get Started</h3>
    <p class="tx" style="color: #374151; margin: 0 0 12px 0;">To complete your account setup, please:</p>
    <ol style="color: #374151; padding-left: 20px; margin: 0 0 20px 0;">
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Sign in</strong> using the login details above.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Change your password</strong> to something secure that only you know.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Upload your ID document</strong> &ndash; this is required before certificates can be issued.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Accept the Terms &amp; Conditions</strong> to complete your profile.</li>
    </ol>

    {{-- CTA Button --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 20px auto;" class="btn-primary">
        <tr>
            <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                <a href="{{ $loginUrl }}" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 15px;">
                    Sign In to My Account
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you have any questions or need assistance, please contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2;">info@nrapa.co.za</a>.</p>

    <p class="tx" style="color: #374151; margin: 0;">Welcome aboard,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $user->email }} because your membership was imported into the NRAPA platform.
@endsection
