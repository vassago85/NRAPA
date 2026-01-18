<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRAPA Membership Payment Instructions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="display: inline-block; background: linear-gradient(135deg, #10b981, #047857); width: 60px; height: 60px; border-radius: 12px; line-height: 60px;">
            <span style="color: white; font-size: 30px; font-weight: bold;">N</span>
        </div>
        <h1 style="color: #1f2937; margin-top: 15px; margin-bottom: 5px;">NRAPA Membership</h1>
        <p style="color: #6b7280; margin: 0;">Payment Instructions</p>
    </div>

    <p>Dear {{ $membership->user->name }},</p>

    <p>Thank you for registering with NRAPA. To activate your <strong>{{ $membership->type->name }}</strong>, please make an EFT payment using the details below:</p>

    <div style="background: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3 style="color: #1f2937; margin-top: 0; margin-bottom: 15px;">Bank Account Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Bank:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $bankAccount['bank_name'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Account Name:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $bankAccount['account_name'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Account Number:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; font-family: monospace;">{{ $bankAccount['account_number'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Branch Code:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; font-family: monospace;">{{ $bankAccount['branch_code'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Account Type:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $bankAccount['account_type'] }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #166534;">Amount to Pay:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; font-size: 1.25em; color: #166534;">R{{ number_format($membership->type->price, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #166534;">Payment Reference:</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; font-family: monospace; color: #166534;">{{ $reference }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0; color: #92400e;"><strong>Important:</strong></p>
        <ul style="color: #92400e; margin: 10px 0 0 0; padding-left: 20px;">
            <li>Please use the exact reference shown above when making payment</li>
            <li>Allow 1-3 business days for payment verification</li>
            <li>Your membership will be activated once payment is confirmed</li>
        </ul>
    </div>

    <p>If you have any questions, please don't hesitate to contact us.</p>

    <p>Best regards,<br><strong>NRAPA Team</strong></p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        This email was sent to {{ $membership->user->email }} because you registered for a NRAPA membership.<br>
        &copy; {{ date('Y') }} National Rifle Association of South Africa. All rights reserved.
    </p>
</body>
</html>
