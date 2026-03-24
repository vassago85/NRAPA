@extends('emails.layout')

@php
    $isIssued = $endorsement->status === \App\Models\EndorsementRequest::STATUS_ISSUED;
    $user = $endorsement->user;
    $firearm = $endorsement->firearm;
@endphp

@section('title', $isIssued ? 'Endorsement Letter Issued — NRAPA' : 'Endorsement Approved — NRAPA')
@section('heading', $isIssued ? 'Endorsement Letter Issued!' : 'Endorsement Approved!')
@section('subtitle', $isIssued ? 'Your endorsement letter is ready to download' : 'Your endorsement request has been approved')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    @if($isIssued)
        <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
            Great news &mdash; your endorsement request has been <strong>approved</strong> and your endorsement letter has been <strong>issued</strong>. You can download it from your account.
        </p>
    @else
        <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
            Your endorsement request has been <strong>approved</strong>. Your endorsement letter will be issued shortly.
        </p>
    @endif

    {{-- Endorsement details --}}
    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #166534;">Endorsement Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Request Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ $endorsement->request_type_label }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Purpose</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ $endorsement->purpose_label }}</td>
            </tr>
            @if($firearm)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Firearm</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ trim(($firearm->make ?? '') . ' ' . ($firearm->model ?? '')) ?: 'N/A' }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Status</td>
                <td style="padding: 5px 0; text-align: right;">
                    @if($isIssued)
                        <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">ISSUED</span>
                    @else
                        <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; letter-spacing: 0.5px;">APPROVED</span>
                    @endif
                </td>
            </tr>
            @if($isIssued && $endorsement->letter_reference)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Reference</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-family: 'Courier New', monospace; font-size: 14px;">{{ $endorsement->letter_reference }}</td>
            </tr>
            @endif
            @if($isIssued && $endorsement->expires_at)
            <tr>
                <td class="tx" style="padding: 5px 0; color: #166534; font-size: 14px;">Valid Until</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #166534; font-size: 14px;">{{ $endorsement->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($isIssued)
        {{-- What's next --}}
        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
            <tr>
                <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                    <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What&rsquo;s Next?</span>
                </td>
            </tr>
        </table>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
            <tr>
                <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                    <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                    <strong>Download your letter</strong> from the Endorsements section in your account.
                </td>
            </tr>
            <tr>
                <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                    <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                    <strong>Print or save</strong> the letter for your records and submission.
                </td>
            </tr>
            <tr>
                <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                    <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                    <strong>Submit to SAPS</strong> along with your Section 16 licence application.
                </td>
            </tr>
        </table>
    @endif

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ config('app.url') }}/member/endorsements" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">{{ $isIssued ? 'Download My Letter' : 'View My Endorsements' }}</span>
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
    This email was sent to {{ $user->email }} regarding your NRAPA endorsement request.
@endsection
