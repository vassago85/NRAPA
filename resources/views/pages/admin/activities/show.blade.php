<?php

use App\Models\ShootingActivity;
use Livewire\Component;

new class extends Component {
    public ShootingActivity $activity;
    public string $rejectionReason = '';

    public function mount(ShootingActivity $activity): void
    {
        $this->activity = $activity->load([
            'user',
            'activityType',
            'tags',
            'firearmType',
            'calibre',
            'country',
            'province',
            'verifier',
        ]);
    }

    public function approve(): void
    {
        $this->activity->approve(auth()->user());
        session()->flash('success', 'Activity approved successfully.');
        $this->redirect(route('admin.activities.index'));
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $this->activity->reject(auth()->user(), $this->rejectionReason);
        session()->flash('success', 'Activity rejected.');
        $this->redirect(route('admin.activities.index'));
    }
}; ?>

<div>
    <div class="mb-8">
        <a href="{{ route('admin.activities.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400 hover:text-emerald-600">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Activities
        </a>
        <div class="mt-2 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Review Activity</h1>
            @if($activity->status === 'approved')
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-sm font-medium text-green-800 dark:text-green-400">
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
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($activity->status === 'rejected' && $activity->rejection_reason)
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div class="flex items-start gap-3">
                <svg class="size-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">Rejection Reason</p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $activity->rejection_reason }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Member Information -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Member Information</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Name</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->user->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->user->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Submitted</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->created_at->format('d F Y, H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Activity Information -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Activity Information</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Date of Activity</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->activity_date->format('d F Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Track</dt>
                        <dd class="mt-1">
                            @if($activity->track)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $activity->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                    {{ ucfirst($activity->track) }}
                                </span>
                            @else
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">N/A</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Activity Type</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? 'N/A' }}</dd>
                    </div>

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
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Location</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Location / Venue</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->location ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Country</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->country?->name ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Province</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->province?->name ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Closest Town/City</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->closest_town_city ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Firearm/Calibre -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Firearm / Calibre</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Type of Firearm Used</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->firearmType?->name ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Calibre / Bore</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->calibre?->name ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Additional Information -->
            @if($activity->description)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Additional Information</h2>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $activity->description }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Supporting Documents -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Supporting Documents</h2>

                <div class="space-y-3">
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3">
                        <div class="flex items-center gap-3">
                            <svg class="size-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">Proof of Activity</p>
                                <p class="text-xs text-zinc-500">Document uploaded</p>
                            </div>
                        </div>
                    </div>

                    @if($activity->additional_document_id)
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3">
                            <div class="flex items-center gap-3">
                                <svg class="size-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Additional Document</p>
                                    <p class="text-xs text-zinc-500">Document uploaded</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Verification Details -->
            @if($activity->verified_at)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Verification Details</h2>

                    <dl class="space-y-3">
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
            @if($activity->status === 'pending')
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Actions</h2>

                    <div class="space-y-4">
                        <button wire:click="approve" class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                            Approve Activity
                        </button>

                        <div>
                            <label for="rejectionReason" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rejection Reason</label>
                            <textarea id="rejectionReason" wire:model="rejectionReason" rows="3" placeholder="Enter reason for rejection..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-red-500 focus:ring-red-500"></textarea>
                            @error('rejectionReason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <button wire:click="reject" class="w-full rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            Reject Activity
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
