@extends('emails.layout')

@section('title', $isReset ? 'Reset Your NRAPA Password' : 'Set Your NRAPA Password')
@section('heading', $isReset ? 'Reset Your Password' : 'Set Your Password')
@section('subtitle', $isReset ? 'Use the secure link below to choose a new password' : 'One quick step to finish setting up your account')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
        @if($isReset)
            We received a request to reset the password for your NRAPA account. Click the button below to choose a new password.
        @else
            Welcome to NRAPA! To activate your account, please set your password using the secure button below.
        @endif
    </p>

    {{-- Single-use notice --}}
    <div class="bx-warning" style="background-color: #FFFBEB; border: 1px solid #D97706; border-radius: 10px; padding: 18px 20px; margin: 0 0 24px 0;">
        <p class="tx" style="color: #92400E; margin: 0; font-size: 14px; line-height: 1.6;">
            <strong>Important:</strong> This is a <strong>single-use link</strong> that only lets you
            {{ $isReset ? 'reset' : 'set' }} your password. It expires in <strong>24 hours</strong> and stops working
            once you have {{ $isReset ? 'reset' : 'set' }} your password.
            <br><br>
            After that, <strong>do not use this email to sign in</strong> &mdash; simply go to the
            login page and sign in with your {{ $user->hasPlaceholderEmail() ? 'phone number' : 'email or phone number' }}
            and your new password.
        </p>
    </div>

    {{-- CTA: Set / Reset password --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 16px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ $setPasswordUrl }}" target="_blank"
                   style="display: inline-block; padding: 16px 48px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 16px; letter-spacing: 0.3px; text-align: center;">
                    <span style="color: #ffffff;">{{ $isReset ? 'Reset My Password' : 'Set My Password' }}</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 28px 0; font-size: 12px; text-align: center;">
        This link expires in 24 hours and can only be used once.
    </p>

    {{-- Login link for after they've set it --}}
    <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin: 0 0 8px 0;">
        <p class="tx" style="color: #374151; margin: 0 0 12px 0; font-size: 14px; text-align: center;">
            Already set your password? Sign in here:
        </p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 8px auto;">
            <tr>
                <td style="border-radius: 8px; text-align: center; border: 1px solid #0B4EA2;">
                    <a href="{{ $loginUrl }}" target="_blank"
                       style="display: inline-block; padding: 12px 36px; border-radius: 8px; color: #0B4EA2; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px; text-align: center;">
                        <span style="color: #0B4EA2;">Go to Login</span>
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p class="tx" style="color: #6b7280; margin: 24px 0 0 0; font-size: 13px;">
        If you didn’t request this, you can safely ignore this email — your account stays secure.
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>.
    </p>

    <p class="tx" style="color: #374151; margin: 20px 0 0 0; font-size: 15px;">
        Warm regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $user->email }} because a password {{ $isReset ? 'reset' : 'setup' }} was requested for your NRAPA account.
@endsection
