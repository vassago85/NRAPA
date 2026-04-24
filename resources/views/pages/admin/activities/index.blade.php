<?php

use App\Models\ShootingActivity;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(nullable: true)]
    public string $search = '';

    #[Url(nullable: true)]
    public string $statusFilter = 'pending';

    public function approve(int $activityId): void
    {
        $activity = ShootingActivity::findOrFail($activityId);
        $activity->approve(auth()->user());
        session()->flash('success', 'Activity approved successfully.');
        $this->resetPage(); // Reset pagination to show updated list
    }

    public function with(): array
    {
        $activities = ShootingActivity::with(['user', 'activityType', 'tags', 'country', 'province'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->whereHas('user', function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('id_number', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $statusCounts = ShootingActivity::selectRaw("status, count(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status');
        $pendingCount = (int) ($statusCounts['pending'] ?? 0);
        $approvedCount = (int) ($statusCounts['approved'] ?? 0);
        $rejectedCount = (int) ($statusCounts['rejected'] ?? 0);

        return [
            'activities' => $activities,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activities</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Review and manage member activity submissions</p>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4 text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button wire:click="$set('statusFilter', 'pending')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-amber-300' }}">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-amber-100 dark:bg-amber-900/30 p-2">
                    <svg class="size-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Review</p>
                </div>
            </div>
        </button>

        <button wire:click="$set('statusFilter', 'approved')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'approved' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-emerald-300' }}">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-emerald-100 dark:bg-emerald-900/30 p-2">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $approvedCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved</p>
                </div>
            </div>
        </button>

        <button wire:click="$set('statusFilter', 'rejected')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'rejected' ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-red-300' }}">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-red-100 dark:bg-red-900/30 p-2">
                    <svg class="size-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $rejectedCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Rejected</p>
                </div>
            </div>
        </button>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or ID number..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 pl-10 text-sm text-zinc-900 dark:text-white placeholder-zinc-500 focus:border-emerald-500 focus:ring-emerald-500">
            <svg class="absolute left-3 top-2.5 size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Activities List -->
    <div class="overflow-hidden rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
        @if($activities->isEmpty())
            <div class="p-8 text-center">
                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">No {{ $statusFilter }} activities found</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($activities as $activity)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($activity->user)
                                        <a href="{{ route('admin.members.show', $activity->user) }}" wire:navigate class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $activity->user->name }}</a>
                                    @else
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">Deleted User</div>
                                    @endif
                                    <div class="text-xs text-zinc-500">{{ $activity->user?->email ?? '-' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $activity->activity_date?->format('d M Y') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    <div class="font-medium">{{ $activity->activityType?->name ?? 'N/A' }}</div>
                                    @if($activity->track)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1 {{ $activity->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                            {{ ucfirst($activity->track) }}
                                        </span>
                                    @endif
                                    @if($activity->tags->count() > 0)
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($activity->tags as $tag)
                                                <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $tag->label }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $activity->full_location ?: 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($activity->status === 'approved')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-400">Approved</span>
                                    @elseif($activity->status === 'pending')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">Pending</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-400">Rejected</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a href="{{ route('admin.activities.show', $activity) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 transition-colors">Review</a>
                                    @if($activity->status === 'pending')
                                        <button wire:click="approve({{ $activity->id }})" class="ml-4 text-emerald-600 hover:text-emerald-700 transition-colors">Quick Approve</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 dark:border-zinc-800 px-6 py-4">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
