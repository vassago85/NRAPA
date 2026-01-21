<?php

use App\Models\Membership;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Approvals - Admin')] class extends Component {
    use WithPagination;

    #[Computed]
    public function pendingMemberships()
    {
        return Membership::query()
            ->with(['user', 'type'])
            ->where('status', 'applied')
            ->orderBy('applied_at', 'asc')
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        return [
            'pending' => Membership::where('status', 'applied')->count(),
            'approved_today' => Membership::where('status', 'active')
                ->whereDate('approved_at', today())
                ->count(),
            'approved_this_week' => Membership::where('status', 'active')
                ->whereBetween('approved_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Membership Approvals</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Review and approve pending membership applications.</p>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Approvals</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved Today</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['approved_today'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Approved This Week</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['approved_this_week'] }}</p>
        </div>
    </div>

    {{-- Pending Applications --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Pending Applications</h2>
        </div>

        @if($this->pendingMemberships->count() > 0)
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($this->pendingMemberships as $membership)
            <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-yellow-100 text-sm font-semibold text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                        {{ $membership->user->initials() }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $membership->user->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $membership->user->email }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                {{ $membership->type->name }}
                            </span>
                            <span class="text-zinc-400">•</span>
                            <span class="text-zinc-500 dark:text-zinc-400">R{{ number_format($membership->type->price, 2) }}</span>
                            @if($membership->payment_reference)
                            <span class="text-zinc-400">•</span>
                            <span class="inline-flex items-center gap-1 rounded bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 text-xs font-mono font-bold text-amber-800 dark:text-amber-200">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                {{ $membership->payment_reference }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Applied</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $membership->applied_at->format('d M Y') }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $membership->applied_at->diffForHumans() }}</p>
                    </div>
                    <a href="{{ route('admin.approvals.show', $membership) }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        Review
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($this->pendingMemberships->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->pendingMemberships->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto size-12 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                There are no pending membership applications to review.
            </p>
        </div>
        @endif
    </div>
</div>
