<?php

use App\Models\EndorsementRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app.sidebar')] #[Title('Endorsement Requests - Admin')] class extends Component {
    use WithPagination;

    public string $status = '';
    public string $type = '';
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        $query = EndorsementRequest::whereHas('user')
            ->with(['user', 'firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel'])
            ->orderBy('created_at', 'desc');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->type) {
            $query->where('request_type', $this->type);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('id_number', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        return [
            'pending' => EndorsementRequest::whereHas('user')->whereIn('status', ['submitted', 'under_review', 'pending_documents'])->count(),
            'approved' => EndorsementRequest::whereHas('user')->where('status', 'approved')->count(),
            'issued' => EndorsementRequest::whereHas('user')->where('status', 'issued')->count(),
            'this_month' => EndorsementRequest::whereHas('user')->where('status', 'issued')
                ->whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Endorsements</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and manage endorsement requests</p>
            </div>
            <a href="{{ route('admin.endorsements.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors text-sm font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Endorsement
            </a>
        </div>
    </x-slot>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Review</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['pending'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['approved'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Issued</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['issued'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">This Month</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['this_month'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or ID number..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="status" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Statuses</option>
                    @foreach(App\Models\EndorsementRequest::getStatusOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="type" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Types</option>
                    @foreach(App\Models\EndorsementRequest::getRequestTypeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Requests Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Firearm</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->requests as $request)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {{ $request->user?->initials() ?? '?' }}
                                    </div>
                                    <div>
                                        @if($request->user)
                                            <a href="{{ route('admin.members.show', $request->user) }}" wire:navigate class="font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $request->user->name }}</a>
                                        @else
                                            <p class="font-medium text-zinc-900 dark:text-white">Deleted User</p>
                                        @endif
                                        <p class="text-sm text-zinc-500">{{ $request->user?->email ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $request->request_type === 'new' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                    {{ $request->request_type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($request->firearm)
                                    <p class="text-sm text-zinc-900 dark:text-white">{{ $request->firearm->category_label }}</p>
                                    @if($request->firearm->calibre_display)
                                        <p class="text-xs text-zinc-500">{{ $request->firearm->calibre_display }}</p>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $request->status_badge_class }}">
                                    {{ $request->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($request->submitted_at)
                                    {{ $request->submitted_at->format('d M Y') }}
                                @else
                                    {{ $request->created_at->format('d M Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.endorsements.show', $request) }}" wire:navigate
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">No endorsement requests</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No requests match your current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->requests->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->requests->links() }}
            </div>
        @endif
    </div>
</div>
