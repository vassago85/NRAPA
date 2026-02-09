<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRAPA Club Membership Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ config('app.url') }}/nrapa-logo.png" alt="NRAPA" style="width: 80px; height: 80px; object-fit: contain;" />
        <h1 style="color: #1f2937; margin-top: 15px; margin-bottom: 5px;">You're Invited!</h1>
        <p style="color: #6b7280; margin: 0;">Join NRAPA via {{ $club->name }}</p>
    </div>

    <p>Hello,</p>

    <p>You have been invited to join the <strong>National Rifle &amp; Pistol Association (NRAPA)</strong> as a member of <strong>{{ $club->name }}</strong>.</p>

    <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="color: #1e40af; margin-top: 0; margin-bottom: 15px;">Membership Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #1e40af;">Club:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #1e40af;">{{ $club->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #1e40af;">Dedicated Status:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #1e40af;">{{ $club->dedicated_type_label }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #1e40af;">Sign-up Fee:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #1e40af;">R{{ number_format($club->initial_fee, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #1e40af;">Annual Renewal:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #1e40af;">R{{ number_format($club->renewal_fee, 2) }}/year</td>
            </tr>
        </table>
    </div>

    @if($club->requires_competency || $club->required_activities_per_year > 0)
    <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <h4 style="color: #92400e; margin-top: 0; margin-bottom: 10px;">Requirements</h4>
        <ul style="color: #92400e; padding-left: 20px; margin: 0;">
            @if($club->requires_competency)
            <li style="margin-bottom: 5px;">Upload your SAPS Firearm Competency Certificate</li>
            @endif
            @if($club->required_activities_per_year > 0)
            <li style="margin-bottom: 5px;">Log {{ $club->required_activities_per_year }} activities per year (match results with your name)</li>
            @endif
            <li>All club applications require manual admin approval</li>
        </ul>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ $inviteUrl }}" target="_blank"
                       style="display: inline-block; padding: 14px 35px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 16px;">
                        Accept Invitation
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p style="color: #6b7280; font-size: 14px;">
        This invitation expires on <strong>{{ $invite->expires_at->format('d F Y') }}</strong>. If you don't have an NRAPA account yet, you'll need to register first and then use this link to complete your club membership application.
    </p>

    <p>Kind regards,<br><strong>NRAPA Team</strong></p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        This email was sent to {{ $invite->email }} because an NRAPA administrator invited you to join via {{ $club->name }}.<br>
        If you did not expect this invitation, you can safely ignore this email.<br>
        &copy; {{ date('Y') }} National Rifle &amp; Pistol Association of South Africa. All rights reserved.
    </p>
</body>
</html>
