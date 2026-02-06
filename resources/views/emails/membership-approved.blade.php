<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to NRAPA</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ config('app.url') }}/nrapa-logo.png" alt="NRAPA" style="width: 80px; height: 80px; object-fit: contain;" />
        <h1 style="color: #1f2937; margin-top: 15px; margin-bottom: 5px;">Welcome to NRAPA!</h1>
        <p style="color: #6b7280; margin: 0;">Your membership is now active</p>
    </div>

    <p>Dear {{ $membership->user->name }},</p>

    <p>We are pleased to confirm that your <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> has been approved and is now active.</p>

    <div style="background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="color: #166534; margin-top: 0; margin-bottom: 15px;">Membership Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #166534;">Membership Number:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #166534; font-family: monospace;">{{ $membership->membership_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #166534;">Membership Type:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #166534;">{{ $membership->type?->name ?? 'Standard' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #166534;">Activated:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #166534;">{{ now()->format('d F Y') }}</td>
            </tr>
            @if($membership->expires_at)
            <tr>
                <td style="padding: 8px 0; color: #166534;">Valid Until:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #166534;">{{ $membership->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;">
        <h3 style="color: #1e40af; margin-top: 0; margin-bottom: 10px;">Your Digital Membership Card</h3>
        <p style="color: #1e40af; margin: 0 0 15px 0;">Your membership card is now available in your account. You can access it anytime from your phone.</p>
        <!-- Bulletproof button (solid background for maximum email client support) -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ $cardUrl }}" target="_blank"
                       style="display: inline-block; padding: 12px 30px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif;">
                        View My Card
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <h3 style="color: #1f2937;">What's Next?</h3>
    <ul style="color: #4b5563; padding-left: 20px;">
        <li style="margin-bottom: 8px;"><strong>Welcome Letter</strong> – Your welcome letter has been issued and is available under Certificates in your account.</li>
        <li style="margin-bottom: 8px;"><strong>Knowledge Test</strong> – Complete your dedicated status knowledge test to unlock endorsement requests.</li>
        <li style="margin-bottom: 8px;"><strong>Virtual Safe</strong> – Register your firearms for license renewal reminders and to link firearms to your Virtual Loading Bench data.</li>
        <li style="margin-bottom: 8px;"><strong>Activities</strong> – Log your shooting activities to maintain your dedicated status.</li>
    </ul>

    <p>If you have any questions, please don't hesitate to contact us.</p>

    <p>Welcome aboard,<br><strong>NRAPA Team</strong></p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        This email was sent to {{ $membership->user->email }} because your NRAPA membership has been approved.<br>
        &copy; {{ date('Y') }} National Rifle &amp; Pistol Association of South Africa. All rights reserved.
    </p>
</body>
</html>
