<?php

use App\Models\ShootingActivity;
use Livewire\Component;

new class extends Component {
    public ShootingActivity $activity;
    public string $rejectionReason = '';
    public bool $showEvidencePreview = false;
    public bool $showAdditionalPreview = false;

    public function mount(ShootingActivity $activity): void
    {
        $this->activity = $activity->load([
            'user',
            'activityType',
            'tags',
            'firearmType',
            'userFirearm',
            'userFirearm.firearmCalibre',
            'country',
            'province',
            'verifier',
            'evidenceDocument',
            'additionalDocument',
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

    public function getPreviewUrl($document): ?string
    {
        if (!$document) {
            return null;
        }
        return route('admin.documents.preview', $document);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activity Review</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review member activity submission</p>
    </x-slot>
    
    <div class="mb-8">
        <a href="{{ route('admin.activities.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400 hover:text-emerald-600 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Activities
        </a>
        <div class="mt-2 flex items-center justify-between">
            @if($activity->status === 'approved')
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 text-sm font-medium text-emerald-800 dark:text-emerald-400">
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
        <div class="mb-6 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4 text-emerald-700 dark:text-emerald-300">
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
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Member Information</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Name</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->user?->name ?? 'Deleted User' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->user?->email ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Submitted</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->created_at->format('d F Y, H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Activity Information -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Activity Information</h2>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Date of Activity</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $activity->activity_date?->format('d F Y') ?? '-' }}</dd>
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
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            @if($activity->userFirearm && $activity->userFirearm->firearm_type_label)
                                {{ $activity->userFirearm->firearm_type_label }}
                            @elseif($activity->firearmType)
                                {{ $activity->firearmType->name }}
                            @else
                                N/A
                            @endif
                        </dd>
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
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Supporting Documents -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Supporting Documents</h2>

                <div class="space-y-3">
                    @if($activity->evidenceDocument)
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3">
                            <div class="flex items-center gap-3">
                                <svg class="size-8 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">Proof of Activity</p>
                                    <p class="text-xs text-zinc-500 truncate">{{ $activity->evidenceDocument->original_filename }}</p>
                                    <p class="text-xs text-zinc-400 mt-0.5">
                                        {{ number_format($activity->evidenceDocument->file_size / 1024, 1) }} KB
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button wire:click="$set('showEvidencePreview', true)" class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-xs font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Preview & Review
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3">
                            <div class="flex items-center gap-3">
                                <svg class="size-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <div>
                                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">No evidence document uploaded</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($activity->additionalDocument)
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-3">
                            <div class="flex items-center gap-3">
                                <svg class="size-8 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">Additional Document</p>
                                    <p class="text-xs text-zinc-500 truncate">{{ $activity->additionalDocument->original_filename }}</p>
                                    <p class="text-xs text-zinc-400 mt-0.5">
                                        {{ number_format($activity->additionalDocument->file_size / 1024, 1) }} KB
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button wire:click="$set('showAdditionalPreview', true)" class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-xs font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Preview & Review
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Verification Details -->
            @if($activity->verified_at)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
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
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Actions</h2>

                    <div class="space-y-4">
                        <button wire:click="approve" class="w-full rounded-lg bg-nrapa-blue px-4 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
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

    {{-- Evidence Document Preview Modal with Activity Information --}}
    @if($showEvidencePreview && $activity->evidenceDocument)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showEvidencePreview') }" x-show="open" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showEvidencePreview', false)" class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-6xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Review Document & Activity</h2>
                        <button wire:click="$set('showEvidencePreview', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Document Preview --}}
                        <div class="space-y-4">
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $activity->evidenceDocument->original_filename }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ number_format($activity->evidenceDocument->file_size / 1024, 1) }} KB • Uploaded {{ $activity->evidenceDocument->uploaded_at?->format('d M Y H:i') ?? 'N/A' }}
                                </p>
                            </div>

                            <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden" style="min-height: 60vh;">
                                @php $previewUrl = $this->getPreviewUrl($activity->evidenceDocument); @endphp
                                @if($previewUrl && str_contains($activity->evidenceDocument->mime_type, 'image'))
                                    <div class="p-4 flex items-center justify-center" style="min-height: 60vh;">
                                        <img src="{{ $previewUrl }}" alt="Document preview" class="max-w-full max-h-full mx-auto rounded-lg object-contain">
                                    </div>
                                @elseif($previewUrl && str_contains($activity->evidenceDocument->mime_type, 'pdf'))
                                    <div class="relative" style="min-height: 60vh;">
                                        <iframe 
                                            src="{{ $previewUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                                            class="w-full border-0"
                                            style="min-height: 60vh;"
                                            title="PDF Preview">
                                        </iframe>
                                        <div class="absolute bottom-2 right-2">
                                            <a href="{{ $previewUrl }}" target="_blank" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg shadow-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                Open Full Screen
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center p-8" style="min-height: 60vh;">
                                        <svg class="w-24 h-24 text-zinc-400 mb-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-zinc-600 dark:text-zinc-400 mb-4">Preview not available for this file type</p>
                                        @if($previewUrl)
                                            <a href="{{ $previewUrl }}" target="_blank" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download File
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Activity Information & Actions --}}
                        <div class="space-y-4">
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Activity Information</h3>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Member</dt>
                                        <dd class="text-zinc-900 dark:text-white font-medium">{{ $activity->user?->name ?? 'Deleted User' }}</dd>
                                        <dd class="text-zinc-600 dark:text-zinc-400 text-xs">{{ $activity->user?->email ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Activity Date</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->activity_date?->format('d F Y') ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Activity Type</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Track</dt>
                                        <dd>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $activity->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                                {{ ucfirst($activity->track) }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Location</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->location ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Country</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->country?->name ?? $activity->country_name ?? 'N/A' }}</dd>
                                    </div>
                                    @if($activity->province || $activity->province_name)
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Province</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->province?->name ?? $activity->province_name ?? 'N/A' }}</dd>
                                    </div>
                                    @endif
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Town/City</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->closest_town_city ?? 'N/A' }}</dd>
                                    </div>
                                    @if($activity->firearmType || $activity->userFirearm)
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Firearm</dt>
                                        <dd class="text-zinc-900 dark:text-white">
                                            @if($activity->userFirearm && $activity->userFirearm->firearm_type_label)
                                                {{ $activity->userFirearm->firearm_type_label }}
                                            @elseif($activity->firearmType)
                                                {{ $activity->firearmType->name }}
                                            @else
                                                N/A
                                            @endif
                                        </dd>
                                    </div>
                                    @endif
                                    @if($activity->calibre_name)
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Calibre</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->calibre_name }}</dd>
                                    </div>
                                    @endif
                                    @if($activity->description)
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Description</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->description }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>

                            @if($activity->status === 'pending')
                            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Review Actions</h4>
                                
                                <div class="space-y-3">
                                    <button wire:click="approve" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve Activity
                                    </button>

                                    <div>
                                        <label for="rejectionReasonModal" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rejection Reason</label>
                                        <textarea id="rejectionReasonModal" wire:model="rejectionReason" rows="3" placeholder="Enter reason for rejection..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-red-500 focus:ring-red-500"></textarea>
                                        @error('rejectionReason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>

                                    <button wire:click="reject" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Reject Activity
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Additional Document Preview Modal --}}
    @if($showAdditionalPreview && $activity->additionalDocument)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showAdditionalPreview') }" x-show="open" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4">
                <div wire:click="$set('showAdditionalPreview', false)" class="fixed inset-0 bg-black/50 transition-opacity"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-6xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Additional Document Preview</h2>
                        <button wire:click="$set('showAdditionalPreview', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Document Preview --}}
                        <div class="space-y-4">
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $activity->additionalDocument->original_filename }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ number_format($activity->additionalDocument->file_size / 1024, 1) }} KB • Uploaded {{ $activity->additionalDocument->uploaded_at?->format('d M Y H:i') ?? 'N/A' }}
                                </p>
                            </div>

                            <div class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden" style="min-height: 60vh;">
                                @php $previewUrl = $this->getPreviewUrl($activity->additionalDocument); @endphp
                                @if($previewUrl && str_contains($activity->additionalDocument->mime_type, 'image'))
                                    <div class="p-4 flex items-center justify-center" style="min-height: 60vh;">
                                        <img src="{{ $previewUrl }}" alt="Document preview" class="max-w-full max-h-full mx-auto rounded-lg object-contain">
                                    </div>
                                @elseif($previewUrl && str_contains($activity->additionalDocument->mime_type, 'pdf'))
                                    <div class="relative" style="min-height: 60vh;">
                                        <iframe 
                                            src="{{ $previewUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH"
                                            class="w-full border-0"
                                            style="min-height: 60vh;"
                                            title="PDF Preview">
                                        </iframe>
                                        <div class="absolute bottom-2 right-2">
                                            <a href="{{ $previewUrl }}" target="_blank" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg shadow-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                Open Full Screen
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center p-8" style="min-height: 60vh;">
                                        <svg class="w-24 h-24 text-zinc-400 mb-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-zinc-600 dark:text-zinc-400 mb-4">Preview not available for this file type</p>
                                        @if($previewUrl)
                                            <a href="{{ $previewUrl }}" target="_blank" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download File
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Activity Information --}}
                        <div class="space-y-4">
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Activity Information</h3>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Member</dt>
                                        <dd class="text-zinc-900 dark:text-white font-medium">{{ $activity->user?->name ?? 'Deleted User' }}</dd>
                                        <dd class="text-zinc-600 dark:text-zinc-400 text-xs">{{ $activity->user?->email ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Activity Date</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->activity_date?->format('d F Y') ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Activity Type</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->activityType?->name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500 dark:text-zinc-400">Location</dt>
                                        <dd class="text-zinc-900 dark:text-white">{{ $activity->location ?? 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
