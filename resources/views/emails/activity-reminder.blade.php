@extends('emails.layout')

@section('title', 'Upload your activities — NRAPA')

@section('heading')
    Deadline reminder: {{ $year }} activities
@endsection

@section('subtitle')
    Submit by {{ $deadline->format('d F Y') }}
@endsection

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
        To stay compliant for <strong>{{ $year }}</strong>, NRAPA members need at least
        <strong>{{ $required }}</strong> approved shooting activities dated between
        1 January and 31 October {{ $year }}. The submission deadline is
        <strong>{{ $deadline->format('d F Y') }}</strong>.
    </p>

    <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 10px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400e;">Your {{ $year }} record</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 4px 0; color: #92400e; font-size: 14px;">Approved activities so far</td>
                <td class="tx" style="padding: 4px 0; text-align: right; font-weight: 700; color: #92400e; font-size: 14px;">{{ $approvedSoFar }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 4px 0; color: #92400e; font-size: 14px;">Required for compliance</td>
                <td class="tx" style="padding: 4px 0; text-align: right; font-weight: 700; color: #92400e; font-size: 14px;">{{ $required }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 4px 0; color: #92400e; font-size: 14px;">Still outstanding</td>
                <td class="tx" style="padding: 4px 0; text-align: right; font-weight: 700; color: #92400e; font-size: 14px;">{{ $outstanding }}</td>
            </tr>
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0; font-size: 15px;">
        Please log into the NRAPA portal and submit the outstanding activities before
        {{ $deadline->format('d F Y') }}. Each activity will be reviewed and only
        <strong>approved</strong> submissions count toward your compliance record.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ $submitUrl }}" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">Submit an activity</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Questions? Reply to this email or contact us at
        <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $user->email }} because your {{ $year }} activity record is below the required minimum.
@endsection
