<?php

use App\Models\UserFirearm;
use App\Models\NotificationPreference;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterType = '';
    public bool $showNotificationBanner = true;

    public function mount(): void
    {
        // Check if user has already configured notification preferences
        $prefs = auth()->user()->notificationPreference;
        // Hide banner if they've explicitly set preferences (regardless of value)
        $this->showNotificationBanner = is_null($prefs);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function dismissNotificationBanner(): void
    {
        // Create default notification preferences when dismissed
        // This marks that the user has seen the notice
        NotificationPreference::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'notify_license_expiry' => true,
                'license_expiry_intervals' => [18, 12, 6],
            ]
        );
        $this->showNotificationBanner = false;
    }

    public function deleteFirearm(UserFirearm $firearm): void
    {
        if ($firearm->user_id !== auth()->id()) {
            return;
        }

        $firearm->delete();
        session()->flash('success', 'Firearm removed from your Virtual Safe.');
    }

    public function with(): array
    {
        $query = UserFirearm::query()
            ->forUser(auth()->id())
            ->with(['firearmType', 'firearmCalibre', 'firearmMake', 'firearmModel']);

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
        } elseif ($this->filterStatus === 'renewal') {
            $query->where('license_status', 'renewal_pending');
        } elseif ($this->filterStatus === 'valid') {
            $query->where(function ($q) {
                $q->whereNull('license_expiry_date')
                    ->orWhere('license_expiry_date', '>', now()->addDays(90));
            });
        }

        if ($this->filterType) {
            $query->where('firearm_type_id', $this->filterType);
        }

        $userId = auth()->id();
        $expiredCount = UserFirearm::forUser($userId)->expired()->count();
        $expiringCount = UserFirearm::forUser($userId)->expiringSoon(90)->count();
        $renewalCount = UserFirearm::forUser($userId)->where('license_status', 'renewal_pending')->count();
        $totalCount = UserFirearm::forUser($userId)->count();

        return [
            'firearms' => $query->orderBy('created_at', 'desc')->paginate(12),
            'firearmTypes' => \App\Models\FirearmType::where('is_active', true)->orderBy('name')->get(),
            'expiredCount' => $expiredCount,
            'expiringCount' => $expiringCount,
            'renewalCount' => $renewalCount,
            'activeCount' => max(0, $totalCount - $expiredCount - $expiringCount - $renewalCount),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Virtual Safe</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage your firearms, loads, and reloading inventory.</p>
    </x-slot>

    <x-virtual-safe-tabs current="firearms" />

    <!-- Action Bar -->
    <div class="mb-6">
        <a href="{{ route('armoury.create') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Firearm
        </a>
    </div>

    <!-- License Expiry Notification Feature Banner -->
    @if($showNotificationBanner)
        <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 p-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-emerald-800 dark:text-emerald-200">License Expiry Reminders Available</h4>
                    <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">
                        Never miss a renewal deadline! You can receive email reminders at 18, 12, and 6 months before your firearm licenses expire.
                        Renewal applications typically take 3-6 months to process.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a href="{{ route('notifications.edit') }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Configure Reminders
                        </a>
                        <button type="button" wire:click="dismissNotificationBanner"
                                class="text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 underline">
                            Dismiss (I'll use default settings)
                        </button>
                    </div>
                </div>
                <button type="button" wire:click="dismissNotificationBanner" class="flex-shrink-0 text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Firearm Status Summary -->
    <div class="mb-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
        <div class="flex items-center justify-between px-5 pt-4 pb-2">
            <h2 class="text-xs uppercase tracking-wider font-semibold text-zinc-500">Firearm Status</h2>
            @if($expiredCount > 0 || $expiringCount > 0)
                <span class="text-xs px-2 py-1 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Attention</span>
            @endif
        </div>
        <div class="grid grid-cols-4 gap-3 p-5 pt-2">
            <button wire:click="$set('filterStatus', '{{ $filterStatus === 'expired' ? '' : 'expired' }}')" type="button"
                    class="rounded-xl p-3 text-center transition {{ $filterStatus === 'expired' ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $expiredCount }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Expired</p>
            </button>
            <button wire:click="$set('filterStatus', '{{ $filterStatus === 'expiring' ? '' : 'expiring' }}')" type="button"
                    class="rounded-xl p-3 text-center transition {{ $filterStatus === 'expiring' ? 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $expiringCount }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Expiring</p>
            </button>
            <button wire:click="$set('filterStatus', '{{ $filterStatus === 'renewal' ? '' : 'renewal' }}')" type="button"
                    class="rounded-xl p-3 text-center transition {{ $filterStatus === 'renewal' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $renewalCount }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Renewal</p>
            </button>
            <button wire:click="$set('filterStatus', '{{ $filterStatus === 'valid' ? '' : 'valid' }}')" type="button"
                    class="rounded-xl p-3 text-center transition {{ $filterStatus === 'valid' ? 'ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $activeCount }}</p>
                <p class="text-xs uppercase tracking-wider text-zinc-500 mt-1">Active</p>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search firearms..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue/50 focus:border-nrapa-blue transition-colors">
        </div>
        <div>
            <select wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All License Statuses</option>
                <option value="valid">Active (90+ days)</option>
                <option value="expiring">Expiring Soon (90 days)</option>
                <option value="expired">Expired</option>
                <option value="renewal">Renewal Pending</option>
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
                @php $badge = $firearm->license_status_badge; @endphp
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition overflow-hidden">
                    <div class="relative h-40 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        @if($firearm->image_path)
                            <img src="{{ Storage::disk('public')->url($firearm->image_path) }}" alt="{{ $firearm->display_name }}" class="h-full w-full object-cover">
                        @else
                            <svg class="h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        @endif
                        <span class="absolute top-3 left-3 text-xs px-2 py-1 rounded-full font-medium
                            @if($badge['color'] === 'green') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-300
                            @elseif($badge['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900/80 dark:text-red-300
                            @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900/80 dark:text-amber-300
                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/80 dark:text-amber-300 @endif">
                            {{ $badge['text'] }}
                        </span>
                        @if($firearm->license_expiry_date)
                            <span class="absolute top-3 right-3 text-xs px-2 py-1 rounded-full font-medium bg-white/90 dark:bg-zinc-900/90 text-zinc-600 dark:text-zinc-400">
                                Expires: {{ $firearm->license_expiry_date->format('d M Y') }}
                            </span>
                        @endif
                    </div>

                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">{{ $firearm->display_name }}</h3>

                        <dl class="space-y-2 mb-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Type</dt>
                                <dd class="text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ $firearm->firearm_type_label ?? $firearm->firearmType?->name ?? 'Not specified' }}@if($firearm->action_label) ({{ $firearm->action_label }})@endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Calibre</dt>
                                <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $firearm->calibre_display ?? 'Not specified' }}</dd>
                            </div>
                            @if($firearm->license_number)
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Application No.</dt>
                                    <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $firearm->license_number }}</dd>
                                </div>
                            @endif
                            @if($firearm->license_expiry_date)
                                @php
                                    $renewBy = $firearm->license_expiry_date->copy()->subMonths(6);
                                    $renewDays = (int) now()->startOfDay()->diffInDays($renewBy, false);
                                @endphp
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">Renew by</dt>
                                    <dd class="text-sm {{ $renewDays < 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-zinc-800 dark:text-zinc-200' }}">
                                        {{ $renewBy->format('d M Y') }}
                                        <span class="text-xs {{ $renewDays < 0 ? 'text-red-500 dark:text-red-500' : 'text-zinc-400 dark:text-zinc-500' }}">
                                            ({{ $renewDays < 0 ? abs($renewDays) . 'd overdue' : $renewDays . ' days' }})
                                        </span>
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <div class="flex items-center gap-1 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <a href="{{ route('armoury.show', $firearm) }}" wire:navigate title="View details"
                               class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('armoury.edit', $firearm) }}" wire:navigate title="Edit"
                               class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button wire:click="deleteFirearm({{ $firearm->id }})" wire:confirm="Remove this firearm from your Virtual Safe?" title="Remove"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <div class="flex-1"></div>
                            <a href="{{ route('activities.submit') }}?firearm={{ $firearm->uuid }}" wire:navigate title="Log activity"
                               class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
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
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">Your Virtual Safe is empty</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Add your first firearm to start tracking licenses, expiry dates, and managing your collection.</p>
            <a href="{{ route('armoury.create') }}" wire:navigate
               class="mt-6 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Your First Firearm
            </a>
        </div>
    @endif
</div>
