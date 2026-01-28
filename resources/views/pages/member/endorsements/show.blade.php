<?php

use App\Models\EndorsementRequest;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Endorsement Request')] class extends Component {
    public EndorsementRequest $request;

    public function mount(EndorsementRequest $request): void
    {
        if ($request->user_id !== auth()->id()) {
            abort(403);
        }
        
        $this->request = $request->load(['firearm', 'firearm.calibre', 'components', 'documents']);
    }

}; ?>

<div>
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('member.endorsements.index') }}" wire:navigate class="inline-flex items-center gap-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back
            </a>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Endorsement Request</h1>
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $request->request_type_label }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium {{ $request->status_badge_class }}">
                {{ $request->status_label }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Request Details --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Request Details</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Request Type</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->request_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Purpose</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->purpose_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Created</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        @if($request->submitted_at)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Submitted</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->submitted_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                        @if($request->issued_at)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Issued</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->issued_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                        @if($request->letter_reference)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Reference Number</dt>
                                <dd class="mt-1 font-mono font-medium text-emerald-600 dark:text-emerald-400">{{ $request->letter_reference }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($request->member_notes)
                        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Your Notes</dt>
                            <dd class="mt-2 text-sm text-zinc-900 dark:text-white">{{ $request->member_notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Firearm Details --}}
            @if($request->firearm)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Firearm Details</h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Category</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->category_label }}</dd>
                            </div>
                            @if($request->firearm->calibre_display)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Calibre</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->calibre_display }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->ignition_type)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Ignition</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->ignition_type_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->action_type)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Action</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->action_type_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->make || $request->firearm->model)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Make / Model</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->make }} {{ $request->firearm->model }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->serial_number)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Serial Number</dt>
                                    <dd class="mt-1 font-mono font-medium text-zinc-900 dark:text-white">{{ $request->firearm->serial_number }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->licence_section)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Licence Section</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $request->firearm->licence_section_label }}</dd>
                                </div>
                            @endif
                            @if($request->firearm->saps_reference)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">SAPS Reference</dt>
                                    <dd class="mt-1 font-mono font-medium text-zinc-900 dark:text-white">{{ $request->firearm->saps_reference }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Components --}}
            @if($request->components->count() > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Component Endorsements</h2>
                    </div>
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($request->components as $component)
                            <div class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-zinc-900 dark:text-white">{{ $component->component_type_label }}</h4>
                                        @if($component->component_make || $component->component_model)
                                            <p class="text-sm text-zinc-500">{{ $component->component_make }} {{ $component->component_model }}</p>
                                        @endif
                                        @if($component->calibre_display)
                                            <p class="text-sm text-zinc-500">Calibre: {{ $component->calibre_display }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Documents --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Documents</h2>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($request->documents as $doc)
                        <div class="p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                @if($doc->isUploaded())
                                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-lg">
                                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white">{{ $doc->document_type_label }}</h4>
                                    @if($doc->original_filename)
                                        <p class="text-sm text-zinc-500 truncate max-w-xs">{{ $doc->original_filename }}</p>
                                    @endif
                                    @if($doc->isActivityProof() && $doc->activity_date)
                                        <p class="text-xs text-zinc-400">
                                            {{ $doc->activity_type_label }} - {{ $doc->activity_date->format('d M Y') }}
                                            @if($doc->activity_venue) · {{ $doc->activity_venue }} @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->status_badge_class }}">
                                {{ $doc->status_label }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Status</h3>
                </div>
                <div class="p-6">
                    @if($request->isIssued())
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-green-600 dark:text-green-400 mb-2">Endorsement Issued</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Your endorsement letter is ready for download.</p>
                            
                            @if($request->isIssued())
                                <div class="flex gap-2">
                                    <a href="{{ route('member.endorsements.preview', $request) }}" target="_blank"
                                        class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        </svg>
                                        View Letter
                                    </a>
                                    <a href="{{ route('member.endorsements.preview', $request) }}" target="_blank"
                                        class="flex-1 px-4 py-2 border border-emerald-600 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Print
                                    </a>
                                </div>
                            @endif
                        </div>
                    @elseif($request->isRejected())
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Request Rejected</h4>
                            @if($request->rejection_reason)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $request->rejection_reason }}</p>
                            @endif
                        </div>
                    @elseif($request->isSubmitted())
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-blue-600 dark:text-blue-400 mb-2">Under Review</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Your request is being processed. You will be notified once reviewed.</p>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto bg-zinc-100 dark:bg-zinc-700 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Draft</h4>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">This request hasn't been submitted yet.</p>
                            <a href="{{ route('member.endorsements.edit', $request) }}" wire:navigate
                                class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                                Continue Editing
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Admin Notes --}}
            @if($request->admin_notes)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Admin Notes</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $request->admin_notes }}</p>
                    </div>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Timeline</h3>
                </div>
                <div class="p-6">
                    <ol class="relative border-l border-zinc-200 dark:border-zinc-700 space-y-6">
                        <li class="ml-6">
                            <span class="absolute flex items-center justify-center w-6 h-6 bg-emerald-100 rounded-full -left-3 dark:bg-emerald-900">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-white">Created</h3>
                            <time class="block text-xs text-zinc-500">{{ $request->created_at->format('d M Y H:i') }}</time>
                        </li>

                        @if($request->submitted_at)
                            <li class="ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 dark:bg-blue-900">
                                    <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">Submitted</h3>
                                <time class="block text-xs text-zinc-500">{{ $request->submitted_at->format('d M Y H:i') }}</time>
                            </li>
                        @endif

                        @if($request->reviewed_at)
                            <li class="ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-amber-100 rounded-full -left-3 dark:bg-amber-900">
                                    <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </span>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">Under Review</h3>
                                <time class="block text-xs text-zinc-500">{{ $request->reviewed_at->format('d M Y H:i') }}</time>
                            </li>
                        @endif

                        @if($request->issued_at)
                            <li class="ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-green-100 rounded-full -left-3 dark:bg-green-900">
                                    <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">Issued</h3>
                                <time class="block text-xs text-zinc-500">{{ $request->issued_at->format('d M Y H:i') }}</time>
                            </li>
                        @endif

                        @if($request->rejected_at)
                            <li class="ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-red-100 rounded-full -left-3 dark:bg-red-900">
                                    <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">Rejected</h3>
                                <time class="block text-xs text-zinc-500">{{ $request->rejected_at->format('d M Y H:i') }}</time>
                            </li>
                        @endif
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
