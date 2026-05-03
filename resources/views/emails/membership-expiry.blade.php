@extends('emails.layout')

@section('title', $subject)
@section('heading', 'Membership Renewal')

@section('subtitle')
    @if($kind === \App\Models\MembershipRenewalReminder::KIND_EXPIRED)
        Your NRAPA membership has expired
    @elseif($kind === \App\Models\MembershipRenewalReminder::KIND_SEVEN_DAYS)
        Urgent: Your membership expires very soon
    @else
        Your NRAPA membership is expiring soon
    @endif
@endsection

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    @if($kind === \App\Models\MembershipRenewalReminder::KIND_EXPIRED)
        <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 20px; margin: 0 0 24px 0; text-align: center;">
            <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#9888;&#65039;</span>
            <span class="tx" style="font-weight: 700; font-size: 15px; color: #991b1b; display: block;">Your NRAPA membership expired on {{ $membership->expires_at->format('d F Y') }}.</span>
        </div>
        <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
            <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #1e40af; display: block; margin-bottom: 8px;">Good news</span>
            <p class="tx" style="color: #1e40af; margin: 0; font-size: 14px; line-height: 1.6;">
                With our new member platform you have a <strong>6-month grace period</strong> from your expiry date to renew without penalty.
                Renew now and keep your existing member number, certificates and endorsement record &mdash; no need to re-apply or pay a sign-up fee.
            </p>
        </div>
        <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 14px;">
            After the 6-month grace period ends you will need to re-apply as a new member and pay the full sign-up fee, so please don&rsquo;t leave it.
        </p>
    @elseif($kind === \App\Models\MembershipRenewalReminder::KIND_SEVEN_DAYS)
        <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 20px; margin: 0 0 24px 0; text-align: center;">
            <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#9888;&#65039;</span>
            <span class="tx" style="font-weight: 700; font-size: 15px; color: #991b1b; display: block;">URGENT: Your NRAPA membership expires in {{ $daysUntilExpiry }} {{ \Illuminate\Support\Str::plural('day', $daysUntilExpiry) }}!</span>
        </div>
    @else
        <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 20px; margin: 0 0 24px 0; text-align: center;">
            <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#9200;</span>
            <span class="tx" style="font-weight: 700; font-size: 15px; color: #92400e; display: block;">Your NRAPA membership expires in {{ $daysUntilExpiry }} days.</span>
        </div>
    @endif

    {{-- Membership details --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #374151;">Membership Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Member Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $membership->membership_number }}</td>
            </tr>
            @if($membership->type)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Membership Type</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $membership->type->name }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Expiry Date</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; font-size: 14px; color: {{ $kind === \App\Models\MembershipRenewalReminder::KIND_EXPIRED ? '#dc2626' : ($kind === \App\Models\MembershipRenewalReminder::KIND_SEVEN_DAYS ? '#dc2626' : '#d97706') }};">{{ $membership->expires_at->format('d F Y') }}</td>
            </tr>
            @if($kind !== \App\Models\MembershipRenewalReminder::KIND_EXPIRED)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Days Remaining</td>
                <td style="padding: 5px 0; text-align: right;">
                    @if($daysUntilExpiry <= 7)
                        <span style="display: inline-block; background-color: #dc2626; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px;">{{ $daysUntilExpiry }} {{ \Illuminate\Support\Str::plural('day', $daysUntilExpiry) }}</span>
                    @else
                        <span style="display: inline-block; background-color: #d97706; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px;">{{ $daysUntilExpiry }} days</span>
                    @endif
                </td>
            </tr>
            @endif
            @if($membership->type && $membership->type->renewal_price)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Renewal Fee</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">R{{ number_format((float) $membership->type->renewal_price, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Action --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
        <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #1e40af; display: block; margin-bottom: 8px;">Next Step</span>
        <p class="tx" style="color: #1e40af; margin: 0; font-size: 14px; line-height: 1.6;">
            Log in to your member portal and click <strong>Renew Membership</strong> on your dashboard. Renewing keeps your member number, certificates and endorsement record intact.
        </p>
    </div>

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ route('membership.index') }}" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">Renew Membership</span>
                </a>
            </td>
        </tr>
    </table>

    @if($kind === \App\Models\MembershipRenewalReminder::KIND_EXPIRED)
        <div class="bx-danger" style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 14px 16px; margin: 0 0 24px 0; border-radius: 0 8px 8px 0;">
            <p class="tx" style="margin: 0; color: #991b1b; font-size: 13px; font-weight: 700;">
                While your membership is lapsed you cannot be issued NRAPA certificates or endorsement letters. Please renew as soon as possible to restore access.
            </p>
        </div>
    @endif

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 14px; text-align: center;">Thank you for being a member of NRAPA.</p>

    <p class="tx" style="color: #374151; margin: 0; text-align: center; font-size: 15px;">
        Kind regards,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This reminder was sent because you have membership renewal notifications enabled.
    You can manage your notification preferences in your <a href="{{ route('settings.notifications') }}" style="color: #6b7280;">account settings</a>.
@endsection
