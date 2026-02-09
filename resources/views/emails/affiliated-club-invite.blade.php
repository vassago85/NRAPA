@extends('emails.layout')

@section('title', 'NRAPA Club Membership Invitation')
@section('heading', "You're Invited!")
@section('subtitle', "Join NRAPA via {$club->name}")

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Hello,</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">You have been invited to join the <strong>National Rifle &amp; Pistol Association (NRAPA)</strong> as a member of <strong>{{ $club->name }}</strong>.</p>

    {{-- Membership details --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #1e40af; margin: 0 0 12px 0; font-size: 16px;">Membership Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #1e40af; font-size: 14px;">Club:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #1e40af; font-size: 14px;">{{ $club->name }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #1e40af; font-size: 14px;">Dedicated Status:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #1e40af; font-size: 14px;">{{ $club->dedicated_type_label }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #1e40af; font-size: 14px;">Sign-up Fee:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #1e40af; font-size: 14px;">R{{ number_format($club->initial_fee, 2) }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #1e40af; font-size: 14px;">Annual Renewal:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #1e40af; font-size: 14px;">R{{ number_format($club->renewal_fee, 2) }}/year</td>
            </tr>
        </table>
    </div>

    {{-- Requirements --}}
    @if($club->requires_competency || $club->required_activities_per_year > 0)
    <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 15px; margin: 0 0 20px 0;">
        <h4 style="color: #92400e; margin: 0 0 8px 0; font-size: 15px;">Requirements</h4>
        <ul style="color: #92400e; padding-left: 20px; margin: 0;">
            @if($club->requires_competency)
            <li style="margin-bottom: 4px;">Upload your SAPS Firearm Competency Certificate</li>
            @endif
            @if($club->required_activities_per_year > 0)
            <li style="margin-bottom: 4px;">Log {{ $club->required_activities_per_year }} activities per year (match results with your name)</li>
            @endif
            <li>All club applications require manual admin approval</li>
        </ul>
    </div>
    @endif

    {{-- CTA button --}}
    <div style="text-align: center; margin: 0 0 20px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ $inviteUrl }}" target="_blank"
                       style="display: inline-block; padding: 14px 35px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 16px;">
                        Accept Invitation
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p class="tx-muted" style="color: #6b7280; font-size: 14px; margin: 0 0 16px 0;">
        This invitation expires on <strong>{{ $invite->expires_at->format('d F Y') }}</strong>. If you don't have an NRAPA account yet, you'll need to register first and then use this link to complete your club membership application.
    </p>

    <p class="tx" style="color: #374151; margin: 0;">Kind regards,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $invite->email }} because an NRAPA administrator invited you to join via {{ $club->name }}.<br>
    If you did not expect this invitation, you can safely ignore this email.
@endsection
