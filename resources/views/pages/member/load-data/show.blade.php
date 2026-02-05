<?php

use App\Models\LoadData;
use Livewire\Component;

new class extends Component {
    public LoadData $load;

    public function mount(LoadData $load): void
    {
        if ($load->user_id !== auth()->id()) {
            abort(403);
        }

        $this->load = $load->load(['userFirearm', 'userFirearm.firearmCalibre']);
    }

    public function toggleFavorite(): void
    {
        $this->load->update(['is_favorite' => !$this->load->is_favorite]);
    }

    public function deleteLoad(): void
    {
        $this->load->delete();
        session()->flash('success', 'Load data deleted.');
        $this->redirect(route('load-data.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('load-data.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $load->name }}</h1>
                        @if($load->is_favorite)
                            <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $load->calibre_name ?? 'No calibre specified' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="toggleFavorite"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    @if($load->is_favorite)
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Unfavorite
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Favorite
                    @endif
                </button>
                <a href="{{ route('load-data.edit', $load) }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button wire:click="deleteLoad" wire:confirm="Are you sure you want to delete this load data?"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </x-slot>

    @if($load->is_max_load)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">⚠️ Maximum Load Warning</p>
                    @if($load->safety_notes)
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $load->safety_notes }}</p>
                    @else
                        <p class="text-sm text-red-600 dark:text-red-400">This load is marked as a maximum load. Use extreme caution and work up carefully.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Projectile -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Projectile / Bullet</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Model</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_model ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Weight</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_weight ? $load->bullet_weight . ' gr' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">BC (G1)</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_bc ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Powder & Primer -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Powder & Primer</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Powder Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->powder_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Powder Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->powder_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Charge</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->powder_charge ? $load->powder_charge . ' gr' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Primer Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->primer_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Primer Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->primer_type ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Brass & Seating -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Brass & Seating</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Brass Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Firings</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_firings ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Annealed</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_annealed ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">COAL</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->coal ? $load->coal . '"' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">CBTO</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->cbto ? $load->cbto . '"' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Jump to Lands</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->jump_to_lands ? $load->jump_to_lands . '"' : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Notes -->
            @if($load->notes)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notes</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ $load->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Status</h2>
                @php $badge = $load->status_badge; @endphp
                <div class="text-center mb-4">
                    <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium
                        @if($badge['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($badge['color'] === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300
                        @endif">
                        {{ $badge['text'] }}
                    </span>
                </div>

                @if($load->userFirearm)
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4">
                        <dt class="text-sm text-zinc-500 mb-1">Firearm</dt>
                        <a href="{{ route('armoury.show', $load->userFirearm) }}" wire:navigate
                           class="font-medium text-emerald-600 hover:text-emerald-700">
                            {{ $load->userFirearm->display_name }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Performance -->
            @if($load->muzzle_velocity || $load->velocity_sd || $load->group_size)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Performance</h2>
                    <dl class="space-y-3">
                        @if($load->muzzle_velocity)
                            <div>
                                <dt class="text-sm text-zinc-500">Muzzle Velocity</dt>
                                <dd class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($load->muzzle_velocity) }} fps</dd>
                            </div>
                        @endif
                        @if($load->velocity_es)
                            <div>
                                <dt class="text-sm text-zinc-500">Extreme Spread</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->velocity_es }} fps</dd>
                            </div>
                        @endif
                        @if($load->velocity_sd)
                            <div>
                                <dt class="text-sm text-zinc-500">Standard Deviation</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->velocity_sd }} fps</dd>
                            </div>
                        @endif
                        @if($load->group_size)
                            <div>
                                <dt class="text-sm text-zinc-500">Group Size</dt>
                                <dd class="text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ $load->group_size }} {{ $load->group_size_unit === 'moa' ? 'MOA' : '"' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Testing Conditions -->
            @if($load->tested_date || $load->tested_distance || $load->tested_temperature)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Testing Conditions</h2>
                    <dl class="space-y-3">
                        @if($load->tested_date)
                            <div>
                                <dt class="text-sm text-zinc-500">Date</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_date->format('d M Y') }}</dd>
                            </div>
                        @endif
                        @if($load->tested_distance)
                            <div>
                                <dt class="text-sm text-zinc-500">Distance</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_distance }} {{ $load->tested_distance_unit }}</dd>
                            </div>
                        @endif
                        @if($load->tested_temperature)
                            <div>
                                <dt class="text-sm text-zinc-500">Temperature</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_temperature }}°C</dd>
                            </div>
                        @endif
                        @if($load->tested_altitude)
                            <div>
                                <dt class="text-sm text-zinc-500">Altitude</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($load->tested_altitude) }}m</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>
</div>
