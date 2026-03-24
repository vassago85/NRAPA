@extends('emails.layout')

@section('title', 'Club Invitation — NRAPA')
@section('heading', "You're Invited!")
@section('subtitle', "Join NRAPA via {$club->name}")

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Hello,</p>

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">
        You have been invited to join the <strong>National Rifle &amp; Pistol Association of South Africa (NRAPA)</strong>
        as a member of <strong>{{ $club->name }}</strong>. We&rsquo;d love to have you on board!
    </p>

    {{-- Club / Membership details --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #1e40af;">Membership Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #1e40af; font-size: 14px;">Club</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #1e40af; font-size: 14px;">{{ $club->name }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #1e40af; font-size: 14px;">Dedicated Status</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #1e40af; font-size: 14px;">{{ $club->dedicated_type_label }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #1e40af; font-size: 14px;">Sign-up Fee</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #1e40af; font-size: 14px;">R{{ number_format($club->initial_fee, 2) }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 5px 0; color: #1e40af; font-size: 14px;">Annual Renewal</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #1e40af; font-size: 14px;">R{{ number_format($club->renewal_fee, 2) }}/year</td>
            </tr>
        </table>
    </div>

    {{-- Requirements --}}
    @if($club->requires_competency || $club->required_activities_per_year > 0)
    <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #92400e; display: block; margin-bottom: 10px;">Requirements</span>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            @if($club->requires_competency)
            <tr>
                <td style="vertical-align: top; padding: 0 10px 8px 0; width: 24px;">
                    <span style="color: #d97706; font-size: 14px;">&#9679;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 8px 0; color: #92400e; font-size: 14px;">Upload your SAPS Firearm Competency Certificate</td>
            </tr>
            @endif
            @if($club->required_activities_per_year > 0)
            <tr>
                <td style="vertical-align: top; padding: 0 10px 8px 0; width: 24px;">
                    <span style="color: #d97706; font-size: 14px;">&#9679;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0 0 8px 0; color: #92400e; font-size: 14px;">Log {{ $club->required_activities_per_year }} {{ Str::plural('activity', $club->required_activities_per_year) }} per year (match results with your name)</td>
            </tr>
            @endif
            <tr>
                <td style="vertical-align: top; padding: 0 10px 0 0; width: 24px;">
                    <span style="color: #d97706; font-size: 14px;">&#9679;</span>
                </td>
                <td class="tx" style="vertical-align: top; padding: 0; color: #92400e; font-size: 14px;">All club applications require manual admin approval</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 24px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ $inviteUrl }}" target="_blank"
                   style="display: inline-block; padding: 16px 48px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 16px;">
                    <span style="color: #ffffff;">Accept Invitation</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; font-size: 13px; margin: 0 0 28px 0; text-align: center;">
        This invitation expires on <strong style="color: #374151;">{{ $invite->expires_at->format('d F Y') }}</strong>.<br>
        If you don&rsquo;t have an NRAPA account yet, you&rsquo;ll need to register first and then use this link to complete your application.
    </p>

    <p class="tx" style="color: #6b7280; margin: 0 0 6px 0; font-size: 13px; text-align: center;">
        Need help? Contact us at <a href="mailto:info@nrapa.co.za" style="color: #0B4EA2; text-decoration: underline;">info@nrapa.co.za</a>
    </p>

    <p class="tx" style="color: #374151; margin: 24px 0 0 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This email was sent to {{ $invite->email }} because an NRAPA administrator invited you to join via {{ $club->name }}.
    If you did not expect this invitation, you can safely ignore this email.
@endsection
