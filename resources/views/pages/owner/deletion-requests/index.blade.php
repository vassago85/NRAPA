<?php

use App\Models\UserDeletionRequest;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('User Deletion Requests - Owner')] class extends Component {
    use WithPagination;

    public string $filter = 'pending';
    public bool $showApproveModal = false;
    public bool $showRejectModal = false;
    public ?UserDeletionRequest $selectedRequest = null;
    public string $rejectionReason = '';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return UserDeletionRequest::with(['user', 'requestedBy', 'actionedBy'])
            ->when($this->filter === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($this->filter === 'approved', fn($q) => $q->where('status', 'approved'))
            ->when($this->filter === 'rejected', fn($q) => $q->where('status', 'rejected'))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'pending' => UserDeletionRequest::pending()->count(),
            'approved' => UserDeletionRequest::where('status', 'approved')->count(),
            'rejected' => UserDeletionRequest::where('status', 'rejected')->count(),
        ];
    }

    public function openApproveModal(UserDeletionRequest $request): void
    {
        $this->selectedRequest = $request;
        $this->showApproveModal = true;
    }

    public function openRejectModal(UserDeletionRequest $request): void
    {
        $this->selectedRequest = $request;
        $this->showRejectModal = true;
        $this->rejectionReason = '';
    }

    public function approveRequest(): void
    {
        if (!$this->selectedRequest || !$this->selectedRequest->isPending()) {
            session()->flash('error', 'This request cannot be approved.');
            $this->showApproveModal = false;
            return;
        }

        $userName = $this->selectedRequest->user->name;
        $this->selectedRequest->approve(auth()->user());

        $this->showApproveModal = false;
        $this->selectedRequest = null;
        
        session()->flash('success', "Deletion request approved. User {$userName} has been deleted.");
    }

    public function rejectRequest(): void
    {
        if (!$this->selectedRequest || !$this->selectedRequest->isPending()) {
            session()->flash('error', 'This request cannot be rejected.');
            $this->showRejectModal = false;
            return;
        }

        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500',
        ]);

        $this->selectedRequest->reject(auth()->user(), $this->rejectionReason);

        $this->showRejectModal = false;
        $this->selectedRequest = null;
        $this->rejectionReason = '';
        
        session()->flash('success', 'Deletion request has been rejected.');
    }

    public function getStatusClasses(string $status): string
    {
        return match($status) {
            'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Deletion Requests</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and process member account deletion requests</p>
    </x-slot>

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">User Deletion Requests</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Review and action deletion requests from administrators.</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-100 p-4 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-300 bg-red-100 p-4 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <button wire:click="$set('filter', 'pending')" class="rounded-xl border p-4 text-left transition-colors {{ $filter === 'pending' ? 'border-amber-500 bg-amber-50 dark:border-amber-600 dark:bg-amber-900/20' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 hover:border-amber-300 dark:hover:border-amber-700' }}">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Review</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['pending'] }}</p>
        </button>
        <button wire:click="$set('filter', 'approved')" class="rounded-xl border p-4 text-left transition-colors {{ $filter === 'approved' ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-600 dark:bg-emerald-900/20' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 hover:border-emerald-300 dark:hover:border-emerald-700' }}">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['approved'] }}</p>
        </button>
        <button wire:click="$set('filter', 'rejected')" class="rounded-xl border p-4 text-left transition-colors {{ $filter === 'rejected' ? 'border-red-500 bg-red-50 dark:border-red-600 dark:bg-red-900/20' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 hover:border-red-300 dark:hover:border-red-700' }}">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Rejected</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->stats['rejected'] }}</p>
        </button>
    </div>

    {{-- Requests Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->requests as $request)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($request->user)
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                    {{ $request->user->initials() }}
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $request->user->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $request->user->email }}</p>
                                </div>
                            </div>
                            @else
                            <span class="text-zinc-400 dark:text-zinc-500 italic">User deleted</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="text-zinc-900 dark:text-white">{{ $request->requestedBy->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $request->requestedBy->role_display_name }}</p>
                        </td>
                        <td class="max-w-xs px-6 py-4">
                            <p class="truncate text-sm text-zinc-600 dark:text-zinc-300" title="{{ $request->reason }}">{{ $request->reason }}</p>
                            @if($request->rejection_reason)
                            <p class="mt-1 truncate text-xs text-red-600 dark:text-red-400" title="{{ $request->rejection_reason }}">
                                Rejected: {{ $request->rejection_reason }}
                            </p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusClasses($request->status) }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            <p>{{ $request->created_at->format('d M Y') }}</p>
                            <p class="text-xs">{{ $request->created_at->format('H:i') }}</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            @if($request->isPending())
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openApproveModal({{ $request->id }})"
                                    class="rounded-lg bg-emerald-100 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:hover:bg-emerald-900 transition-colors">
                                    Approve
                                </button>
                                <button wire:click="openRejectModal({{ $request->id }})"
                                    class="rounded-lg bg-red-100 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300 dark:hover:bg-red-900 transition-colors">
                                    Reject
                                </button>
                            </div>
                            @else
                            <span class="text-sm text-zinc-400 dark:text-zinc-500">
                                @if($request->actionedBy)
                                by {{ $request->actionedBy->name }}
                                @endif
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No {{ $filter }} requests</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($filter === 'pending')
                                    There are no deletion requests pending your review.
                                @else
                                    No {{ $filter }} deletion requests found.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->requests->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->requests->links() }}
        </div>
        @endif
    </div>

    {{-- Approve Modal --}}
    @if($showApproveModal && $selectedRequest)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showApproveModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Approve Deletion Request</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">This action cannot be undone.</p>
                    </div>
                </div>
                
                <div class="mb-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900/50">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>User:</strong> {{ $selectedRequest->user?->name ?? 'Unknown' }}
                    </p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>Requested by:</strong> {{ $selectedRequest->requestedBy->name }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>Reason:</strong> {{ $selectedRequest->reason }}
                    </p>
                </div>

                <p class="mb-4 text-amber-600 dark:text-amber-400 text-sm">
                    Approving this request will permanently delete the user and all their data.
                </p>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showApproveModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="approveRequest"
                        class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Approve Deletion
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal && $selectedRequest)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showRejectModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50">
                        <svg class="size-5 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Reject Deletion Request</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Please provide a reason for rejection.</p>
                    </div>
                </div>
                
                <div class="mb-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900/50">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>User:</strong> {{ $selectedRequest->user?->name ?? 'Unknown' }}
                    </p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>Requested by:</strong> {{ $selectedRequest->requestedBy->name }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <strong>Reason:</strong> {{ $selectedRequest->reason }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rejection reason *</label>
                    <textarea wire:model="rejectionReason" rows="3" placeholder="Explain why this request is being rejected..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                    @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showRejectModal', false)" 
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="rejectRequest"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                        Reject Request
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
