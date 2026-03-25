@extends('emails.layout')

@section('title', 'Endorsement Document Approved — NRAPA')
@section('heading', 'Document Approved!')
@section('subtitle', 'Your endorsement document has been verified')

@section('content')
    @php
        $user = $document->endorsementRequest?->user;
        $docLabel = str_replace('_', ' ', $document->document_type ?? 'Document');
    @endphp

    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user?->name ?? 'Member' }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        A document you submitted for your endorsement request has been reviewed and <strong>approved</strong>.
    </p>

    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #166534;">Document Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Document Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ ucwords($docLabel) }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Status</td>
                <td style="padding: 5px 0; text-align: right;">
                    <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">VERIFIED</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Verified On</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ now()->format('d F Y') }}</td>
            </tr>
        </table>
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
