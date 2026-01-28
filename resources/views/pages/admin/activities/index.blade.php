<?php

use App\Models\ShootingActivity;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending';

    public function approve(ShootingActivity $activity): void
    {
        $activity->approve(auth()->user());
        session()->flash('success', 'Activity approved successfully.');
    }

    public function with(): array
    {
        $activities = ShootingActivity::with(['user', 'activityType', 'tags', 'country', 'province'])
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->whereHas('user', function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $pendingCount = ShootingActivity::pending()->count();
        $approvedCount = ShootingActivity::approved()->count();
        $rejectedCount = ShootingActivity::rejected()->count();

        return [
            'activities' => $activities,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ];
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activity Verification</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Review and verify member shooting activities</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button wire:click="$set('statusFilter', 'pending')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'pending' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 hover:border-yellow-300' }}">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-yellow-100 dark:bg-yellow-900/30 p-2">
                    <svg class="size-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $pendingCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Review</p>
                </div>
            </div>
        </button>

        <button wire:click="$set('statusFilter', 'approved')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'approved' ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 hover:border-green-300' }}">
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-2">
                    <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $approvedCount }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved</p>
                </div>
            </div>
        </button>

        <button wire:click="$set('statusFilter', 'rejected')" class="rounded-lg border p-4 text-left transition-colors {{ $statusFilter === 'rejected' ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 hover:border-red-300' }}">
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
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member name or email..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2 pl-10 text-sm text-zinc-900 dark:text-white placeholder-zinc-500 focus:border-emerald-500 focus:ring-emerald-500">
            <svg class="absolute left-3 top-2.5 size-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Activities List -->
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        @if($activities->isEmpty())
            <div class="p-8 text-center">
                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">No {{ $statusFilter }} activities found</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($activities as $activity)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $activity->user->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $activity->user->email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $activity->activity_date->format('d M Y') }}
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
                                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-400">Approved</span>
                                    @elseif($activity->status === 'pending')
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:text-yellow-400">Pending</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-400">Rejected</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a href="{{ route('admin.activities.show', $activity) }}" wire:navigate class="text-emerald-600 hover:text-emerald-700">Review</a>
                                    @if($activity->status === 'pending')
                                        <button wire:click="approve({{ $activity->id }})" class="ml-4 text-green-600 hover:text-green-700">Quick Approve</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-4">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
