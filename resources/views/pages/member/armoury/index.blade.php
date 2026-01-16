<?php

use App\Models\UserFirearm;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterType = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteFirearm(UserFirearm $firearm): void
    {
        if ($firearm->user_id !== auth()->id()) {
            return;
        }

        $firearm->delete();
        session()->flash('success', 'Firearm removed from your armoury.');
    }

    public function with(): array
    {
        $query = UserFirearm::query()
            ->forUser(auth()->id())
            ->with(['firearmType', 'calibre']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('make', 'like', "%{$this->search}%")
                    ->orWhere('model', 'like', "%{$this->search}%")
                    ->orWhere('nickname', 'like', "%{$this->search}%")
                    ->orWhere('serial_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus === 'expired') {
            $query->expired();
        } elseif ($this->filterStatus === 'expiring') {
            $query->expiringSoon(90);
        } elseif ($this->filterStatus === 'valid') {
            $query->where(function ($q) {
                $q->whereNull('license_expiry_date')
                    ->orWhere('license_expiry_date', '>', now()->addDays(90));
            });
        }

        if ($this->filterType) {
            $query->where('firearm_type_id', $this->filterType);
        }

        return [
            'firearms' => $query->orderBy('created_at', 'desc')->paginate(12),
            'firearmTypes' => \App\Models\FirearmType::where('is_active', true)->orderBy('name')->get(),
            'expiringCount' => UserFirearm::forUser(auth()->id())->expiringSoon(90)->count(),
            'expiredCount' => UserFirearm::forUser(auth()->id())->expired()->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Armoury</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage your firearms, licenses, and track expiry dates</p>
            </div>
            <a href="{{ route('armoury.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Firearm
            </a>
        </div>
    </x-slot>

    <!-- Alert banners for expiring licenses -->
    @if($expiredCount > 0)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">{{ $expiredCount }} firearm license(s) have expired!</p>
                    <p class="text-sm text-red-600 dark:text-red-400">Please renew your licenses immediately to remain compliant.</p>
                </div>
            </div>
        </div>
    @endif

    @if($expiringCount > 0)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-medium text-amber-800 dark:text-amber-200">{{ $expiringCount }} firearm license(s) expiring within 90 days</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Start your renewal process early to avoid any issues.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search firearms..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-500">
        </div>
        <div>
            <select wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All License Statuses</option>
                <option value="valid">Valid (90+ days)</option>
                <option value="expiring">Expiring Soon (within 90 days)</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        <div>
            <select wire:model.live="filterType"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All Firearm Types</option>
                @foreach($firearmTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Firearms Grid -->
    @if($firearms->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($firearms as $firearm)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <!-- Firearm Image or Placeholder -->
                    <div class="h-40 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                        @if($firearm->image_path)
                            <img src="{{ Storage::url($firearm->image_path) }}" alt="{{ $firearm->display_name }}" class="h-full w-full object-cover">
                        @else
                            <svg class="h-16 w-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        @endif
                    </div>

                    <!-- Firearm Details -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $firearm->display_name }}</h3>
                                <p class="text-sm text-zinc-500">{{ $firearm->make }} {{ $firearm->model }}</p>
                            </div>
                            @php $badge = $firearm->license_status_badge; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($badge['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($badge['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif">
                                {{ $badge['text'] }}
                            </span>
                        </div>

                        <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                            @if($firearm->calibre)
                                <p><span class="font-medium">Calibre:</span> {{ $firearm->calibre->name }}</p>
                            @endif
                            @if($firearm->firearmType)
                                <p><span class="font-medium">Type:</span> {{ $firearm->firearmType->name }}</p>
                            @endif
                            @if($firearm->license_number)
                                <p><span class="font-medium">License:</span> {{ $firearm->license_number }}</p>
                            @endif
                            @if($firearm->license_expiry_date)
                                <p><span class="font-medium">Expires:</span> {{ $firearm->license_expiry_date->format('d M Y') }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('armoury.show', $firearm) }}" wire:navigate
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                View
                            </a>
                            <a href="{{ route('armoury.edit', $firearm) }}" wire:navigate
                               class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-emerald-700">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $firearms->links() }}
        </div>
    @else
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">Your armoury is empty</h3>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Add your first firearm to start tracking licenses and managing your collection.</p>
            <a href="{{ route('armoury.create') }}" wire:navigate
               class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Your First Firearm
            </a>
        </div>
    @endif
</div>
