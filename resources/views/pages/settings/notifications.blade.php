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

    // Notification Types
    public bool $notify_new_member = true;
    public bool $notify_payment_received = true;
    public bool $notify_document_uploaded = true;
    public bool $notify_membership_expiring = true;
    public bool $notify_activity_submitted = true;
    public bool $notify_knowledge_test_completed = true;
    public bool $notify_system_errors = false;

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
            $this->notify_membership_expiring = $prefs->notify_membership_expiring;
            $this->notify_activity_submitted = $prefs->notify_activity_submitted;
            $this->notify_knowledge_test_completed = $prefs->notify_knowledge_test_completed;
            $this->notify_system_errors = $prefs->notify_system_errors;
        }
    }

    public function save(): void
    {
        $this->validate([
            'ntfy_topic' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'working_days' => 'required|array|min:1',
        ]);

        NotificationPreference::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'ntfy_topic' => $this->ntfy_topic ?: null,
                'ntfy_enabled' => $this->ntfy_enabled && !empty($this->ntfy_topic),
                'working_hours_start' => $this->working_hours_start,
                'working_hours_end' => $this->working_hours_end,
                'working_days' => $this->working_days,
                'respect_working_hours' => $this->respect_working_hours,
                'notify_new_member' => $this->notify_new_member,
                'notify_payment_received' => $this->notify_payment_received,
                'notify_document_uploaded' => $this->notify_document_uploaded,
                'notify_membership_expiring' => $this->notify_membership_expiring,
                'notify_activity_submitted' => $this->notify_activity_submitted,
                'notify_knowledge_test_completed' => $this->notify_knowledge_test_completed,
                'notify_system_errors' => $this->notify_system_errors,
            ]
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
    @include('partials.settings-heading')

    <x-settings-layout heading="Notifications" subheading="Configure push notifications via NTFY">

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        @if(!auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <p class="text-amber-700 dark:text-amber-300">Notification settings are only available for administrators and owners.</p>
            </div>
        @else
            <form wire:submit="save" class="space-y-6">
                <!-- NTFY Configuration -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
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
                    </div>
                </div>

                <!-- Working Hours -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
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
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notification Types</h3>

                    <div class="space-y-3">
                        @foreach([
                            'notify_new_member' => ['New Member Registration', 'Get notified when a new member registers'],
                            'notify_payment_received' => ['Payment Received', 'Get notified when payment is confirmed'],
                            'notify_document_uploaded' => ['Document Uploaded', 'Get notified when a member uploads a document'],
                            'notify_membership_expiring' => ['Membership Expiring', 'Get notified about expiring memberships'],
                            'notify_activity_submitted' => ['Activity Submitted', 'Get notified when an activity is submitted for review'],
                            'notify_knowledge_test_completed' => ['Knowledge Test Completed', 'Get notified when a member completes their test'],
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

                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                    Save Notification Preferences
                </button>
            </form>
        @endif
    </x-settings-layout>
</section>
