@extends('emails.layout')

@section('title', 'Account Deleted — NRAPA')
@section('heading', 'Account Notification')
@section('subtitle', 'Your NRAPA account has been removed')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $userName }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        We regret to inform you that your NRAPA account associated with <strong>{{ $userEmail }}</strong> has been permanently deleted.
    </p>

    {{-- Reason --}}
    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #991b1b;">Reason for Deletion</span>
                </td>
            </tr>
        </table>
        <p class="tx" style="color: #991b1b; margin: 0; font-size: 14px; line-height: 1.6;">{{ $reason }}</p>
    </div>

    <p class="tx" style="color: #6b7280; margin: 0 0 24px 0; font-size: 13px;">
        This action was taken by <strong>{{ $deletedBy }}</strong> on {{ now()->format('d F Y') }} at {{ now()->format('H:i') }}.
    </p>

    {{-- What this means --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What This Means</span>
            </td>
        </tr>
    </table>

    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 28px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; padding: 0 10px 12px 0; width: 24px;">
                    <span style="color: #dc2626; font-size: 14px;">&#10005;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 12px 0; color: #374151; font-size: 14px;">You will no longer be able to log in to the NRAPA platform.</td>
            </tr>
            <tr>
                <td style="vertical-align: top; padding: 0 10px 12px 0; width: 24px;">
                    <span style="color: #dc2626; font-size: 14px;">&#10005;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 12px 0; color: #374151; font-size: 14px;">Your membership and associated benefits have been cancelled.</td>
            </tr>
            <tr>
                <td style="vertical-align: top; padding: 0 10px 0 0; width: 24px;">
                    <span style="color: #dc2626; font-size: 14px;">&#10005;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">Any pending endorsement requests have been cancelled.</td>
            </tr>
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 6px 0; font-size: 14px; text-align: center;">
        If you believe this was done in error, please contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Best regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $userEmail }} regarding your NRAPA account.
@endsection
