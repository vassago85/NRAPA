<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRAPA Account Deleted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="display: inline-block; background: linear-gradient(135deg, #10b981, #047857); width: 60px; height: 60px; border-radius: 12px; line-height: 60px;">
            <span style="color: white; font-size: 30px; font-weight: bold;">N</span>
        </div>
        <h1 style="color: #1f2937; margin-top: 15px; margin-bottom: 5px;">NRAPA</h1>
        <p style="color: #6b7280; margin: 0;">Account Notification</p>
    </div>

    <p>Dear {{ $userName }},</p>

    <p>We regret to inform you that your NRAPA account associated with the email address <strong>{{ $userEmail }}</strong> has been deleted.</p>

    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="color: #991b1b; margin-top: 0; margin-bottom: 10px;">Reason for Deletion</h3>
        <p style="color: #7f1d1d; margin: 0;">{{ $reason }}</p>
    </div>

    <p>This action was taken by an administrator ({{ $deletedBy }}) on {{ now()->format('d F Y') }} at {{ now()->format('H:i') }}.</p>

    <div style="background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="color: #1f2937; margin-top: 0; margin-bottom: 15px;">What This Means</h3>
        <ul style="color: #4b5563; margin: 0; padding-left: 20px;">
            <li>You will no longer be able to log in to the NRAPA platform</li>
            <li>Your membership and associated benefits have been cancelled</li>
            <li>Any pending endorsement requests have been cancelled</li>
        </ul>
    </div>

    <p>If you believe this action was taken in error, or if you have any questions, please contact us for further assistance.</p>

    <p>Best regards,<br><strong>NRAPA Team</strong></p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        This email was sent to {{ $userEmail }} regarding your NRAPA account.<br>
        &copy; {{ date('Y') }} National Rifle Association of South Africa. All rights reserved.
    </p>
</body>
</html>
