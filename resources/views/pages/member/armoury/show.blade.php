<?php

use App\Models\UserFirearm;
use Livewire\Component;

new class extends Component {
    public UserFirearm $firearm;

    public function mount(UserFirearm $firearm): void
    {
        if ($firearm->user_id !== auth()->id()) {
            abort(403);
        }

        $this->firearm = $firearm->load(['firearmType', 'firearmCalibre', 'firearmMake', 'firearmModel', 'loadData']);
    }

    public function deleteFirearm(): void
    {
        $this->firearm->delete();
        session()->flash('success', 'Firearm removed from your armoury.');
        $this->redirect(route('armoury.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('armoury.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $firearm->display_name }}</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $firearm->full_description }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('armoury.edit', $firearm) }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button wire:click="deleteFirearm" wire:confirm="Are you sure you want to remove this firearm from your armoury?"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Remove
                </button>
            </div>
        </div>
    </x-slot>

    <!-- License Status Alert -->
    @if($firearm->is_expired)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">License Expired!</p>
                    <p class="text-sm text-red-600 dark:text-red-400">This firearm's license expired on {{ $firearm->license_expiry_date->format('d M Y') }}. Please renew immediately.</p>
                </div>
            </div>
        </div>
    @elseif($firearm->is_expiring_soon)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-medium text-amber-800 dark:text-amber-200">License Expiring Soon</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">This license expires in {{ $firearm->days_until_expiry }} days ({{ $firearm->license_expiry_date->format('d M Y') }}). Start your renewal process.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Basic Information</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-zinc-500">Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->make_display ?? $firearm->make ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Model</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->model_display ?? $firearm->model ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Serial Number</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->serial_number ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Firearm Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->firearmType?->name ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Calibre</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->calibre_display ?? 'Not specified' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Barrel & Stock -->
            @if($firearm->barrel_length || $firearm->barrel_twist || $firearm->barrel_profile || $firearm->stock_type || $firearm->stock_make)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Barrel & Stock</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        @if($firearm->barrel_length)
                            <div>
                                <dt class="text-sm text-zinc-500">Barrel Length</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_length }}</dd>
                            </div>
                        @endif
                        @if($firearm->barrel_twist)
                            <div>
                                <dt class="text-sm text-zinc-500">Barrel Twist</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_twist }}</dd>
                            </div>
                        @endif
                        @if($firearm->barrel_profile)
                            <div>
                                <dt class="text-sm text-zinc-500">Barrel Profile</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_profile }}</dd>
                            </div>
                        @endif
                        @if($firearm->stock_type)
                            <div>
                                <dt class="text-sm text-zinc-500">Stock Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->stock_type }}</dd>
                            </div>
                        @endif
                        @if($firearm->stock_make)
                            <div>
                                <dt class="text-sm text-zinc-500">Stock Make</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->stock_make }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Optics -->
            @if($firearm->scope_make || $firearm->scope_model || $firearm->scope_magnification)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Optics</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        @if($firearm->scope_make)
                            <div>
                                <dt class="text-sm text-zinc-500">Scope Make</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_make }}</dd>
                            </div>
                        @endif
                        @if($firearm->scope_model)
                            <div>
                                <dt class="text-sm text-zinc-500">Scope Model</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_model }}</dd>
                            </div>
                        @endif
                        @if($firearm->scope_magnification)
                            <div>
                                <dt class="text-sm text-zinc-500">Magnification</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_magnification }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Load Data -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Load Data</h2>
                    <a href="{{ route('load-data.create') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                        + Add Load
                    </a>
                </div>
                @if($firearm->loadData->count() > 0)
                    <div class="space-y-3">
                        @foreach($firearm->loadData as $load)
                            <a href="{{ route('load-data.show', $load) }}" wire:navigate
                               class="block rounded-lg border border-zinc-200 dark:border-zinc-600 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $load->name }}</p>
                                        <p class="text-sm text-zinc-500">{{ $load->bullet_description }}</p>
                                    </div>
                                    @php $badge = $load->status_badge; @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if($badge['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($badge['color'] === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200
                                        @endif">
                                        {{ $badge['text'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500">No load data recorded yet.</p>
                @endif
            </div>

            <!-- Notes -->
            @if($firearm->notes)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notes</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ $firearm->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- License Status Card -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">License Status</h2>
                @php $badge = $firearm->license_status_badge; @endphp
                <div class="text-center mb-4">
                    <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium
                        @if($badge['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($badge['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @endif">
                        {{ $badge['text'] }}
                    </span>
                </div>
                <dl class="space-y-3">
                    @if($firearm->license_number)
                        <div>
                            <dt class="text-sm text-zinc-500">License Number</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_number }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_type)
                        <div>
                            <dt class="text-sm text-zinc-500">License Type</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_type_label }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_issue_date)
                        <div>
                            <dt class="text-sm text-zinc-500">Issue Date</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_issue_date->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_expiry_date)
                        <div>
                            <dt class="text-sm text-zinc-500">Expiry Date</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_expiry_date->format('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Firearm Image -->
            @if($firearm->image_path)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <img src="{{ Storage::url($firearm->image_path) }}" alt="{{ $firearm->display_name }}" class="w-full">
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('activities.submit') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Log Activity with this Firearm
                    </a>
                    <a href="{{ route('load-data.create') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Add Load Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
