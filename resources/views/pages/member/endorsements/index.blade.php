<?php

use App\Models\EndorsementRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Endorsement Letters')] class extends Component {
    
    #[Computed]
    public function requests()
    {
        return EndorsementRequest::where('user_id', auth()->id())
            ->with(['firearm', 'firearm.calibre', 'documents'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function hasDraft()
    {
        return EndorsementRequest::where('user_id', auth()->id())
            ->where('status', EndorsementRequest::STATUS_DRAFT)
            ->exists();
    }

    public function deleteRequest(EndorsementRequest $request): void
    {
        if ($request->user_id !== auth()->id()) {
            return;
        }

        if (!$request->isDraft()) {
            session()->flash('error', 'Only draft requests can be deleted.');
            return;
        }

        // Delete related records
        $request->documents()->delete();
        $request->components()->delete();
        $request->firearm?->delete();
        $request->delete();

        session()->flash('success', 'Draft request deleted successfully.');
    }

    public function cancelRequest(EndorsementRequest $request): void
    {
        if ($request->user_id !== auth()->id()) {
            return;
        }

        if (!$request->isSubmitted()) {
            session()->flash('error', 'This request cannot be cancelled.');
            return;
        }

        $request->cancel();
        session()->flash('success', 'Request cancelled successfully.');
    }
}; ?>

<div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Endorsement Letters</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Request endorsement letters for dedicated status firearms.</p>
        </div>
        <a href="{{ route('member.endorsements.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Request
        </a>
    </div>

    {{-- Flash Messages --}}
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

    {{-- Requests List --}}
    @if($this->requests->count() > 0)
        <div class="space-y-4">
            @foreach($this->requests as $request)
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-4">
                                {{-- Icon --}}
                                <div class="p-3 rounded-lg {{ $request->isRenewal() ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                                    @if($request->isRenewal())
                                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                            {{ $request->request_type_label }}
                                        </h3>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $request->status_badge_class }}">
                                            {{ $request->status_label }}
                                        </span>
                                    </div>

                                    @if($request->firearm)
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $request->firearm->summary }}
                                        </p>
                                    @endif

                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                        Created {{ $request->created_at->diffForHumans() }}
                                        @if($request->submitted_at)
                                            · Submitted {{ $request->submitted_at->diffForHumans() }}
                                        @endif
                                        @if($request->issued_at)
                                            · Issued {{ $request->issued_at->format('d M Y') }}
                                        @endif
                                    </p>

                                    @if($request->letter_reference)
                                        <p class="mt-1 text-sm font-mono text-emerald-600 dark:text-emerald-400">
                                            Ref: {{ $request->letter_reference }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($request->isDraft())
                                    <a href="{{ route('member.endorsements.edit', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Continue
                                    </a>
                                    <button wire:click="deleteRequest('{{ $request->uuid }}')"
                                        wire:confirm="Are you sure you want to delete this draft?"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                @elseif($request->isSubmitted())
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <button wire:click="cancelRequest('{{ $request->uuid }}')"
                                        wire:confirm="Are you sure you want to cancel this request?"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                @elseif($request->isIssued())
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                @else
                                    <a href="{{ route('member.endorsements.show', $request) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                        View Details
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Documents Progress (for draft/submitted) --}}
                        @if($request->isDraft() || $request->status === 'pending_documents')
                            @php
                                $totalDocs = $request->documents->where('is_required', true)->count();
                                $uploadedDocs = $request->documents->where('is_required', true)->whereIn('status', ['uploaded', 'verified', 'system_verified'])->count();
                            @endphp
                            @if($totalDocs > 0)
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-zinc-600 dark:text-zinc-400">Documents</span>
                                        <span class="text-zinc-900 dark:text-white font-medium">{{ $uploadedDocs }}/{{ $totalDocs }} uploaded</span>
                                    </div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $totalDocs > 0 ? ($uploadedDocs / $totalDocs * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No Endorsement Requests</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">
                You haven't submitted any endorsement letter requests yet. Start a new request to get an endorsement letter for your dedicated status firearms.
            </p>
            <a href="{{ route('member.endorsements.create') }}" wire:navigate
                class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Request Endorsement Letter
            </a>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <h4 class="font-semibold mb-1">About Endorsement Letters</h4>
                <p>
                    Endorsement letters confirm your dedicated status and are required when applying for Section 16 firearm licences. 
                    You can request a <strong>New Endorsement</strong> for first-time applications or a <strong>Renewal Endorsement</strong> 
                    for existing firearms (which can also include component requests like barrels or actions).
                </p>
            </div>
        </div>
    </div>
</div>
