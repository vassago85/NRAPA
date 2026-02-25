<?php

use App\Models\LoadData;
use App\Models\FirearmCalibre;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterCalibre = '';
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleFavorite(LoadData $load): void
    {
        if ($load->user_id !== auth()->id()) {
            return;
        }

        $load->update(['is_favorite' => !$load->is_favorite]);
    }

    public function deleteLoad(LoadData $load): void
    {
        if ($load->user_id !== auth()->id()) {
            return;
        }

        $load->delete();
        session()->flash('success', 'Load data deleted.');
    }

    public function with(): array
    {
        $query = LoadData::query()
            ->forUser(auth()->id())
            ->with(['userFirearm', 'userFirearm.firearmCalibre']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('bullet_make', 'like', "%{$this->search}%")
                    ->orWhere('powder_type', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterCalibre) {
            $query->forCalibre($this->filterCalibre);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return [
            'loads' => $query->orderByDesc('is_favorite')->orderByDesc('created_at')->paginate(12),
            'calibres' => FirearmCalibre::whereIn('id', function ($query) {
                $query->select('firearm_calibre_id')
                    ->from('user_firearms')
                    ->whereIn('id', function ($q) {
                        $q->select('user_firearm_id')
                            ->from('load_data')
                            ->where('user_id', auth()->id())
                            ->whereNotNull('user_firearm_id');
                    });
            })->orWhereIn('id', function ($query) {
                // Legacy: calibre_id might still point to firearm_calibres if migrated
                $query->select('calibre_id')
                    ->from('load_data')
                    ->where('user_id', auth()->id())
                    ->whereNotNull('calibre_id');
            })->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Virtual Safe</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Reloading recipes and load development data</p>
    </x-slot>

    <x-virtual-safe-tabs current="loads" />

    <!-- Action Bar -->
    <div class="mb-6">
        <a href="{{ route('load-data.create') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Load
        </a>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search loads..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-500">
        </div>
        <div>
            <select wire:model.live="filterCalibre"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All Calibres</option>
                @foreach($calibres as $calibre)
                    <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All Statuses</option>
                <option value="development">Development</option>
                <option value="tested">Tested</option>
                <option value="approved">Approved</option>
                <option value="retired">Retired</option>
            </select>
        </div>
    </div>

    <!-- Load Data Grid -->
    @if($loads->count() > 0)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($loads as $load)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <div class="flex items-start justify-between mb-3 h-14">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white line-clamp-2 leading-tight" title="{{ $load->name }}">{{ $load->name }}</h3>
                                @if($load->is_favorite)
                                    <svg class="h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-500 truncate">{{ $load->calibre_name ?? 'No calibre' }}</p>
                        </div>
                        @php $badge = $load->status_badge; @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            @if($badge['color'] === 'green') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                            @elseif($badge['color'] === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                            @else bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300
                            @endif">
                            {{ $badge['text'] }}
                        </span>
                    </div>

                    <!-- Load details -->
                    <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        <p><span class="font-medium">Bullet:</span> {{ $load->bullet_description }}</p>
                        <p><span class="font-medium">Powder:</span> {{ $load->powder_description }}</p>
                        @if($load->muzzle_velocity || $load->velocity_sd || $load->group_size)
                            <p><span class="font-medium">Performance:</span> {{ $load->performance_summary }}</p>
                        @endif
                        @if($load->userFirearm)
                            <p><span class="font-medium">Firearm:</span> {{ $load->userFirearm->display_name }}</p>
                        @endif
                    </div>

                    @if($load->is_max_load)
                        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 px-3 py-2 text-xs text-red-700 dark:text-red-300">
                            ⚠️ Maximum load - use caution
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <button wire:click="toggleFavorite({{ $load->id }})"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            @if($load->is_favorite)
                                <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            @endif
                        </button>
                        <a href="{{ route('load-data.show', $load) }}" wire:navigate
                           class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            View
                        </a>
                        <a href="{{ route('load-data.edit', $load) }}" wire:navigate
                           class="flex-1 rounded-lg bg-nrapa-blue px-3 py-2 text-center text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                            Edit
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $loads->links() }}
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No load data yet</h3>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Start documenting your reloading recipes and load development.</p>
            <a href="{{ route('load-data.create') }}" wire:navigate
               class="mt-6 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Your First Load
            </a>
        </div>
    @endif
</div>
