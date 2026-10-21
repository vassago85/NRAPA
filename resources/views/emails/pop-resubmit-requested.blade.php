@extends('emails.layout')

@section('title', 'Please Resubmit Your Proof of Payment — NRAPA')
@section('heading', 'Please Resubmit Your Proof of Payment')
@section('subtitle', 'The file we have is not a proof of payment')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        The file on your
        @if($membership->isTypeChange())
            membership upgrade
        @else
            membership application
        @endif
        is not the proof of payment we need. Your upgrade request and the amount already approved stay as they are. Please upload the correct proof of payment.
    </p>

    <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #991b1b; display: block; margin-bottom: 10px;">Message from NRAPA</span>
        <p class="tx" style="color: #991b1b; margin: 0; font-size: 14px; line-height: 1.6;">{{ $reason }}</p>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        Amount due: <strong>R{{ number_format($membership->amount_due, 2) }}</strong>
        @if($membership->payment_reference)
            · reference <strong>{{ $membership->payment_reference }}</strong>
        @endif
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ route('membership.show', $membership) }}" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">Upload Proof of Payment</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection
