@extends('emails.layout')

@section('title', 'Endorsement Document Requires Attention — NRAPA')
@section('heading', 'Document Requires Attention')
@section('subtitle', 'Your endorsement document needs to be reviewed')

@section('content')
    @php
        $user = $document->endorsementRequest?->user;
        $docLabel = str_replace('_', ' ', $document->document_type ?? 'Document');
    @endphp

    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user?->name ?? 'Member' }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        Unfortunately, a document you submitted for your endorsement request could not be approved. Please review the reason below and re-upload a corrected version.
    </p>

    <div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #991b1b;">Document Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #991b1b; font-size: 14px;">Document Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #991b1b; font-size: 14px;">{{ ucwords($docLabel) }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #991b1b; font-size: 14px;">Status</td>
                <td style="padding: 5px 0; text-align: right;">
                    <span style="display: inline-block; background-color: #dc2626; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">REJECTED</span>
                </td>
            </tr>
        </table>
    </div>

    <div style="background-color: #fff7ed; border: 1px solid #fdba74; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
        <p class="tx" style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9a3412;">Reason</p>
        <p class="tx" style="margin: 0; font-size: 14px; color: #9a3412;">{{ $reason }}</p>
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/endorsements" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">View My Endorsements</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $user?->email ?? 'you' }} regarding your NRAPA endorsement request.
@endsection
