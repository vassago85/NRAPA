<?php

use App\Models\CalibreRequest;
use App\Models\FirearmCalibre;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Calibre Requests')] class extends Component {

    public string $statusFilter = 'pending';
    public string $search = '';

    // Editing request
    public ?CalibreRequest $editingRequest = null;
    public string $editName = '';
    public string $editCategory = '';
    public string $editIgnition = '';
    public string $editSapsCode = '';
    public string $adminNotes = '';
    public string $rejectionReason = '';

    #[Computed]
    public function requests()
    {
        return CalibreRequest::query()
            ->with(['user', 'reviewer', 'calibre'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'pending' => CalibreRequest::pending()->count(),
            'approved' => CalibreRequest::approved()->count(),
            'rejected' => CalibreRequest::rejected()->count(),
        ];
    }

    public function edit(CalibreRequest $request): void
    {
        $this->editingRequest = $request;
        $this->editName = $request->name;
        $this->editCategory = $request->category;
        $this->editIgnition = $request->ignition_type;
        $this->editSapsCode = $request->saps_code ?? '';
        $this->adminNotes = $request->admin_notes ?? '';
        $this->rejectionReason = '';
    }

    public function cancelEdit(): void
    {
        $this->editingRequest = null;
        $this->reset(['editName', 'editCategory', 'editIgnition', 'editSapsCode', 'adminNotes', 'rejectionReason']);
    }

    public function approve(): void
    {
        if (!$this->editingRequest) return;

        $this->validate([
            'editName' => 'required|string|min:2|max:100',
            'editCategory' => 'required|in:handgun,rifle,shotgun,muzzleloader,historic',
            'editIgnition' => 'required|in:rimfire,centerfire',
        ]);

        // Map 'other' category to 'rifle' if somehow it gets through
        $category = $this->editCategory === 'other' ? 'rifle' : $this->editCategory;

        // Create the FirearmCalibre entry
        $calibre = FirearmCalibre::create([
            'name' => $this->editName,
            'normalized_name' => FirearmCalibre::normalize($this->editName),
            'category' => $category,
            'ignition' => $this->editIgnition,
            'is_active' => true,
            'is_obsolete' => false,
            'is_wildcat' => false,
        ]);

        // Update the request
        $this->editingRequest->update([
            'name' => $this->editName,
            'category' => $category,
            'ignition_type' => $this->editIgnition,
            'saps_code' => $this->editSapsCode ?: null,
            'status' => CalibreRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_notes' => $this->adminNotes ?: null,
            'calibre_id' => $calibre->id,
        ]);

        $this->cancelEdit();
        session()->flash('success', "Calibre '{$calibre->name}' has been created and the request approved.");
    }

    public function reject(): void
    {
        if (!$this->editingRequest) return;

        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:500',
        ], [
            'rejectionReason.required' => 'Please provide a reason for rejection.',
        ]);

        $this->editingRequest->update([
            'status' => CalibreRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_notes' => $this->rejectionReason,
        ]);

        $this->cancelEdit();
        session()->flash('success', 'Calibre request has been rejected.');
    }
}; ?>

<div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Calibre Requests</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Review and approve member requests for new calibres.</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <button wire:click="$set('statusFilter', 'pending')"
            class="p-4 rounded-xl border {{ $statusFilter === 'pending' ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }} text-left transition-colors">
            <p class="text-2xl font-bold {{ $statusFilter === 'pending' ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white' }}">{{ $this->stats['pending'] }}</p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Pending</p>
        </button>
        <button wire:click="$set('statusFilter', 'approved')"
            class="p-4 rounded-xl border {{ $statusFilter === 'approved' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }} text-left transition-colors">
            <p class="text-2xl font-bold {{ $statusFilter === 'approved' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-white' }}">{{ $this->stats['approved'] }}</p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Approved</p>
        </button>
        <button wire:click="$set('statusFilter', 'rejected')"
            class="p-4 rounded-xl border {{ $statusFilter === 'rejected' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800' }} text-left transition-colors">
            <p class="text-2xl font-bold {{ $statusFilter === 'rejected' ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">{{ $this->stats['rejected'] }}</p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Rejected</p>
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 rounded-lg text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search calibre name..."
            class="w-full max-w-md px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
    </div>

    {{-- Requests List --}}
    @if($this->requests->count() > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Calibre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->requests as $request)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $request->name }}</div>
                                @if($request->saps_code)
                                    <div class="text-xs text-zinc-500 font-mono">{{ $request->saps_code }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $request->category_label }} / {{ $request->ignition_type_label }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $request->user->name }}
                            </td>
                            <td class="px-6 py-4">
                                @php $badge = $request->status_badge; @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $badge['color'] === 'amber' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                    {{ $badge['color'] === 'green' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                    {{ $badge['color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}">
                                    {{ $badge['text'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-500">
                                {{ $request->created_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($request->status === 'pending')
                                    <button wire:click="edit({{ $request->id }})"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        Review
                                    </button>
                                @else
                                    <button wire:click="edit({{ $request->id }})"
                                        class="text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">
                                        View
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No requests found</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                @if($statusFilter === 'pending')
                    No pending calibre requests.
                @else
                    No {{ $statusFilter }} calibre requests found.
                @endif
            </p>
        </div>
    @endif

    {{-- Edit/Review Modal --}}
    @if($editingRequest)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-zinc-500/75 dark:bg-zinc-900/75 transition-opacity" wire:click="cancelEdit"></div>

            <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $editingRequest->status === 'pending' ? 'Review Calibre Request' : 'View Calibre Request' }}
                    </h3>
                </div>

                <div class="px-6 py-4 space-y-4">
                    {{-- Requested By --}}
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Requested by <strong>{{ $editingRequest->user->name }}</strong> 
                            on {{ $editingRequest->created_at->format('d M Y \a\t H:i') }}
                        </p>
                        @if($editingRequest->reason)
                            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <strong>Notes:</strong> {{ $editingRequest->reason }}
                            </p>
                        @endif
                    </div>

                    @if($editingRequest->status === 'pending')
                        {{-- Editable fields --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Calibre Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="editName" 
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('editName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                                <select wire:model="editCategory" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="handgun">Handgun</option>
                                    <option value="rifle">Rifle</option>
                                    <option value="shotgun">Shotgun</option>
                                    <option value="muzzleloader">Muzzleloader</option>
                                    <option value="historic">Historic</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition</label>
                                <select wire:model="editIgnition" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="centerfire">Centerfire</option>
                                    <option value="rimfire">Rimfire</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                SAPS Code <span class="text-xs text-zinc-500">(Optional)</span>
                            </label>
                            <input type="text" wire:model="editSapsCode" placeholder="e.g., 65PRC"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono uppercase">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Notes</label>
                            <textarea wire:model="adminNotes" rows="2"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"></textarea>
                        </div>

                        {{-- Rejection reason --}}
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <label class="block text-sm font-medium text-red-800 dark:text-red-200 mb-1">
                                Rejection Reason (required to reject)
                            </label>
                            <textarea wire:model="rejectionReason" rows="2" placeholder="Explain why this request is being rejected..."
                                class="w-full px-4 py-2 border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white resize-none"></textarea>
                            @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        {{-- View only for approved/rejected --}}
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-zinc-500">Calibre Name</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $editingRequest->name }}</dd>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm text-zinc-500">Category</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $editingRequest->category_label }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500">Ignition</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $editingRequest->ignition_type_label }}</dd>
                                </div>
                            </div>
                            @if($editingRequest->saps_code)
                                <div>
                                    <dt class="text-sm text-zinc-500">SAPS Code</dt>
                                    <dd class="font-mono text-zinc-900 dark:text-white">{{ $editingRequest->saps_code }}</dd>
                                </div>
                            @endif
                            @if($editingRequest->admin_notes)
                                <div>
                                    <dt class="text-sm text-zinc-500">Admin Notes</dt>
                                    <dd class="text-zinc-900 dark:text-white">{{ $editingRequest->admin_notes }}</dd>
                                </div>
                            @endif
                            @if($editingRequest->reviewer)
                                <div>
                                    <dt class="text-sm text-zinc-500">Reviewed By</dt>
                                    <dd class="text-zinc-900 dark:text-white">
                                        {{ $editingRequest->reviewer->name }} on {{ $editingRequest->reviewed_at->format('d M Y') }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </div>

                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-between">
                    <button type="button" wire:click="cancelEdit"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        Close
                    </button>
                    
                    @if($editingRequest->status === 'pending')
                        <div class="flex gap-3">
                            <button type="button" wire:click="reject"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                Reject
                            </button>
                            <button type="button" wire:click="approve"
                                class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors">
                                Approve & Create
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
