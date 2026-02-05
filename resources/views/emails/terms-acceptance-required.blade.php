<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required: Accept Terms & Conditions</title>
</head>
<body style="margin: 0; padding: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background-color: #f5f7fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; background: linear-gradient(135deg, #0B4EA2 0%, #F58220 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">Action Required</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #111827; font-size: 20px; font-weight: 600;">Hello {{ $user->name }},</h2>
                            
                            <p style="margin: 0 0 20px 0; color: #475569; font-size: 16px; line-height: 1.6;">
                                You must accept the <strong>NRAPA Membership Terms & Conditions</strong> before your membership can be activated and before any certificates or letters can be issued.
                            </p>
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 25px 0; border-radius: 0 8px 8px 0;">
                                <p style="margin: 0; color: #92400e; font-size: 14px; font-weight: 600;">
                                    ⚠️ Your membership status cannot become "Member in Good Standing" until you accept the Terms & Conditions.
                                </p>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $acceptUrl }}" style="display: inline-block; padding: 14px 28px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Accept Terms & Conditions
                                </a>
                            </div>
                            
                            <p style="margin: 20px 0 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                If the button above doesn't work, copy and paste this link into your browser:<br>
                                <a href="{{ $acceptUrl }}" style="color: #0B4EA2; text-decoration: underline;">{{ $acceptUrl }}</a>
                            </p>
                            
                            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 30px 0;">
                            
                            <p style="margin: 0 0 10px 0; color: #64748b; font-size: 14px;">
                                <strong>What happens after acceptance?</strong>
                            </p>
                            <ul style="margin: 0 0 20px 0; padding-left: 20px; color: #64748b; font-size: 14px; line-height: 1.8;">
                                <li>Your membership can be activated (subject to payment/approval)</li>
                                <li>You can request endorsement letters</li>
                                <li>Certificates can be issued to you</li>
                                <li>You'll have full access to the member portal</li>
                            </ul>
                            
                            <p style="margin: 30px 0 0 0; color: #64748b; font-size: 14px;">
                                If you have any questions, please contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2;">info@nrapa.co.za</a>
                            </p>
                            
                            <p style="margin: 20px 0 0 0; color: #64748b; font-size: 14px;">
                                Best regards,<br>
                                <strong>NRAPA Administration</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px; text-align: center;">
                                National Rifle & Pistol Association of South Africa (NRAPA)<br>
                                www.nrapa.co.za
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
