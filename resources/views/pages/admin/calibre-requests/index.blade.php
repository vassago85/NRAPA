<?php

use App\Models\Calibre;
use App\Models\CalibreRequest;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new #[Title('Calibre Requests')] class extends Component {
    use WithPagination;

    public string $statusFilter = 'pending';
    public ?int $reviewingRequestId = null;
    public string $adminNotes = '';
    public bool $showReviewModal = false;
    public string $reviewAction = '';

    public function with(): array
    {
        $requests = CalibreRequest::query()
            ->with(['user', 'reviewer', 'calibre'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        $pendingCount = CalibreRequest::pending()->count();

        return [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
        ];
    }

    public function openReviewModal(int $id, string $action): void
    {
        $this->reviewingRequestId = $id;
        $this->reviewAction = $action;
        $this->adminNotes = '';
        $this->showReviewModal = true;
    }

    public function approve(): void
    {
        $request = CalibreRequest::find($this->reviewingRequestId);
        if (!$request || $request->status !== 'pending') {
            session()->flash('error', 'Request not found or already processed.');
            $this->showReviewModal = false;
            return;
        }

        // Create the calibre
        $calibre = Calibre::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'category' => $request->category,
            'ignition_type' => $request->ignition_type,
            'is_active' => true,
            'is_common' => false,
            'is_obsolete' => false,
            'sort_order' => 999,
        ]);

        // Update the request
        $request->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_notes' => $this->adminNotes ?: null,
            'calibre_id' => $calibre->id,
        ]);

        $this->showReviewModal = false;
        $this->reviewingRequestId = null;
        session()->flash('success', "Calibre '{$calibre->name}' has been created and is now available for use.");
    }

    public function reject(): void
    {
        $this->validate([
            'adminNotes' => ['required', 'string', 'max:1000'],
        ], [
            'adminNotes.required' => 'Please provide a reason for rejecting this request.',
        ]);

        $request = CalibreRequest::find($this->reviewingRequestId);
        if (!$request || $request->status !== 'pending') {
            session()->flash('error', 'Request not found or already processed.');
            $this->showReviewModal = false;
            return;
        }

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_notes' => $this->adminNotes,
        ]);

        $this->showReviewModal = false;
        $this->reviewingRequestId = null;
        session()->flash('success', 'Request has been rejected.');
    }

    public function getReviewingRequestProperty()
    {
        return $this->reviewingRequestId ? CalibreRequest::with('user')->find($this->reviewingRequestId) : null;
    }
}; ?>

<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Calibre Requests</h1>
            <p class="mt-1 text-zinc-600 dark:text-zinc-400">Review and approve member calibre requests</p>
        </div>
        @if($pendingCount > 0)
            <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 dark:bg-amber-900/30 px-4 py-2 text-sm font-medium text-amber-800 dark:text-amber-300">
                <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ $pendingCount }} pending
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-6 flex items-center gap-4">
        <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-1">
            <button wire:click="$set('statusFilter', 'pending')" class="px-4 py-2 text-sm font-medium rounded-md {{ $statusFilter === 'pending' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Pending
            </button>
            <button wire:click="$set('statusFilter', 'approved')" class="px-4 py-2 text-sm font-medium rounded-md {{ $statusFilter === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Approved
            </button>
            <button wire:click="$set('statusFilter', 'rejected')" class="px-4 py-2 text-sm font-medium rounded-md {{ $statusFilter === 'rejected' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                Rejected
            </button>
            <button wire:click="$set('statusFilter', '')" class="px-4 py-2 text-sm font-medium rounded-md {{ $statusFilter === '' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                All
            </button>
        </div>
    </div>

    {{-- Requests Table --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Requested Calibre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($requests as $request)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $request->name }}</div>
                            @if($request->reason)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">{{ $request->reason }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-zinc-900 dark:text-white">{{ $request->user->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $request->user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium 
                                    {{ $request->category === 'handgun' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $request->category === 'rifle' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : '' }}
                                    {{ $request->category === 'shotgun' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}
                                    {{ $request->category === 'other' ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' : '' }}">
                                    {{ $request->category_label }}
                                </span>
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $request->ignition_type === 'rimfire' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ $request->ignition_type_label }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php $badge = $request->status_badge; @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium 
                                {{ $badge['color'] === 'amber' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}
                                {{ $badge['color'] === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $badge['color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                {{ $badge['text'] }}
                            </span>
                            @if($request->reviewer)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">by {{ $request->reviewer->name }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $request->created_at->format('d M Y') }}
                            <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($request->status === 'pending')
                                <button wire:click="openReviewModal({{ $request->id }}, 'approve')" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">Approve</button>
                                <button wire:click="openReviewModal({{ $request->id }}, 'reject')" class="ml-4 text-red-600 hover:text-red-700 text-sm font-medium">Reject</button>
                            @elseif($request->calibre_id)
                                <a href="{{ route('admin.firearm-settings.index') }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 text-sm">View Calibre</a>
                            @endif
                            @if($request->admin_notes)
                                <button type="button" 
                                        x-data
                                        @click="alert('{{ addslashes($request->admin_notes) }}')"
                                        class="ml-4 text-zinc-500 hover:text-zinc-700 text-sm">
                                    Notes
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="mt-2">No {{ $statusFilter ?: '' }} calibre requests found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    {{-- Review Modal --}}
    @if($showReviewModal && $this->reviewingRequest)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-zinc-500 dark:bg-zinc-900 bg-opacity-75 dark:bg-opacity-75" aria-hidden="true" wire:click="$set('showReviewModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-xl rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="modal-title">
                            {{ $reviewAction === 'approve' ? 'Approve' : 'Reject' }} Calibre Request
                        </h3>
                        <button type="button" wire:click="$set('showReviewModal', false)" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <div class="text-sm">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $this->reviewingRequest->name }}</p>
                            <p class="text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ $this->reviewingRequest->category_label }} - {{ $this->reviewingRequest->ignition_type_label }}
                            </p>
                            <p class="text-zinc-500 dark:text-zinc-400 mt-1">
                                Requested by: {{ $this->reviewingRequest->user->name }}
                            </p>
                            @if($this->reviewingRequest->reason)
                                <p class="text-zinc-600 dark:text-zinc-300 mt-2 text-sm">
                                    <span class="font-medium">Reason:</span> {{ $this->reviewingRequest->reason }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($reviewAction === 'approve')
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                            Approving this request will create a new calibre that will be available for all members to use.
                        </p>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Notes (optional)</label>
                            <textarea wire:model="adminNotes" rows="2" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('showReviewModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="button" wire:click="approve" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                                Approve & Create Calibre
                            </button>
                        </div>
                    @else
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                            Please provide a reason for rejecting this request. The member will see this reason.
                        </p>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                            <textarea wire:model="adminNotes" rows="3" placeholder="e.g., This calibre already exists as '.308 Winchester', please search for it." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                            @error('adminNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('showReviewModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="button" wire:click="reject" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Reject Request
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
