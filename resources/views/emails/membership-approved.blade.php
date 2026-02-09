@extends('emails.layout')

@section('title', 'Welcome to NRAPA')
@section('heading', 'Welcome to NRAPA!')
@section('subtitle', 'Your membership is now active')

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Dear {{ $membership->user->name }},</p>

    <p class="tx" style="color: #374151; margin: 0 0 20px 0;">We are pleased to confirm that your <strong>{{ $membership->type?->name ?? 'NRAPA' }} Membership</strong> has been approved and is now active.</p>

    {{-- Membership details --}}
    <div class="bx-success" style="background-color: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #166534; margin: 0 0 12px 0; font-size: 16px;">Membership Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Membership Number:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-family: 'Courier New', monospace; font-size: 14px;">{{ $membership->membership_number }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Membership Type:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ $membership->type?->name ?? 'Standard' }}</td>
            </tr>
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Activated:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ now()->format('d F Y') }}</td>
            </tr>
            @if($membership->expires_at)
            <tr>
                <td class="tx" style="padding: 6px 0; color: #166534; font-size: 14px;">Valid Until:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #166534; font-size: 14px;">{{ $membership->expires_at->format('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Digital card --}}
    <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 20px; margin: 0 0 20px 0; text-align: center;">
        <h3 class="hd" style="color: #1e40af; margin: 0 0 8px 0; font-size: 16px;">Your Digital Membership Card</h3>
        <p style="color: #1e40af; margin: 0 0 16px 0; font-size: 14px;">Your membership card is now available in your account. You can access it anytime from your phone.</p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ $cardUrl }}" target="_blank"
                       style="display: inline-block; padding: 12px 30px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 15px;">
                        View My Card
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <h3 class="hd" style="color: #111827; margin: 0 0 12px 0; font-size: 16px;">What's Next?</h3>
    <ul style="color: #374151; padding-left: 20px; margin: 0 0 20px 0;">
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Welcome Letter</strong> – Your welcome letter has been issued and is available under Certificates in your account.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Knowledge Test</strong> – Complete your dedicated status knowledge test to unlock endorsement requests.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Virtual Safe</strong> – Register your firearms for license renewal reminders and to link firearms to your Virtual Loading Bench data.</li>
        <li class="tx" style="margin-bottom: 8px; color: #374151;"><strong>Activities</strong> – Log your shooting activities to maintain your dedicated status.</li>
    </ul>

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">If you have any questions, please don't hesitate to contact us.</p>

    <p class="tx" style="color: #374151; margin: 0;">Welcome aboard,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This email was sent to {{ $membership->user->email }} because your NRAPA membership has been approved.
@endsection
