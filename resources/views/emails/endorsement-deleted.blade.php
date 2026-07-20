@extends('emails.layout')

@section('title', 'Endorsement Removed — NRAPA')
@section('heading', $wasIssued ? 'Endorsement Letter No Longer Valid' : 'Endorsement Request Removed')
@section('subtitle', $wasIssued ? 'Your issued endorsement letter has been removed from our records' : 'Your endorsement request has been removed')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $memberName }},</p>

    @if($wasIssued)
        <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
            We&rsquo;re writing to let you know that your NRAPA endorsement letter has been <strong>removed from our records</strong> by an administrator.
        </p>
    @else
        <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
            We&rsquo;re writing to let you know that your NRAPA endorsement request has been <strong>removed from our records</strong> by an administrator.
        </p>
    @endif

    {{-- Critical QR verification warning (issued letters only) --}}
    @if($wasIssued)
        <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0 0 10px 0;">
                        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #991b1b;">Important &mdash; Do Not Submit This Letter</span>
                    </td>
                </tr>
            </table>
            <p class="tx" style="color: #991b1b; margin: 0 0 12px 0; font-size: 14px; line-height: 1.6;">
                If you have a <strong>printed or saved copy</strong> of this endorsement letter, please <strong>do not submit it</strong> to SAPS or any other authority.
            </p>
            <p class="tx" style="color: #991b1b; margin: 0; font-size: 14px; line-height: 1.6;">
                Anyone scanning the QR code or opening the verification link on the letter will now see that it is <strong>no longer valid</strong>. Submitting a letter that verifies as invalid could delay or jeopardise your application.
            </p>
        </div>
    @endif

    {{-- Endorsement details snapshot --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #374151;">Removed Endorsement</span>
                </td>
            </tr>
            @if($letterReference)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Reference</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $letterReference }}</td>
            </tr>
            @endif
            @if($endorsementTypeLabel)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $endorsementTypeLabel }}</td>
            </tr>
            @endif
            @if($firearmSummary)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Firearm</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $firearmSummary }}</td>
            </tr>
            @endif
            @if($issuedAtDisplay)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Issued</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $issuedAtDisplay }}</td>
            </tr>
            @endif
            @if($expiresAtDisplay)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Was Valid Until</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $expiresAtDisplay }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- What to do next --}}
    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 16px 0;">
        <tr>
            <td style="border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <span class="hd" style="font-size: 17px; font-weight: 700; color: #111827;">What To Do Next</span>
            </td>
        </tr>
    </table>

    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0 0 28px 0;">
        @if($wasIssued)
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Destroy or set aside</strong> any printed or saved copies of the removed letter so they aren&rsquo;t submitted by mistake.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Contact us</strong> if you weren&rsquo;t expecting this &mdash; there may have been a mistake, or a replacement letter may already be on the way.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">3</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Check your account</strong> to see if a new endorsement letter has been issued to replace this one.
            </td>
        </tr>
        @else
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 14px 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">1</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0 0 14px 0; color: #374151; font-size: 14px;">
                <strong>Contact us</strong> if you weren&rsquo;t expecting this so we can explain why the request was removed.
            </td>
        </tr>
        <tr>
            <td style="width: 32px; vertical-align: top; padding: 0 12px 0 0;">
                <div style="width: 28px; height: 28px; background-color: #0B4EA2; color: #ffffff; border-radius: 50%; text-align: center; line-height: 28px; font-size: 13px; font-weight: 700;">2</div>
            </td>
            <td class="tx" style="vertical-align: top; padding: 0; color: #374151; font-size: 14px;">
                <strong>Submit a new request</strong> from your account when you&rsquo;re ready.
            </td>
        </tr>
        @endif
    </table>

    {{-- CTA --}}
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
    This email was sent to {{ $memberEmail }} regarding your NRAPA endorsement.
@endsection
