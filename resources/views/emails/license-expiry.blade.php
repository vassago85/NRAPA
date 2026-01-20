@component('mail::message')
# Firearm License Expiry Notice

Dear {{ $user->name }},

This is a reminder that your firearm license is expiring soon.

**Firearm Details:**
- **Name:** {{ $firearm->display_name }}
@if($firearm->make && $firearm->model)
- **Make/Model:** {{ $firearm->make }} {{ $firearm->model }}
@endif
@if($firearm->serial_number)
- **Serial Number:** {{ $firearm->serial_number }}
@endif
@if($firearm->license_number)
- **License Number:** {{ $firearm->license_number }}
@endif
- **Expiry Date:** {{ $expiryDate }}
- **Time Until Expiry:** Approximately {{ $months }} months

**Action Required:**

Please ensure you renew your license before the expiry date to maintain compliance with South African firearms legislation. Remember that it is illegal to possess a firearm with an expired license.

@component('mail::button', ['url' => route('armoury.show', $firearm->uuid)])
View Firearm in Virtual Safe
@endcomponent

---

**Notification Settings:**

You can manage your license expiry notification preferences in your account settings.

@component('mail::button', ['url' => route('settings.notifications'), 'color' => 'secondary'])
Manage Notifications
@endcomponent

Stay safe and compliant,<br>
{{ config('app.name') }}

@component('mail::subcopy')
This notification was sent because you have enabled license expiry notifications for your Virtual Safe. You can disable these notifications in your account settings.
@endcomponent
@endcomponent
