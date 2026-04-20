<?php

use App\Models\RoundLog;
use App\Models\ShootingActivity;
use App\Models\UserFirearm;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public UserFirearm $firearm;

    // Quick round log form
    public ?string $log_date = null;
    public ?int $log_rounds = null;
    public string $log_note = '';
    public bool $showLogForm = false;

    public function mount(UserFirearm $firearm): void
    {
        if ($firearm->user_id !== auth()->id()) {
            abort(403);
        }

        $this->firearm = $firearm->load(['firearmType', 'firearmCalibre', 'firearmMake', 'firearmModel', 'loadData']);
        $this->log_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function totalRoundsFired(): int
    {
        $activityRounds = (int) ShootingActivity::where('user_firearm_id', $this->firearm->id)
            ->where('status', 'approved')
            ->sum('rounds_fired');

        $logRounds = (int) RoundLog::where('user_firearm_id', $this->firearm->id)
            ->sum('rounds_fired');

        return $activityRounds + $logRounds;
    }

    #[Computed]
    public function recentRoundLogs()
    {
        return RoundLog::where('user_firearm_id', $this->firearm->id)
            ->orderByDesc('logged_date')
            ->limit(10)
            ->get();
    }

    public function saveRoundLog(): void
    {
        $this->validate([
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'log_rounds' => ['required', 'integer', 'min:1', 'max:10000'],
            'log_note' => ['nullable', 'string', 'max:255'],
        ]);

        RoundLog::create([
            'user_id' => auth()->id(),
            'user_firearm_id' => $this->firearm->id,
            'rounds_fired' => $this->log_rounds,
            'logged_date' => $this->log_date,
            'note' => $this->log_note ?: null,
        ]);

        $this->log_rounds = null;
        $this->log_note = '';
        $this->log_date = now()->format('Y-m-d');
        $this->showLogForm = false;

        unset($this->totalRoundsFired);
        unset($this->recentRoundLogs);

        session()->flash('round_log_success', 'Rounds logged successfully.');
    }

    public function deleteRoundLog(int $id): void
    {
        $log = RoundLog::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $log->delete();

        unset($this->totalRoundsFired);
        unset($this->recentRoundLogs);
    }

    public function deleteFirearm(): void
    {
        $this->firearm->delete();
        session()->flash('success', 'Firearm removed from your armoury.');
        // Full page reload (no navigate: true) so the soft-deleted firearm's
        // Livewire/SPA state is fully discarded before landing on the index.
        $this->redirect(route('armoury.index'));
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('armoury.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $firearm->display_name }}</h1>
            </div>
            <div class="flex items-center gap-1">
                <a href="{{ route('armoury.edit', $firearm) }}" wire:navigate title="Edit"
                   class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <button wire:click="deleteFirearm" wire:confirm="Are you sure you want to remove this firearm from your armoury?" title="Remove"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <a href="{{ route('activities.submit') }}?firearm={{ $firearm->uuid }}" wire:navigate title="Log activity"
                   class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </a>
                @if($firearm->license_document_path)
                    <a href="{{ Storage::url($firearm->license_document_path) }}" target="_blank" title="Download license"
                       class="inline-flex items-center justify-center rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- License Status Alert -->
    @if($firearm->is_expired)
        <div class="mb-6 rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">License Expired!</p>
                    <p class="text-sm text-red-600 dark:text-red-400">This firearm's license expired on {{ $firearm->license_expiry_date->format('d M Y') }}. Please renew immediately.</p>
                </div>
            </div>
        </div>
    @elseif($firearm->is_expiring_soon)
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-medium text-amber-800 dark:text-amber-200">License Expiring Soon</p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">This license expires in {{ $firearm->days_until_expiry }} days ({{ $firearm->license_expiry_date->format('d M Y') }}). Start your renewal process.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Hero Image -->
    @if($firearm->image_path)
        <div class="mb-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <img src="{{ Storage::disk('public')->url($firearm->image_path) }}" alt="{{ $firearm->display_name }}" class="w-full max-h-72 object-cover">
        </div>
    @endif

    <!-- Summary Strip -->
    @php $badge = $firearm->license_status_badge; @endphp
    <div class="mb-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <div>
                <span class="text-xs uppercase tracking-wider text-zinc-500">Make / Model</span>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $firearm->make_display ?? $firearm->make ?? '—' }} {{ $firearm->model_display ?? $firearm->model ?? '' }}</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-zinc-500">Calibre</span>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $firearm->calibre_display ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-zinc-500">Action</span>
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $firearm->action_label ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-zinc-500">Status</span>
                <p class="mt-0.5">
                    <span class="text-xs px-2 py-1 rounded-full font-medium
                        @if($badge['color'] === 'green') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                        @elseif($badge['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                        @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 @endif">
                        {{ $badge['text'] }}
                    </span>
                </p>
            </div>
            @if($firearm->license_expiry_date)
                <div>
                    <span class="text-xs uppercase tracking-wider text-zinc-500">Expires</span>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $firearm->license_expiry_date->format('d M Y') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Basic Information</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Make</dt>
                        <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $firearm->make_display ?? $firearm->make ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Model</dt>
                        <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $firearm->model_display ?? $firearm->model ?? 'Not specified' }}</dd>
                    </div>
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
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-zinc-500">Serial Number</dt>
                        <dd class="text-sm text-zinc-800 dark:text-zinc-200">{{ $firearm->primary_serial ?? 'Not specified' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Component Serials (SAPS 271) -->
            @php $components = $firearm->components()->get(); @endphp
            @if($components->count() > 0)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Component Serials</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        @foreach($components as $component)
                            @if($component->serial)
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-zinc-500">{{ ucfirst($component->type) }}</dt>
                                    <dd class="text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $component->serial }}
                                        @if($component->make) <span class="text-zinc-400">({{ $component->make }})</span> @endif
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            @endif

            <!-- Barrel & Stock -->
            @if($firearm->barrel_length || $firearm->barrel_twist || $firearm->barrel_profile || $firearm->stock_type || $firearm->stock_make)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Barrel & Stock</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        @if($firearm->barrel_length)
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-zinc-500">Barrel Length</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_length }}</dd>
                            </div>
                        @endif
                        @if($firearm->barrel_twist)
                            <div>
                                <dt class="text-sm text-zinc-500">Barrel Twist</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_twist }}</dd>
                            </div>
                        @endif
                        @if($firearm->barrel_profile)
                            <div>
                                <dt class="text-sm text-zinc-500">Barrel Profile</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->barrel_profile }}</dd>
                            </div>
                        @endif
                        @if($firearm->stock_type)
                            <div>
                                <dt class="text-sm text-zinc-500">Stock Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->stock_type }}</dd>
                            </div>
                        @endif
                        @if($firearm->stock_make)
                            <div>
                                <dt class="text-sm text-zinc-500">Stock Make</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->stock_make }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Optics -->
            @if($firearm->scope_make || $firearm->scope_model || $firearm->scope_magnification)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Optics</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        @if($firearm->scope_make)
                            <div>
                                <dt class="text-sm text-zinc-500">Scope Make</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_make }}</dd>
                            </div>
                        @endif
                        @if($firearm->scope_model)
                            <div>
                                <dt class="text-sm text-zinc-500">Scope Model</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_model }}</dd>
                            </div>
                        @endif
                        @if($firearm->scope_magnification)
                            <div>
                                <dt class="text-sm text-zinc-500">Magnification</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->scope_magnification }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Load Data -->
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Load Data</h2>
                    <a href="{{ route('load-data.create') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="text-sm font-medium text-nrapa-blue hover:text-nrapa-blue-dark">
                        + Add Load
                    </a>
                </div>
                @if($firearm->loadData->count() > 0)
                    <div class="space-y-3">
                        @foreach($firearm->loadData as $load)
                            <a href="{{ route('load-data.show', $load) }}" wire:navigate
                               class="block rounded-lg border border-zinc-200 dark:border-zinc-600 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $load->name }}</p>
                                        <p class="text-sm text-zinc-500">{{ $load->bullet_description }}</p>
                                    </div>
                                    @php $loadBadge = $load->status_badge; @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if($loadBadge['color'] === 'green') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                                        @elseif($loadBadge['color'] === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($loadBadge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200
                                        @endif">
                                        {{ $loadBadge['text'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500">No load data recorded yet.</p>
                @endif
            </div>

            <!-- Notes -->
            @if($firearm->notes)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notes</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ $firearm->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- License Status Card -->
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">License Status</h2>
                <div class="text-center mb-4">
                    <span class="text-xs px-3 py-1.5 rounded-full font-medium
                        @if($badge['color'] === 'green') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                        @elseif($badge['color'] === 'red') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                        @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 @endif">
                        {{ $badge['text'] }}
                    </span>
                </div>
                <dl class="space-y-3">
                    @if($firearm->license_number)
                        <div>
                            <dt class="text-sm text-zinc-500">License Number</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_number }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_type)
                        <div>
                            <dt class="text-sm text-zinc-500">License Type</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_type_label }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_issue_date)
                        <div>
                            <dt class="text-sm text-zinc-500">Issue Date</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_issue_date->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($firearm->license_expiry_date)
                        <div>
                            <dt class="text-sm text-zinc-500">Expiry Date</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $firearm->license_expiry_date->format('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Barrel Life -->
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Barrel Life</h2>
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-nrapa-blue">{{ number_format($this->totalRoundsFired) }}</p>
                    <p class="mt-1 text-sm text-zinc-500">rounds through barrel</p>
                </div>
                <p class="mt-4 text-xs text-zinc-400 text-center">Based on approved activities and manual logs</p>

                <!-- Quick Log Rounds Toggle -->
                <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    @if(session('round_log_success'))
                        <div class="mb-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300">
                            {{ session('round_log_success') }}
                        </div>
                    @endif

                    @if(!$showLogForm)
                        <button wire:click="$set('showLogForm', true)"
                                class="w-full rounded-lg bg-nrapa-orange px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-orange-dark transition-colors">
                            + Log Rounds
                        </button>
                    @else
                        <form wire:submit="saveRoundLog" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Date</label>
                                <input type="date" wire:model="log_date"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                                @error('log_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Rounds Fired</label>
                                <input type="number" wire:model="log_rounds" min="1" placeholder="e.g., 50"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                                @error('log_rounds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Note (optional)</label>
                                <input type="text" wire:model="log_note" placeholder="e.g., Range practice"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                        class="flex-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                                    Save
                                </button>
                                <button type="button" wire:click="$set('showLogForm', false)"
                                        class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Recent Round Logs -->
            @if($this->recentRoundLogs->count() > 0)
                <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent Round Logs</h2>
                    <div class="space-y-2">
                        @foreach($this->recentRoundLogs as $log)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 dark:border-zinc-700 p-2">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $log->rounds_fired }} rounds</p>
                                    <p class="text-xs text-zinc-500">{{ $log->logged_date->format('d M Y') }}@if($log->note) &mdash; {{ $log->note }}@endif</p>
                                </div>
                                <button wire:click="deleteRoundLog({{ $log->id }})" wire:confirm="Delete this round log?"
                                        class="text-zinc-400 hover:text-red-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('activities.submit') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Log Activity with this Firearm
                    </a>
                    <a href="{{ route('load-data.create') }}?firearm={{ $firearm->uuid }}" wire:navigate
                       class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-center text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        Add Load Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
