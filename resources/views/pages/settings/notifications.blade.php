<?php

use App\Models\NotificationPreference;
use Livewire\Component;

new class extends Component {
    // NTFY Settings
    public string $ntfy_topic = '';
    public bool $ntfy_enabled = false;

    // Working Hours
    public string $working_hours_start = '08:00';
    public string $working_hours_end = '17:00';
    public array $working_days = [1, 2, 3, 4, 5];
    public bool $respect_working_hours = true;

    // Notification Types (Admin)
    public bool $notify_new_member = true;
    public bool $notify_payment_received = true;
    public bool $notify_document_uploaded = true;
    public bool $notify_document_rejected = true;
    public bool $notify_membership_expiring = true;
    public bool $notify_activity_submitted = true;
    public bool $notify_knowledge_test_completed = true;
    public bool $notify_endorsement_request = true;
    public bool $notify_system_errors = false;

    // License Expiry Notifications (All Members)
    public bool $notify_license_expiry = true;
    public bool $license_expiry_18m = true;
    public bool $license_expiry_12m = true;
    public bool $license_expiry_6m = true;

    public function mount(): void
    {
        $prefs = auth()->user()->notificationPreference;

        if ($prefs) {
            $this->ntfy_topic = $prefs->ntfy_topic ?? '';
            $this->ntfy_enabled = $prefs->ntfy_enabled;
            $this->working_hours_start = $prefs->working_hours_start ?? '08:00';
            $this->working_hours_end = $prefs->working_hours_end ?? '17:00';
            $this->working_days = $prefs->working_days ?? [1, 2, 3, 4, 5];
            $this->respect_working_hours = $prefs->respect_working_hours;
            $this->notify_new_member = $prefs->notify_new_member;
            $this->notify_payment_received = $prefs->notify_payment_received;
            $this->notify_document_uploaded = $prefs->notify_document_uploaded;
            $this->notify_document_rejected = $prefs->notify_document_rejected ?? true;
            $this->notify_membership_expiring = $prefs->notify_membership_expiring;
            $this->notify_activity_submitted = $prefs->notify_activity_submitted;
            $this->notify_knowledge_test_completed = $prefs->notify_knowledge_test_completed;
            $this->notify_endorsement_request = $prefs->notify_endorsement_request ?? true;
            $this->notify_system_errors = $prefs->notify_system_errors;
            $this->notify_license_expiry = $prefs->notify_license_expiry ?? true;
            
            // Parse license expiry intervals
            $intervals = $prefs->license_expiry_intervals ?? [18, 12, 6];
            $this->license_expiry_18m = in_array(18, $intervals);
            $this->license_expiry_12m = in_array(12, $intervals);
            $this->license_expiry_6m = in_array(6, $intervals);
        }
    }

    public function sendTestNotification(): void
    {
        $prefs = auth()->user()->notificationPreference;

        if (! $prefs || ! $prefs->ntfy_enabled || ! $prefs->ntfy_topic) {
            session()->flash('error', 'Please save your NTFY settings first (enable NTFY and set a topic).');
            return;
        }

        $ntfy = app(\App\Services\NtfyService::class);
        $success = $ntfy->send(
            $prefs->ntfy_topic,
            'NRAPA Test Notification',
            'If you see this, ntfy is working correctly! ' . now()->format('H:i:s'),
            'default',
            ['white_check_mark', 'test_tube']
        );

        if ($success) {
            session()->flash('success', 'Test notification sent! Check your ntfy app.');
        } else {
            session()->flash('error', 'Failed to send test notification. Check the topic name and try again.');
        }
    }

    public function save(): void
    {
        if (auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            $this->validate([
                'ntfy_topic' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
                'working_hours_start' => 'required|date_format:H:i',
                'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
                'working_days' => 'required|array|min:1',
            ]);
        }

        // Build license expiry intervals array
        $intervals = [];
        if ($this->license_expiry_18m) $intervals[] = 18;
        if ($this->license_expiry_12m) $intervals[] = 12;
        if ($this->license_expiry_6m) $intervals[] = 6;

        $data = [
            'notify_license_expiry' => $this->notify_license_expiry,
            'license_expiry_intervals' => $intervals,
        ];

        // Only save admin settings if user is admin
        if (auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            $data = array_merge($data, [
                'ntfy_topic' => $this->ntfy_topic ?: null,
                'ntfy_enabled' => $this->ntfy_enabled && !empty($this->ntfy_topic),
                'working_hours_start' => $this->working_hours_start,
                'working_hours_end' => $this->working_hours_end,
                'working_days' => $this->working_days,
                'respect_working_hours' => $this->respect_working_hours,
                'notify_new_member' => $this->notify_new_member,
                'notify_payment_received' => $this->notify_payment_received,
                'notify_document_uploaded' => $this->notify_document_uploaded,
                'notify_document_rejected' => $this->notify_document_rejected,
                'notify_membership_expiring' => $this->notify_membership_expiring,
                'notify_activity_submitted' => $this->notify_activity_submitted,
                'notify_knowledge_test_completed' => $this->notify_knowledge_test_completed,
                'notify_endorsement_request' => $this->notify_endorsement_request,
                'notify_system_errors' => $this->notify_system_errors,
            ]);
        }

        NotificationPreference::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        session()->flash('success', 'Notification preferences saved successfully.');
    }

    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->working_days)) {
            $this->working_days = array_values(array_diff($this->working_days, [$day]));
        } else {
            $this->working_days[] = $day;
            sort($this->working_days);
        }
    }
}; ?>

<section class="w-full">
    <x-slot name="header">
        @include('partials.settings-heading')
    </x-slot>

    <x-settings-layout heading="Notifications" subheading="Configure your notification preferences">

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                <p class="text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <!-- License Expiry Notifications - Available to ALL members -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Virtual Safe - License Expiry Alerts</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Get email reminders before your firearm licenses expire</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Enable License Expiry Notifications</label>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Receive email alerts when your firearm licenses are approaching expiry</p>
                        </div>
                        <button type="button" wire:click="$toggle('notify_license_expiry')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $notify_license_expiry ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $notify_license_expiry ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>

                    @if($notify_license_expiry)
                    <div class="pl-4 border-l-2 border-emerald-200 dark:border-emerald-800 space-y-3">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notify me at these intervals before expiry:</p>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="license_expiry_18m" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">18 months before expiry</span>
                        </label>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="license_expiry_12m" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">12 months before expiry</span>
                        </label>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="license_expiry_6m" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">6 months before expiry</span>
                        </label>
                        
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                            Select at least one interval to receive notifications. Renewal applications typically take 3-6 months to process, so early notification is recommended.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

        @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
                <!-- NTFY Configuration -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">NTFY Configuration</h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Enable NTFY Notifications</label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Receive push notifications via ntfy.sh</p>
                            </div>
                            <button type="button" wire:click="$toggle('ntfy_enabled')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $ntfy_enabled ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $ntfy_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">NTFY Topic</label>
                            <input type="text" wire:model="ntfy_topic" placeholder="your-unique-topic-name"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Create a unique topic at <a href="https://ntfy.sh" target="_blank" class="text-emerald-600 hover:underline">ntfy.sh</a> and enter it here. Only alphanumeric, dashes, and underscores.
                            </p>
                            @error('ntfy_topic') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <button type="button" wire:click="sendTestNotification"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-100 border border-amber-300 rounded-lg hover:bg-amber-200 dark:text-amber-300 dark:bg-amber-900/30 dark:border-amber-700 dark:hover:bg-amber-900/50 transition-colors">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Send Test Notification
                        </button>
                    </div>
                </div>

                <!-- Working Hours -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Working Hours</h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Respect Working Hours</label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Only receive notifications during working hours</p>
                            </div>
                            <button type="button" wire:click="$toggle('respect_working_hours')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $respect_working_hours ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $respect_working_hours ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Time</label>
                                <input type="time" wire:model="working_hours_start"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Time</label>
                                <input type="time" wire:model="working_hours_end"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Working Days</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7] as $label => $day)
                                    <button type="button" wire:click="toggleDay({{ $day }})"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ in_array($day, $working_days) ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Notifications outside working hours will be queued and sent when working hours start.</p>
                        </div>
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notification Types</h3>

                    <div class="space-y-3">
                        @foreach([
                            'notify_new_member' => ['New Member Registration', 'Get notified when a new member registers'],
                            'notify_payment_received' => ['Payment Received', 'Get notified when payment is confirmed'],
                            'notify_document_uploaded' => ['Document Uploaded', 'Get notified when a member uploads a document'],
                            'notify_document_rejected' => ['Document Rejected', 'Get notified when a member\'s document is rejected'],
                            'notify_membership_expiring' => ['Membership Expiring', 'Get notified about expiring memberships'],
                            'notify_activity_submitted' => ['Activity Submitted', 'Get notified when an activity is submitted for review'],
                            'notify_knowledge_test_completed' => ['Knowledge Test Completed', 'Get notified when a member completes their test'],
                            'notify_endorsement_request' => ['Endorsement Request', 'Get notified when a member submits an endorsement request'],
                        ] as $key => [$label, $description])
                            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                                <div>
                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                                </div>
                                <button type="button" wire:click="$toggle('{{ $key }}')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $this->$key ? 'bg-emerald-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $this->$key ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>
                        @endforeach

                        @if(auth()->user()->isDeveloper())
                            <div class="flex items-center justify-between py-2 border-t border-red-200 dark:border-red-800 mt-4 pt-4">
                                <div>
                                    <label class="text-sm font-medium text-red-700 dark:text-red-300">System Errors</label>
                                    <p class="text-xs text-red-500 dark:text-red-400">Developer only - Get notified about system errors</p>
                                </div>
                                <button type="button" wire:click="$toggle('notify_system_errors')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $notify_system_errors ? 'bg-red-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $notify_system_errors ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

        @endif

            <button type="submit" class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white font-medium rounded-lg transition-colors">
                Save Notification Preferences
            </button>
        </form>
    </x-settings-layout>
</section>
