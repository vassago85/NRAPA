<?php

use App\Models\ConfigurationChangeRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Configuration Approvals - Owner')] class extends Component {
    use WithPagination;

    public string $statusFilter = 'pending';
    public ?string $viewingRequestId = null;
    public string $reviewNotes = '';

    #[Computed]
    public function changeRequests()
    {
        return ConfigurationChangeRequest::query()
            ->with(['requestedBy', 'reviewedBy'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function viewingRequest()
    {
        if (!$this->viewingRequestId) {
            return null;
        }
        return ConfigurationChangeRequest::with(['requestedBy', 'reviewedBy'])
            ->where('uuid', $this->viewingRequestId)
            ->first();
    }

    #[Computed]
    public function pendingCount()
    {
        return ConfigurationChangeRequest::pending()->count();
    }

    public function viewRequest(string $uuid): void
    {
        $this->viewingRequestId = $uuid;
        $this->reviewNotes = '';
    }

    public function closeView(): void
    {
        $this->viewingRequestId = null;
        $this->reviewNotes = '';
    }

    public function approveRequest(): void
    {
        $request = $this->viewingRequest;
        if (!$request || !$request->isPending()) {
            session()->flash('error', 'Cannot approve this request.');
            return;
        }

        $request->approve(auth()->user(), $this->reviewNotes);
        session()->flash('success', 'Change request approved and applied successfully.');
        $this->closeView();
    }

    public function rejectRequest(): void
    {
        $this->validate([
            'reviewNotes' => ['required', 'string', 'min:10'],
        ], [
            'reviewNotes.required' => 'Please provide a reason for rejection.',
            'reviewNotes.min' => 'Rejection reason must be at least 10 characters.',
        ]);

        $request = $this->viewingRequest;
        if (!$request || !$request->isPending()) {
            session()->flash('error', 'Cannot reject this request.');
            return;
        }

        $request->reject(auth()->user(), $this->reviewNotes);
        session()->flash('success', 'Change request rejected.');
        $this->closeView();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">@include('partials.owner-settings-heading')</x-slot>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-amber-100 p-3 dark:bg-amber-900/30">
                    <svg class="size-6 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->pendingCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approvals</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-4">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Filter by status:</label>
        <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    {{-- Request Detail Modal --}}
    @if($viewingRequest = $this->viewingRequest)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $viewingRequest->action_label }} {{ $viewingRequest->configuration_type_label }}
                        </h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Requested by {{ $viewingRequest->requestedBy?->name }} on {{ $viewingRequest->created_at->format('M d, Y H:i') }}
                        </p>
                    </div>
                    <button wire:click="closeView" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Status Badge --}}
                <div>
                    @if($viewingRequest->isPending())
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Pending Review</span>
                    @elseif($viewingRequest->isApproved())
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Approved</span>
                    @else
                    <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>
                    @endif
                </div>

                {{-- Reason --}}
                @if($viewingRequest->reason)
                <div>
                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Reason for Change</h4>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-3">
                        {{ $viewingRequest->reason }}
                    </p>
                </div>
                @endif

                {{-- Changes --}}
                <div class="grid gap-4 md:grid-cols-2">
                    @if($viewingRequest->old_values)
                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Current Values</h4>
                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-sm">
                            @if($viewingRequest->configuration_type === 'document_requirements')
                                <p class="font-medium text-zinc-900 dark:text-white mb-2">{{ $viewingRequest->old_values['membership_type'] ?? 'Unknown' }}</p>
                                @forelse($viewingRequest->old_values['document_types'] ?? [] as $doc)
                                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                                    <span>• {{ $doc['name'] }}</span>
                                    @if($doc['is_required'] ?? false)
                                    <span class="text-xs text-amber-600">(Required)</span>
                                    @endif
                                </div>
                                @empty
                                <p class="text-zinc-500">No documents required</p>
                                @endforelse
                            @else
                                @foreach($viewingRequest->old_values as $key => $value)
                                    @if(!in_array($key, ['id', 'created_at', 'updated_at']))
                                    <div class="flex justify-between py-1 border-b border-red-100 dark:border-red-800 last:border-0">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                        <span class="text-zinc-900 dark:text-white">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif

                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ $viewingRequest->old_values ? 'Proposed Values' : 'New Values' }}</h4>
                        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-4 text-sm">
                            @if($viewingRequest->configuration_type === 'document_requirements')
                                <p class="font-medium text-zinc-900 dark:text-white mb-2">{{ $viewingRequest->new_values['membership_type'] ?? 'Unknown' }}</p>
                                @forelse($viewingRequest->new_values['document_types'] ?? [] as $doc)
                                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                                    <span>• {{ $doc['name'] }}</span>
                                    @if($doc['is_required'] ?? false)
                                    <span class="text-xs text-amber-600">(Required)</span>
                                    @endif
                                </div>
                                @empty
                                <p class="text-zinc-500">No documents required</p>
                                @endforelse
                            @else
                                @foreach($viewingRequest->new_values as $key => $value)
                                    @if(!in_array($key, ['id', 'created_at', 'updated_at']))
                                    <div class="flex justify-between py-1 border-b border-emerald-100 dark:border-emerald-800 last:border-0">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                        <span class="text-zinc-900 dark:text-white">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Review Notes (for approved/rejected) --}}
                @if($viewingRequest->review_notes && !$viewingRequest->isPending())
                <div>
                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Review Notes</h4>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-3">
                        {{ $viewingRequest->review_notes }}
                    </p>
                    <p class="text-xs text-zinc-500 mt-2">
                        Reviewed by {{ $viewingRequest->reviewedBy?->name }} on {{ $viewingRequest->reviewed_at?->format('M d, Y H:i') }}
                    </p>
                </div>
                @endif

                {{-- Action Form (for pending) --}}
                @if($viewingRequest->isPending())
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Review Notes</label>
                        <textarea
                            wire:model="reviewNotes"
                            rows="3"
                            placeholder="Add notes (required for rejection)..."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        ></textarea>
                        @error('reviewNotes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button
                            wire:click="approveRequest"
                            wire:confirm="Are you sure you want to approve this change? It will be applied immediately."
                            class="flex-1 rounded-lg bg-nrapa-blue px-4 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
                        >
                            <svg class="inline-block size-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Approve & Apply
                        </button>
                        <button
                            wire:click="rejectRequest"
                            class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                        >
                            <svg class="inline-block size-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Reject
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Requests List --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->changeRequests as $request)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                            {{ $request->configuration_type_label }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($request->action === 'create')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Create</span>
                            @elseif($request->action === 'update')
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Update</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Delete</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $request->target_name ?? 'New record' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $request->requestedBy?->name ?? 'Unknown' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $request->created_at->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($request->isPending())
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Pending</span>
                            @elseif($request->isApproved())
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Approved</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button wire:click="viewRequest('{{ $request->uuid }}')" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p class="mt-4 text-zinc-500 dark:text-zinc-400">No change requests found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->changeRequests->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->changeRequests->links() }}
        </div>
        @endif
    </div>
</div>
