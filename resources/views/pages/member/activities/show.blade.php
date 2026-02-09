<?php

use App\Models\ShootingActivity;
use Livewire\Component;

new class extends Component {
    public ShootingActivity $activity;

    public function mount(ShootingActivity $activity): void
    {
        // Ensure user owns this activity
        if ($activity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->activity = $activity->load([
            'activityType',
            'tags',
            'firearmType',
            'userFirearm.firearmCalibre',
            'country',
            'province',
            'verifier',
        ]);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('activities.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activity Details</h1>
            </div>
            @if($activity->status === 'approved')
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-3 py-1 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Approved
                </span>
            @elseif($activity->status === 'pending')
                <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-3 py-1 text-sm font-medium text-yellow-800 dark:text-yellow-400">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    Pending Review
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900/30 px-3 py-1 text-sm font-medium text-red-800 dark:text-red-400">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Rejected
                </span>
            @endif
        </div>
    </x-slot>

    @if($activity->status === 'rejected' && $activity->rejection_reason)
        <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div class="flex items-start gap-3">
                <svg class="size-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">Rejection Reason</p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $activity->rejection_reason }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        <!-- Activity Information -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Activity Information</h2>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Date of Activity</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->activity_date->format('d F Y') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Track</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $activity->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                            {{ $activity->track ? ucfirst($activity->track) : 'N/A' }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Activity Type</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? 'N/A' }}</dd>
                </div>

                @if($activity->rounds_fired)
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Rounds Fired</dt>
                    <dd class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($activity->rounds_fired) }}</dd>
                </div>
                @endif

                @if($activity->tags->count() > 0)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Tags</dt>
                    <dd class="mt-1 flex flex-wrap gap-2">
                        @foreach($activity->tags as $tag)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-700 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:text-zinc-300">
                                {{ $tag->label }}
                            </span>
                        @endforeach
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Location -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Location</h2>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Location / Venue</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->location ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Country</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->country?->name ?? $activity->country_name ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Province</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->province?->name ?? $activity->province_name ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Closest Town/City</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->closest_town_city ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Firearm/Calibre -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Firearm / Calibre</h2>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Type of Firearm Used</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->firearmType?->name ?? 'N/A' }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Calibre / Bore</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->calibre_name ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Additional Information -->
        @if($activity->description)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Additional Information</h2>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $activity->description }}</p>
            </div>
        @endif

        <!-- Verification Details -->
        @if($activity->verified_at)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Verification Details</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Verified On</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->verified_at->format('d F Y, H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Verified By</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->verifier?->name ?? 'System' }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center gap-4">
            @if($activity->status === 'pending')
                <a href="{{ route('activities.edit', $activity) }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Activity
                </a>
            @endif
            <a href="{{ route('activities.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                Back to List
            </a>
        </div>
    </div>
</div>
