@extends('emails.layout')

@section('title', $subject)
@section('heading', 'Licence Expiry Notice')

@section('subtitle')
    @if($daysUntilExpiry <= 7)
        Urgent: Your firearm licence expires very soon
    @elseif($daysUntilExpiry <= 30)
        Your firearm licence is expiring soon
    @else
        Upcoming licence expiry reminder
    @endif
@endsection

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0; font-size: 15px;">Dear {{ $user->name }},</p>

    @if($daysUntilExpiry <= 7)
        <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 20px; margin: 0 0 24px 0; text-align: center;">
            <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#9888;&#65039;</span>
            <span class="tx" style="font-weight: 700; font-size: 15px; color: #991b1b; display: block;">URGENT: Your firearm licence expires in {{ $daysUntilExpiry }} {{ Str::plural('day', $daysUntilExpiry) }}!</span>
        </div>
    @elseif($daysUntilExpiry <= 30)
        <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 20px; margin: 0 0 24px 0; text-align: center;">
            <span style="font-size: 28px; display: block; margin-bottom: 8px;">&#9200;</span>
            <span class="tx" style="font-weight: 700; font-size: 15px; color: #92400e; display: block;">Your firearm licence expires in {{ $daysUntilExpiry }} days.</span>
        </div>
    @else
        <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 15px;">This is a friendly reminder about your upcoming firearm licence expiry.</p>
    @endif

    {{-- Firearm details --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin: 0 0 24px 0;">
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0 0 14px 0;">
                    <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #374151;">Firearm Details</span>
                </td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Firearm</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $firearm->display_name }}</td>
            </tr>
            @if($firearm->make && $firearm->model)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Make / Model</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-size: 14px;">{{ $firearm->make }} {{ $firearm->model }}</td>
            </tr>
            @endif
            @if($firearm->serial_number)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Serial Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $firearm->serial_number }}</td>
            </tr>
            @endif
            @if($firearm->license_number)
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Licence Number</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $firearm->license_number }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Expiry Date</td>
                <td class="tx" style="padding: 5px 0; text-align: right; font-weight: 700; font-size: 14px; color: {{ $daysUntilExpiry <= 7 ? '#dc2626' : ($daysUntilExpiry <= 30 ? '#d97706' : '#374151') }};">{{ $firearm->license_expiry_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 5px 0; color: #6b7280; font-size: 14px;">Days Remaining</td>
                <td style="padding: 5px 0; text-align: right;">
                    @if($daysUntilExpiry <= 7)
                        <span style="display: inline-block; background-color: #dc2626; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px;">{{ $daysUntilExpiry }} {{ Str::plural('day', $daysUntilExpiry) }}</span>
                    @elseif($daysUntilExpiry <= 30)
                        <span style="display: inline-block; background-color: #d97706; color: #ffffff; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px;">{{ $daysUntilExpiry }} days</span>
                    @else
                        <span class="tx" style="font-weight: 700; color: #374151; font-size: 14px;">{{ $daysUntilExpiry }} days</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if($daysUntilExpiry <= 30)
        {{-- Action required --}}
        <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 10px; padding: 20px; margin: 0 0 24px 0;">
            <span class="tx" style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; color: #1e40af; display: block; margin-bottom: 8px;">Action Required</span>
            <p class="tx" style="color: #1e40af; margin: 0; font-size: 14px; line-height: 1.6;">Please start your licence renewal process immediately to avoid any legal issues or interruptions to your shooting activities.</p>
        </div>
    @endif

    {{-- CTA --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 28px auto;" class="btn-primary">
        <tr>
            <td style="border-radius: 8px; text-align: center; background-color: #0B4EA2;">
                <a href="{{ route('armoury.show', $firearm) }}" target="_blank"
                   style="display: inline-block; padding: 14px 36px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff; text-decoration: none; font-weight: 700; font-family: Arial, sans-serif; font-size: 15px;">
                    <span style="color: #ffffff;">View in My Armoury</span>
                </a>
            </td>
        </tr>
    </table>

    <p class="tx" style="color: #6b7280; margin: 0 0 12px 0; font-size: 13px; text-align: center;">
        You can update your licence details and upload your renewed licence in your armoury.
    </p>

    @if($daysUntilExpiry <= 7)
        <div class="bx-danger" style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 14px 16px; margin: 0 0 24px 0; border-radius: 0 8px 8px 0;">
            <p class="tx" style="margin: 0; color: #991b1b; font-size: 13px; font-weight: 700;">An expired firearm licence may result in legal consequences. Please prioritise this renewal.</p>
        </div>
    @endif

    <p class="tx" style="color: #374151; margin: 0 0 24px 0; font-size: 14px; text-align: center;">Thank you for being a responsible firearm owner and NRAPA member.</p>

    <p class="tx" style="color: #374151; margin: 0; text-align: center; font-size: 15px;">
        Stay safe and compliant,<br><strong class="hd" style="color: #0B4EA2;">The NRAPA Team</strong>
    </p>
@endsection

@section('footer')
    This notification was sent because you have enabled licence expiry notifications for your Virtual Safe.
    You can manage your notification preferences in your <a href="{{ route('settings.notifications') }}" style="color: #6b7280;">account settings</a>.
@endsection
