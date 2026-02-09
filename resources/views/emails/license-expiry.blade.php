@extends('emails.layout')

@section('title', $subject)
@section('heading', 'License Expiry Notice')

@section('subtitle')
    @if($daysUntilExpiry <= 7)
        Urgent: Your firearm license expires very soon
    @elseif($daysUntilExpiry <= 30)
        Your firearm license is expiring soon
    @else
        Upcoming license expiry reminder
    @endif
@endsection

@section('content')
    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Hello {{ $user->name }},</p>

    @if($daysUntilExpiry <= 7)
        <div class="bx-danger" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin: 0 0 20px 0;">
            <p style="margin: 0; color: #991b1b; font-weight: bold; font-size: 14px;">&#9888;&#65039; URGENT: Your firearm license is expiring in {{ $daysUntilExpiry }} {{ Str::plural('day', $daysUntilExpiry) }}!</p>
        </div>
    @elseif($daysUntilExpiry <= 30)
        <div class="bx-warning" style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin: 0 0 20px 0;">
            <p style="margin: 0; color: #92400e; font-weight: bold; font-size: 14px;">&#9200; Your firearm license is expiring in {{ $daysUntilExpiry }} days.</p>
        </div>
    @else
        <p class="tx" style="color: #374151; margin: 0 0 20px 0;">This is a reminder about your upcoming firearm license expiry.</p>
    @endif

    {{-- Firearm details --}}
    <div class="bx-neutral" style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 0 0 20px 0;">
        <h3 class="hd" style="color: #111827; margin: 0 0 12px 0; font-size: 16px;">Firearm Details</h3>
        <table role="presentation" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Firearm:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-size: 14px;">{{ $firearm->display_name }}</td>
            </tr>
            @if($firearm->make && $firearm->model)
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Make/Model:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-size: 14px;">{{ $firearm->make }} {{ $firearm->model }}</td>
            </tr>
            @endif
            @if($firearm->serial_number)
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Serial Number:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $firearm->serial_number }}</td>
            </tr>
            @endif
            @if($firearm->license_number)
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">License Number:</td>
                <td class="tx" style="padding: 6px 0; text-align: right; font-weight: bold; color: #374151; font-family: 'Courier New', monospace; font-size: 14px;">{{ $firearm->license_number }}</td>
            </tr>
            @endif
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Expiry Date:</td>
                <td style="padding: 6px 0; text-align: right; font-weight: bold; color: {{ $daysUntilExpiry <= 7 ? '#dc2626' : ($daysUntilExpiry <= 30 ? '#d97706' : '#374151') }}; font-size: 14px;">{{ $firearm->license_expiry_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="tx-muted" style="padding: 6px 0; color: #6b7280; font-size: 14px;">Days Remaining:</td>
                <td style="padding: 6px 0; text-align: right; font-weight: bold; color: {{ $daysUntilExpiry <= 7 ? '#dc2626' : ($daysUntilExpiry <= 30 ? '#d97706' : '#374151') }}; font-size: 14px;">{{ $daysUntilExpiry }} {{ Str::plural('day', $daysUntilExpiry) }}</td>
            </tr>
        </table>
    </div>

    @if($daysUntilExpiry <= 30)
        {{-- Action required --}}
        <div class="bx-info" style="background-color: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 16px; margin: 0 0 20px 0;">
            <h3 class="hd" style="color: #1e40af; margin: 0 0 8px 0; font-size: 16px;">Action Required</h3>
            <p style="color: #1e40af; margin: 0; font-size: 14px;">Please start your license renewal process immediately to avoid any legal issues or interruptions to your shooting activities.</p>
        </div>
    @endif

    {{-- CTA button --}}
    <div style="text-align: center; margin: 0 0 20px 0;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="btn-primary">
            <tr>
                <td bgcolor="#0B4EA2" style="border-radius: 8px; text-align: center;">
                    <a href="{{ route('armoury.show', $firearm) }}" target="_blank"
                       style="display: inline-block; padding: 12px 30px; border-radius: 8px; background-color: #0B4EA2; color: #ffffff !important; text-decoration: none; font-weight: bold; font-family: Arial, sans-serif; font-size: 15px;">
                        View in My Armoury
                    </a>
                </td>
            </tr>
        </table>
    </div>

    <p class="tx" style="color: #374151; margin: 0 0 12px 0; font-size: 14px;">You can update your license details and upload your renewed license document in your armoury.</p>

    @if($daysUntilExpiry <= 7)
        <p style="color: #dc2626; margin: 0 0 16px 0; font-size: 14px; font-weight: bold;">Important: An expired firearm license may result in legal consequences. Please prioritise this renewal.</p>
    @endif

    <p class="tx" style="color: #374151; margin: 0 0 16px 0;">Thank you for being a responsible firearm owner and NRAPA member.</p>

    <p class="tx" style="color: #374151; margin: 0;">Stay safe and compliant,<br><strong>NRAPA Team</strong></p>
@endsection

@section('footer')
    This notification was sent because you have enabled license expiry notifications for your Virtual Safe.
    You can manage your notification preferences in your <a href="{{ route('settings.notifications') }}" style="color: #6b7280;">account settings</a>.
@endsection
