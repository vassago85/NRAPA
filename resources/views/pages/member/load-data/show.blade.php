<?php

use App\Models\InventoryLog;
use App\Models\LoadData;
use App\Models\LoadingSession;
use App\Models\ReloadingInventory;
use Spatie\LaravelPdf\Facades\Pdf;
use Livewire\Component;

new class extends Component {
    public LoadData $load;

    // Load Ammo form
    public bool $showLoadAmmoForm = false;
    public ?int $ammo_rounds = null;
    public ?string $ammo_date = null;
    public string $ammo_notes = '';
    public bool $deductFromInventory = true;

    // Print Labels form
    public bool $showPrintForm = false;
    public int $label_count = 14;
    public string $label_layout = '2x7';

    public function mount(LoadData $load): void
    {
        if ($load->user_id !== auth()->id()) {
            abort(403);
        }

        $this->load = $load->load(['userFirearm', 'userFirearm.firearmCalibre', 'powderInventory', 'primerInventory', 'bulletInventory', 'brassInventory']);
        $this->ammo_date = now()->format('Y-m-d');
    }

    public function toggleFavorite(): void
    {
        $this->load->update(['is_favorite' => !$this->load->is_favorite]);
    }

    public function loadAmmo(): void
    {
        $this->validate([
            'ammo_rounds' => ['required', 'integer', 'min:1', 'max:10000'],
            'ammo_date' => ['required', 'date'],
        ]);

        // Create loading session
        LoadingSession::create([
            'user_id' => auth()->id(),
            'load_data_id' => $this->load->id,
            'rounds_loaded' => $this->ammo_rounds,
            'session_date' => $this->ammo_date,
            'notes' => $this->ammo_notes ?: null,
        ]);

        // Deduct from inventory if requested
        if ($this->deductFromInventory) {
            $this->deductInventory($this->ammo_rounds);
        }

        $this->showLoadAmmoForm = false;
        $this->ammo_rounds = null;
        $this->ammo_notes = '';

        session()->flash('success', "Loaded {$this->ammo_rounds} rounds recorded.");
    }

    private function deductInventory(int $rounds): void
    {
        $userId = auth()->id();
        $loadName = $this->load->name;
        $loadId = $this->load->id;
        $date = $this->ammo_date;

        // Deduct powder (convert grains to grams: 1 grain = 0.0648g)
        if ($this->load->powder_type) {
            $powderGrams = $rounds * ($this->load->powder_charge ?? 0) * 0.0648;
            $powderInv = ReloadingInventory::where('user_id', $userId)
                ->where('type', 'powder')
                ->where('name', 'like', '%' . $this->load->powder_type . '%')
                ->first();
            if ($powderInv && $powderGrams > 0) {
                $powderInv->decrement('quantity', $powderGrams);
                InventoryLog::record($powderInv->id, $userId, 'usage', -$powderGrams, $rounds, $loadName, $loadId, LoadData::class, null, $date);
            }
        }

        // Deduct primers
        if ($this->load->primer_type) {
            $primerInv = ReloadingInventory::where('user_id', $userId)
                ->where('type', 'primer')
                ->where('name', 'like', '%' . $this->load->primer_type . '%')
                ->first();
            if ($primerInv) {
                $primerInv->decrement('quantity', $rounds);
                InventoryLog::record($primerInv->id, $userId, 'usage', -$rounds, $rounds, $loadName, $loadId, LoadData::class, null, $date);
            }
        }

        // Deduct bullets
        if ($this->load->bullet_make) {
            $bulletInv = ReloadingInventory::where('user_id', $userId)
                ->where('type', 'bullet')
                ->where('make', 'like', '%' . $this->load->bullet_make . '%')
                ->first();
            if ($bulletInv) {
                $bulletInv->decrement('quantity', $rounds);
                InventoryLog::record($bulletInv->id, $userId, 'usage', -$rounds, $rounds, $loadName, $loadId, LoadData::class, null, $date);
            }
        }

        // Deduct brass
        if ($this->load->brass_make) {
            $brassInv = ReloadingInventory::where('user_id', $userId)
                ->where('type', 'brass')
                ->where('make', 'like', '%' . $this->load->brass_make . '%')
                ->first();
            if ($brassInv) {
                $brassInv->decrement('quantity', $rounds);
                InventoryLog::record($brassInv->id, $userId, 'usage', -$rounds, $rounds, $loadName, $loadId, LoadData::class, null, $date);
            }
        }
    }

    public function printLabels()
    {
        $this->validate([
            'label_count' => ['required', 'integer', 'min:1', 'max:56'],
            'label_layout' => ['required', 'in:2x7,single'],
        ]);

        $pdfContent = base64_decode(
            Pdf::view('documents.load-label', [
                'load' => $this->load,
                'label_count' => $this->label_count,
                'label_layout' => $this->label_layout,
            ])
            ->format('a4')
            ->portrait()
            ->base64()
        );

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, 'load-labels-' . str_replace(' ', '-', strtolower($this->load->name)) . '.pdf');
    }

    public function deleteLoad(): void
    {
        $this->load->delete();
        session()->flash('success', 'Load data deleted.');
        $this->redirect(route('load-data.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'loadingSessions' => LoadingSession::where('load_data_id', $this->load->id)
                ->orderByDesc('session_date')
                ->limit(5)
                ->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('load-data.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $load->name }}</h1>
                        @if($load->is_favorite)
                            <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $load->calibre_name ?? 'No calibre specified' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="toggleFavorite"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    @if($load->is_favorite)
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Unfavorite
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Favorite
                    @endif
                </button>
                <a href="{{ route('load-data.edit', $load) }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button wire:click="deleteLoad" wire:confirm="Are you sure you want to delete this load data?"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($load->is_max_load)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="font-medium text-red-800 dark:text-red-200">Maximum Load Warning</p>
                    @if($load->safety_notes)
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $load->safety_notes }}</p>
                    @else
                        <p class="text-sm text-red-600 dark:text-red-400">This load is marked as a maximum load. Use extreme caution and work up carefully.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Projectile -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Projectile / Bullet</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Model</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_model ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Weight</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            @if($load->bullet_weight)
                                {{ $load->bullet_weight }} gr
                                <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->bullet_weight * 0.06479891, 2) }} g)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">BC ({{ $load->bullet_bc_type ?? 'G1' }})</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->bullet_bc ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Powder & Primer -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Powder & Primer</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Powder Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->powder_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Powder Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->powder_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Charge</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            @if($load->powder_charge)
                                {{ $load->powder_charge }} gr
                                <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->powder_charge * 0.06479891, 2) }} g)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Primer Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->primer_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Primer Type</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->primer_type ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Brass & Seating -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Brass & Seating</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Brass Make</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_make ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Firings</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_firings ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Annealed</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->brass_annealed ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">COAL</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            @if($load->coal)
                                {{ $load->coal }}"
                                <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->coal * 25.4, 2) }} mm)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">CBTO</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            @if($load->cbto)
                                {{ $load->cbto }}"
                                <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->cbto * 25.4, 2) }} mm)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Jump to Lands</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            @if($load->jump_to_lands)
                                {{ $load->jump_to_lands }}"
                                <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->jump_to_lands * 25.4, 2) }} mm)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Cost Tracking -->
            @if($load->cost_per_round !== null)
                <div class="rounded-lg border border-nrapa-orange/30 bg-nrapa-orange-light dark:bg-nrapa-orange/10 dark:border-nrapa-orange/20 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Cost per Round</h2>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-extrabold text-nrapa-orange">R{{ number_format($load->cost_per_round, 2) }}</span>
                        <span class="text-sm text-zinc-500">per round</span>
                    </div>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        @if($load->bullet_price_per_unit)
                            <div class="flex justify-between col-span-2">
                                <span class="text-zinc-500">
                                    Bullet
                                    @if($load->bulletInventory)
                                        <span class="text-xs text-nrapa-blue ml-1">({{ $load->bulletInventory->display_name }})</span>
                                    @endif
                                </span>
                                <span class="text-zinc-900 dark:text-white">R{{ number_format($load->bullet_price_per_unit, 2) }}<span class="text-zinc-400 text-xs ml-1">(~R{{ number_format($load->bullet_price_per_unit * 100, 0) }}/100)</span></span>
                            </div>
                        @endif
                        @if($load->powder_price_per_kg && $load->powder_charge)
                            <div class="flex justify-between col-span-2">
                                <span class="text-zinc-500">
                                    Powder ({{ $load->powder_charge }}gr)
                                    @if($load->powderInventory)
                                        <span class="text-xs text-nrapa-blue ml-1">({{ $load->powderInventory->display_name }})</span>
                                    @endif
                                </span>
                                <span class="text-zinc-900 dark:text-white">R{{ number_format(($load->powder_price_per_kg / 1000) * ($load->powder_charge * 0.0648), 2) }}<span class="text-zinc-400 text-xs ml-1">(~R{{ number_format($load->powder_price_per_kg * 0.453592, 0) }}/lb)</span></span>
                            </div>
                        @endif
                        @if($load->primer_price_per_unit)
                            <div class="flex justify-between col-span-2">
                                <span class="text-zinc-500">
                                    Primer
                                    @if($load->primerInventory)
                                        <span class="text-xs text-nrapa-blue ml-1">({{ $load->primerInventory->display_name }})</span>
                                    @endif
                                </span>
                                <span class="text-zinc-900 dark:text-white">R{{ number_format($load->primer_price_per_unit, 2) }}<span class="text-zinc-400 text-xs ml-1">(~R{{ number_format($load->primer_price_per_unit * 100, 0) }}/100)</span></span>
                            </div>
                        @endif
                        @if($load->brass_price_per_unit)
                            @php $brassLoads = $load->brass_load_count; @endphp
                            <div class="flex justify-between col-span-2">
                                <span class="text-zinc-500">
                                    Brass (÷{{ $brassLoads }} load{{ $brassLoads > 1 ? 's' : '' }})
                                    @if($load->brassInventory)
                                        <span class="text-xs text-nrapa-blue ml-1">({{ $load->brassInventory->display_name }})</span>
                                    @endif
                                </span>
                                <span class="text-zinc-900 dark:text-white">R{{ number_format($load->brass_price_per_unit / $brassLoads, 2) }}<span class="text-zinc-400 text-xs ml-1">(R{{ number_format($load->brass_price_per_unit, 0) }}/case)</span></span>
                            </div>
                        @endif
                    </dl>
                    @if($load->powderInventory || $load->bulletInventory || $load->primerInventory || $load->brassInventory)
                        @php
                            $priceChanged = false;
                            if ($load->powderInventory && $load->powderInventory->price_for_load && $load->powder_price_per_kg) {
                                $priceChanged = $priceChanged || abs($load->powderInventory->price_for_load - $load->powder_price_per_kg) > 0.5;
                            }
                            if ($load->bulletInventory && $load->bulletInventory->price_for_load && $load->bullet_price_per_unit) {
                                $priceChanged = $priceChanged || abs($load->bulletInventory->price_for_load - $load->bullet_price_per_unit) > 0.01;
                            }
                            if ($load->primerInventory && $load->primerInventory->price_for_load && $load->primer_price_per_unit) {
                                $priceChanged = $priceChanged || abs($load->primerInventory->price_for_load - $load->primer_price_per_unit) > 0.01;
                            }
                            if ($load->brassInventory && $load->brassInventory->price_for_load && $load->brass_price_per_unit) {
                                $priceChanged = $priceChanged || abs($load->brassInventory->price_for_load - $load->brass_price_per_unit) > 0.01;
                            }
                        @endphp
                        @if($priceChanged)
                            <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                                <svg class="inline h-3.5 w-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path></svg>
                                Inventory prices have changed since this load was saved. Edit the load to update.
                            </p>
                        @endif
                    @endif
                </div>
            @endif

            <!-- Notes -->
            @if($load->notes)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notes</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ $load->notes }}</p>
                </div>
            @endif

            <!-- Loading Sessions -->
            @if($loadingSessions->count() > 0)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent Loading Sessions</h2>
                    <div class="space-y-2">
                        @foreach($loadingSessions as $session)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 dark:border-zinc-700 p-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $session->rounds_loaded }} rounds loaded</p>
                                    <p class="text-xs text-zinc-500">{{ $session->session_date->format('d M Y') }}@if($session->notes) &mdash; {{ $session->notes }}@endif</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Status</h2>
                @php $badge = $load->status_badge; @endphp
                <div class="text-center mb-4">
                    <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-medium
                        @if($badge['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($badge['color'] === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @elseif($badge['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300
                        @endif">
                        {{ $badge['text'] }}
                    </span>
                </div>

                @if($load->userFirearm)
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4">
                        <dt class="text-sm text-zinc-500 mb-1">Firearm</dt>
                        <a href="{{ route('armoury.show', $load->userFirearm) }}" wire:navigate
                           class="font-medium text-nrapa-blue hover:text-nrapa-blue-dark">
                            {{ $load->userFirearm->display_name }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Performance -->
            @if($load->muzzle_velocity || $load->velocity_sd || $load->group_size)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Performance</h2>
                    <dl class="space-y-3">
                        @if($load->muzzle_velocity)
                            <div>
                                <dt class="text-sm text-zinc-500">Muzzle Velocity</dt>
                                <dd class="text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format($load->muzzle_velocity) }} fps
                                    <span class="text-sm font-normal text-zinc-400 ml-1">({{ number_format($load->muzzle_velocity * 0.3048, 0) }} m/s)</span>
                                </dd>
                            </div>
                        @endif
                        @if($load->velocity_es)
                            <div>
                                <dt class="text-sm text-zinc-500">Extreme Spread</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">
                                    {{ $load->velocity_es }} fps
                                    <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->velocity_es * 0.3048, 1) }} m/s)</span>
                                </dd>
                            </div>
                        @endif
                        @if($load->velocity_sd)
                            <div>
                                <dt class="text-sm text-zinc-500">Standard Deviation</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">
                                    {{ $load->velocity_sd }} fps
                                    <span class="text-xs text-zinc-400 ml-1">({{ number_format($load->velocity_sd * 0.3048, 1) }} m/s)</span>
                                </dd>
                            </div>
                        @endif
                        @if($load->group_size)
                            <div>
                                <dt class="text-sm text-zinc-500">Group Size</dt>
                                <dd class="text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ $load->group_size }} {{ $load->group_size_unit === 'moa' ? 'MOA' : '"' }}
                                    @if($load->group_size_unit === 'inches')
                                        <span class="text-sm font-normal text-zinc-400 ml-1">({{ number_format($load->group_size * 25.4, 1) }} mm)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Testing Conditions -->
            @if($load->tested_date || $load->tested_distance || $load->tested_temperature)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Testing Conditions</h2>
                    <dl class="space-y-3">
                        @if($load->tested_date)
                            <div>
                                <dt class="text-sm text-zinc-500">Date</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_date->format('d M Y') }}</dd>
                            </div>
                        @endif
                        @if($load->tested_distance)
                            <div>
                                <dt class="text-sm text-zinc-500">Distance</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_distance }} {{ $load->tested_distance_unit }}</dd>
                            </div>
                        @endif
                        @if($load->tested_temperature)
                            <div>
                                <dt class="text-sm text-zinc-500">Temperature</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $load->tested_temperature }}°C</dd>
                            </div>
                        @endif
                        @if($load->tested_altitude)
                            <div>
                                <dt class="text-sm text-zinc-500">Altitude</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($load->tested_altitude) }}m</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <!-- Actions -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Actions</h2>
                <div class="space-y-2">
                    <!-- Load Ammo Button -->
                    @if(!$showLoadAmmoForm)
                        <button wire:click="$set('showLoadAmmoForm', true)"
                                class="block w-full rounded-lg bg-nrapa-orange px-4 py-2 text-center text-sm font-medium text-white hover:bg-nrapa-orange-dark">
                            Load Ammo
                        </button>
                    @else
                        <form wire:submit="loadAmmo" class="space-y-3 border border-nrapa-orange/30 rounded-lg p-3 bg-nrapa-orange-light dark:bg-nrapa-orange/5">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Record Loading Session</p>
                            <div>
                                <input type="number" wire:model="ammo_rounds" min="1" placeholder="Rounds to load"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                                @error('ammo_rounds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="date" wire:model="ammo_date"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <input type="text" wire:model="ammo_notes" placeholder="Notes (optional)"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="deductFromInventory" class="rounded border-zinc-300">
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Deduct from inventory</span>
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark">Save</button>
                                <button type="button" wire:click="$set('showLoadAmmoForm', false)" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400">Cancel</button>
                            </div>
                        </form>
                    @endif

                    <!-- Print Labels Button -->
                    @if(!$showPrintForm)
                        <button wire:click="$set('showPrintForm', true)"
                                class="block w-full rounded-lg border border-nrapa-blue text-nrapa-blue px-4 py-2 text-center text-sm font-medium hover:bg-nrapa-blue-light dark:hover:bg-nrapa-blue/10">
                            Print Labels
                        </button>
                    @else
                        <form wire:submit="printLabels" class="space-y-3 border border-nrapa-blue/30 rounded-lg p-3 bg-nrapa-blue-light dark:bg-nrapa-blue/5">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Print Ammo Labels</p>
                            <div>
                                <label class="block text-xs text-zinc-500 mb-1">Number of Labels</label>
                                <input type="number" wire:model="label_count" min="1" max="56"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs text-zinc-500 mb-1">Layout</label>
                                <select wire:model="label_layout"
                                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                                    <option value="2x7">2x7 on A4 (14 per page)</option>
                                    <option value="single">Single large label</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark">Download PDF</button>
                                <button type="button" wire:click="$set('showPrintForm', false)" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400">Cancel</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
