<?php

use App\Models\LoadData;
use App\Models\UserFirearm;
use App\Models\FirearmCalibre;
use App\Models\ReloadingInventory;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public LoadData $load;

    // Basic
    public string $name = '';
    public ?int $user_firearm_id = null;
    public ?int $calibre_id = null;
    public string $status = 'development';

    // Bullet
    public string $bullet_make = '';
    public string $bullet_model = '';
    public ?float $bullet_weight = null;
    public ?float $bullet_bc = null;
    public string $bullet_bc_type = 'G1';
    public string $bullet_type = '';

    // Powder
    public string $powder_make = '';
    public string $powder_type = '';
    public ?float $powder_charge = null;

    // Primer
    public string $primer_make = '';
    public string $primer_type = '';

    // Brass
    public string $brass_make = '';
    public ?int $brass_firings = null;
    public bool $brass_annealed = false;

    // Seating
    public ?float $coal = null;
    public ?float $cbto = null;
    public ?float $jump_to_lands = null;

    // Performance
    public ?int $muzzle_velocity = null;
    public ?int $velocity_es = null;
    public ?int $velocity_sd = null;
    public ?float $group_size = null;
    public string $group_size_unit = 'moa';

    // Testing
    public ?string $tested_date = null;
    public ?int $tested_distance = null;
    public string $tested_distance_unit = 'meters';
    public ?float $tested_temperature = null;
    public ?int $tested_altitude = null;

    // Inventory links
    public ?int $powder_inventory_id = null;
    public ?int $primer_inventory_id = null;
    public ?int $bullet_inventory_id = null;
    public ?int $brass_inventory_id = null;

    // Unit preferences (display only — DB stores canonical: grains, inches, fps)
    public string $weight_unit = 'gr';      // gr (grains) or g (grams)
    public string $seating_unit = 'in';     // in (inches) or mm
    public string $velocity_unit = 'fps';   // fps or ms (m/s)

    // Cost tracking (snapshot)
    public ?float $powder_price_per_kg = null;
    public ?float $primer_price_per_unit = null;
    public ?float $bullet_price_per_unit = null;
    public ?float $brass_price_per_unit = null;

    // Flags
    public bool $is_favorite = false;
    public bool $is_max_load = false;
    public string $notes = '';
    public string $safety_notes = '';

    public function mount(LoadData $load): void
    {
        if ($load->user_id !== auth()->id()) {
            abort(403);
        }

        $this->load = $load;
        $this->name = $load->name;
        $this->user_firearm_id = $load->user_firearm_id;
        $this->calibre_id = $load->calibre_id;
        $this->status = $load->status;
        $this->bullet_make = $load->bullet_make ?? '';
        $this->bullet_model = $load->bullet_model ?? '';
        $this->bullet_weight = $load->bullet_weight;
        $this->bullet_bc = $load->bullet_bc;
        $this->bullet_bc_type = $load->bullet_bc_type ?? 'G1';
        $this->bullet_type = $load->bullet_type ?? '';
        $this->powder_make = $load->powder_make ?? '';
        $this->powder_type = $load->powder_type ?? '';
        $this->powder_charge = $load->powder_charge;
        $this->primer_make = $load->primer_make ?? '';
        $this->primer_type = $load->primer_type ?? '';
        $this->brass_make = $load->brass_make ?? '';
        $this->brass_firings = $load->brass_firings;
        $this->brass_annealed = $load->brass_annealed;
        $this->coal = $load->coal;
        $this->cbto = $load->cbto;
        $this->jump_to_lands = $load->jump_to_lands;
        $this->muzzle_velocity = $load->muzzle_velocity;
        $this->velocity_es = $load->velocity_es;
        $this->velocity_sd = $load->velocity_sd;
        $this->group_size = $load->group_size;
        $this->group_size_unit = $load->group_size_unit ?? 'moa';
        $this->tested_date = $load->tested_date?->format('Y-m-d');
        $this->tested_distance = $load->tested_distance;
        $this->tested_distance_unit = $load->tested_distance_unit ?? 'meters';
        $this->tested_temperature = $load->tested_temperature;
        $this->tested_altitude = $load->tested_altitude;
        $this->powder_price_per_kg = $load->powder_price_per_kg;
        $this->primer_price_per_unit = $load->primer_price_per_unit;
        $this->bullet_price_per_unit = $load->bullet_price_per_unit;
        $this->brass_price_per_unit = $load->brass_price_per_unit;
        $this->powder_inventory_id = $load->powder_inventory_id;
        $this->primer_inventory_id = $load->primer_inventory_id;
        $this->bullet_inventory_id = $load->bullet_inventory_id;
        $this->brass_inventory_id = $load->brass_inventory_id;
        $this->is_favorite = $load->is_favorite;
        $this->is_max_load = $load->is_max_load;
        $this->notes = $load->notes ?? '';
        $this->safety_notes = $load->safety_notes ?? '';
    }

    public function updatedUserFirearmId($value): void
    {
        if ($value) {
            $firearm = UserFirearm::where('id', $value)->where('user_id', auth()->id())->first();
            if ($firearm && $firearm->firearm_calibre_id) {
                $this->calibre_id = $firearm->firearm_calibre_id;
            }
        } else {
            $this->calibre_id = null;
        }
    }

    public function updatedPowderInventoryId($value): void
    {
        if ($value) {
            $inv = ReloadingInventory::find($value);
            if ($inv) {
                $this->powder_make = $inv->make;
                $this->powder_type = $inv->name;
                $this->powder_price_per_kg = $inv->price_for_load;
            }
        }
    }

    public function updatedPrimerInventoryId($value): void
    {
        if ($value) {
            $inv = ReloadingInventory::find($value);
            if ($inv) {
                $this->primer_make = $inv->make;
                $this->primer_type = $inv->name;
                $this->primer_price_per_unit = $inv->price_for_load;
            }
        }
    }

    // --- Unit conversion helpers ---
    private function grToG(?float $v): ?float { return $v !== null ? round($v * 0.06479891, 2) : null; }
    private function gToGr(?float $v): ?float { return $v !== null ? round($v / 0.06479891, 1) : null; }
    private function inToMm(?float $v): ?float { return $v !== null ? round($v * 25.4, 3) : null; }
    private function mmToIn(?float $v): ?float { return $v !== null ? round($v / 25.4, 4) : null; }
    private function fpsToMs($v) { return $v !== null ? round($v * 0.3048, 1) : null; }
    private function msToFps($v) { return $v !== null ? (int) round($v / 0.3048) : null; }

    public function updatedWeightUnit($value): void
    {
        if ($value === 'g') {
            $this->bullet_weight = $this->grToG($this->bullet_weight);
            $this->powder_charge = $this->grToG($this->powder_charge);
        } else {
            $this->bullet_weight = $this->gToGr($this->bullet_weight);
            $this->powder_charge = $this->gToGr($this->powder_charge);
        }
    }

    public function updatedSeatingUnit($value): void
    {
        if ($value === 'mm') {
            $this->coal = $this->inToMm($this->coal);
            $this->cbto = $this->inToMm($this->cbto);
            $this->jump_to_lands = $this->inToMm($this->jump_to_lands);
        } else {
            $this->coal = $this->mmToIn($this->coal);
            $this->cbto = $this->mmToIn($this->cbto);
            $this->jump_to_lands = $this->mmToIn($this->jump_to_lands);
        }
    }

    public function updatedVelocityUnit($value): void
    {
        if ($value === 'ms') {
            $this->muzzle_velocity = $this->fpsToMs($this->muzzle_velocity);
            $this->velocity_es = $this->fpsToMs($this->velocity_es);
            $this->velocity_sd = $this->fpsToMs($this->velocity_sd);
        } else {
            $this->muzzle_velocity = $this->msToFps($this->muzzle_velocity);
            $this->velocity_es = $this->msToFps($this->velocity_es);
            $this->velocity_sd = $this->msToFps($this->velocity_sd);
        }
    }

    public function updatedBulletInventoryId($value): void
    {
        if ($value) {
            $inv = ReloadingInventory::find($value);
            if ($inv) {
                $this->bullet_make = $inv->make;
                $this->bullet_model = $inv->name;
                $this->bullet_price_per_unit = $inv->price_for_load;
                // Auto-fill bullet details from inventory (inventory stores grains)
                if ($inv->bullet_weight) {
                    $this->bullet_weight = $this->weight_unit === 'g'
                        ? $this->grToG($inv->bullet_weight)
                        : $inv->bullet_weight;
                }
                if ($inv->bullet_bc) {
                    $this->bullet_bc = $inv->bullet_bc;
                    $this->bullet_bc_type = $inv->bullet_bc_type ?? 'G1';
                }
                if ($inv->bullet_type) {
                    $this->bullet_type = $inv->bullet_type;
                }
            }
        }
    }

    public function updatedBrassInventoryId($value): void
    {
        if ($value) {
            $inv = ReloadingInventory::find($value);
            if ($inv) {
                $this->brass_make = $inv->make . ' ' . $inv->name;
                $this->brass_price_per_unit = $inv->price_for_load;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_firearm_id' => ['nullable', Rule::exists('user_firearms', 'id')->where('user_id', auth()->id())],
            'calibre_id' => ['nullable', 'exists:firearm_calibres,id'],
            'status' => ['required', 'in:development,tested,approved,retired'],
            'powder_inventory_id' => ['nullable', Rule::exists('reloading_inventories', 'id')->where('user_id', auth()->id())],
            'primer_inventory_id' => ['nullable', Rule::exists('reloading_inventories', 'id')->where('user_id', auth()->id())],
            'bullet_inventory_id' => ['nullable', Rule::exists('reloading_inventories', 'id')->where('user_id', auth()->id())],
            'brass_inventory_id' => ['nullable', Rule::exists('reloading_inventories', 'id')->where('user_id', auth()->id())],
        ]);

        // Convert display values to canonical units (grains, inches, fps) for storage
        $bulletWeight = $this->bullet_weight;
        $powderCharge = $this->powder_charge;
        if ($this->weight_unit === 'g') {
            $bulletWeight = $this->gToGr($bulletWeight);
            $powderCharge = $this->gToGr($powderCharge);
        }

        $coal = $this->coal;
        $cbto = $this->cbto;
        $jumpToLands = $this->jump_to_lands;
        if ($this->seating_unit === 'mm') {
            $coal = $this->mmToIn($coal);
            $cbto = $this->mmToIn($cbto);
            $jumpToLands = $this->mmToIn($jumpToLands);
        }

        $mv = $this->muzzle_velocity;
        $es = $this->velocity_es;
        $sd = $this->velocity_sd;
        if ($this->velocity_unit === 'ms') {
            $mv = $this->msToFps($mv);
            $es = $this->msToFps($es);
            $sd = $this->msToFps($sd);
        }

        // Convert group size mm to inches for storage
        $groupSize = $this->group_size;
        $groupSizeUnit = $this->group_size_unit;
        if ($groupSizeUnit === 'mm') {
            $groupSize = $this->mmToIn($groupSize);
            $groupSizeUnit = 'inches';
        }

        $this->load->update([
            'user_firearm_id' => $this->user_firearm_id,
            'calibre_id' => $this->calibre_id,
            'name' => $this->name,
            'status' => $this->status,
            'bullet_make' => $this->bullet_make ?: null,
            'bullet_model' => $this->bullet_model ?: null,
            'bullet_weight' => $bulletWeight,
            'bullet_bc' => $this->bullet_bc,
            'bullet_bc_type' => $this->bullet_bc_type,
            'bullet_type' => $this->bullet_type ?: null,
            'powder_make' => $this->powder_make ?: null,
            'powder_type' => $this->powder_type ?: null,
            'powder_charge' => $powderCharge,
            'primer_make' => $this->primer_make ?: null,
            'primer_type' => $this->primer_type ?: null,
            'brass_make' => $this->brass_make ?: null,
            'brass_firings' => $this->brass_firings,
            'brass_annealed' => $this->brass_annealed,
            'coal' => $coal,
            'cbto' => $cbto,
            'jump_to_lands' => $jumpToLands,
            'muzzle_velocity' => $mv,
            'velocity_es' => $es,
            'velocity_sd' => $sd,
            'group_size' => $groupSize,
            'group_size_unit' => $groupSizeUnit,
            'tested_date' => $this->tested_date ?: null,
            'tested_distance' => $this->tested_distance,
            'tested_distance_unit' => $this->tested_distance_unit,
            'tested_temperature' => $this->tested_temperature,
            'tested_altitude' => $this->tested_altitude,
            'powder_price_per_kg' => $this->powder_price_per_kg,
            'primer_price_per_unit' => $this->primer_price_per_unit,
            'bullet_price_per_unit' => $this->bullet_price_per_unit,
            'brass_price_per_unit' => $this->brass_price_per_unit,
            'powder_inventory_id' => $this->powder_inventory_id,
            'primer_inventory_id' => $this->primer_inventory_id,
            'bullet_inventory_id' => $this->bullet_inventory_id,
            'brass_inventory_id' => $this->brass_inventory_id,
            'is_favorite' => $this->is_favorite,
            'is_max_load' => $this->is_max_load,
            'notes' => $this->notes ?: null,
            'safety_notes' => $this->safety_notes ?: null,
        ]);

        session()->flash('success', 'Load data updated successfully.');
        $this->redirect(route('load-data.show', $this->load), navigate: true);
    }

    public function with(): array
    {
        $userId = auth()->id();
        return [
            'firearms' => UserFirearm::forUser($userId)->active()->with(['firearmCalibre', 'firearmMake', 'firearmModel'])->get(),
            'calibres' => FirearmCalibre::active()->ordered()->get(),
            'bulletTypes' => LoadData::bulletTypes(),
            'statuses' => LoadData::statuses(),
            'powderInventory' => ReloadingInventory::forUser($userId)->ofType('powder')->orderBy('make')->orderBy('name')->get(),
            'primerInventory' => ReloadingInventory::forUser($userId)->ofType('primer')->orderBy('make')->orderBy('name')->get(),
            'bulletInventory' => ReloadingInventory::forUser($userId)->ofType('bullet')->orderBy('make')->orderBy('name')->get(),
            'brassInventory' => ReloadingInventory::forUser($userId)->ofType('brass')->orderBy('make')->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('load-data.show', $load) }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Edit Load Data</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $load->name }}</p>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Load Name *</label>
                    <input type="text" wire:model="name" placeholder="e.g., Match Load #1"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm</label>
                    <select wire:model.live="user_firearm_id"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select firearm...</option>
                        @foreach($firearms as $firearm)
                            <option value="{{ $firearm->id }}">{{ $firearm->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                    <select wire:model="calibre_id"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select calibre...</option>
                        @foreach($calibres as $calibre)
                            <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model="status"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Projectile -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Projectile / Bullet</h2>
            @if($bulletInventory->count() > 0)
                <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <label class="block text-xs font-medium text-nrapa-blue mb-1">Select from inventory</label>
                    <select wire:model.live="bullet_inventory_id"
                            class="w-full rounded-lg border border-nrapa-blue/30 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">-- Manual entry --</option>
                        @foreach($bulletInventory as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->load_dropdown_label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                    <input type="text" wire:model="bullet_make" placeholder="e.g., Sierra"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                    <input type="text" wire:model="bullet_model" placeholder="e.g., MatchKing"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Weight</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="bullet_weight" step="0.1"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model.live="weight_unit"
                                class="w-16 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="gr">gr</option>
                            <option value="g">g</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ballistic Coefficient</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="bullet_bc" step="0.001"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model="bullet_bc_type"
                                class="w-20 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="G1">G1</option>
                            <option value="G7">G7</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type</label>
                    <select wire:model="bullet_type"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select...</option>
                        @foreach($bulletTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Powder & Primer -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Powder & Primer</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                @if($powderInventory->count() > 0)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                        <label class="block text-xs font-medium text-nrapa-blue mb-1">Powder from inventory</label>
                        <select wire:model.live="powder_inventory_id"
                                class="w-full rounded-lg border border-nrapa-blue/30 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">-- Manual entry --</option>
                            @foreach($powderInventory as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->load_dropdown_label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if($primerInventory->count() > 0)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                        <label class="block text-xs font-medium text-nrapa-blue mb-1">Primer from inventory</label>
                        <select wire:model.live="primer_inventory_id"
                                class="w-full rounded-lg border border-nrapa-blue/30 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">-- Manual entry --</option>
                            @foreach($primerInventory as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->load_dropdown_label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Powder Make</label>
                    <input type="text" wire:model="powder_make"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Powder Type</label>
                    <input type="text" wire:model="powder_type"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Charge</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="powder_charge" step="0.1"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <span class="inline-flex items-center px-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $weight_unit === 'gr' ? 'gr' : 'g' }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Primer Make</label>
                    <input type="text" wire:model="primer_make"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Primer Type</label>
                    <input type="text" wire:model="primer_type"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Brass & Seating -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Brass & Seating</h2>
            @if($brassInventory->count() > 0)
                <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <label class="block text-xs font-medium text-nrapa-blue mb-1">Brass from inventory</label>
                    <select wire:model.live="brass_inventory_id"
                            class="w-full rounded-lg border border-nrapa-blue/30 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">-- Manual entry --</option>
                        @foreach($brassInventory as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->load_dropdown_label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Brass Make</label>
                    <input type="text" wire:model="brass_make" placeholder="e.g., Lapua, Norma, ADG"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firings</label>
                    <input type="number" wire:model="brass_firings" placeholder="e.g., 3"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="brass_annealed" class="rounded border-zinc-300">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Brass Annealed</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">COAL</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="coal" step="{{ $seating_unit === 'mm' ? '0.01' : '0.001' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model.live="seating_unit"
                                class="w-16 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="in">in</option>
                            <option value="mm">mm</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">CBTO</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="cbto" step="{{ $seating_unit === 'mm' ? '0.01' : '0.001' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <span class="inline-flex items-center px-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $seating_unit }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Jump to Lands</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="jump_to_lands" step="{{ $seating_unit === 'mm' ? '0.01' : '0.001' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <span class="inline-flex items-center px-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $seating_unit }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Performance Data</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Muzzle Velocity</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="muzzle_velocity" step="{{ $velocity_unit === 'ms' ? '0.1' : '1' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model.live="velocity_unit"
                                class="w-16 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="fps">fps</option>
                            <option value="ms">m/s</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ES</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="velocity_es" step="{{ $velocity_unit === 'ms' ? '0.1' : '1' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <span class="inline-flex items-center px-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $velocity_unit === 'fps' ? 'fps' : 'm/s' }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SD</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="velocity_sd" step="{{ $velocity_unit === 'ms' ? '0.1' : '1' }}"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <span class="inline-flex items-center px-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $velocity_unit === 'fps' ? 'fps' : 'm/s' }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Group Size</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="group_size" step="0.01"
                               class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model="group_size_unit"
                                class="w-16 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="moa">MOA</option>
                            <option value="inches">in</option>
                            <option value="mm">mm</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Summary (auto-filled from inventory or manual) -->
        @if($powder_price_per_kg || $primer_price_per_unit || $bullet_price_per_unit || $brass_price_per_unit)
            <div class="rounded-lg border border-nrapa-orange/30 bg-nrapa-orange-light dark:bg-nrapa-orange/5 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Cost Preview</h2>
                <p class="text-xs text-zinc-500 mb-3">Prices pulled from inventory selections above.</p>
                <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                    @if($powder_price_per_kg)
                        <div class="rounded-lg bg-white dark:bg-zinc-800 p-3 text-center">
                            <p class="text-xs text-zinc-500">Powder</p>
                            <p class="font-bold text-nrapa-orange">~R{{ number_format($powder_price_per_kg * 0.453592, 0) }}/lb</p>
                        </div>
                    @endif
                    @if($primer_price_per_unit)
                        <div class="rounded-lg bg-white dark:bg-zinc-800 p-3 text-center">
                            <p class="text-xs text-zinc-500">Primer</p>
                            <p class="font-bold text-nrapa-orange">R{{ number_format($primer_price_per_unit * 100, 0) }}/100</p>
                        </div>
                    @endif
                    @if($bullet_price_per_unit)
                        <div class="rounded-lg bg-white dark:bg-zinc-800 p-3 text-center">
                            <p class="text-xs text-zinc-500">Bullet</p>
                            <p class="font-bold text-nrapa-orange">R{{ number_format($bullet_price_per_unit * 100, 0) }}/100</p>
                        </div>
                    @endif
                    @if($brass_price_per_unit)
                        <div class="rounded-lg bg-white dark:bg-zinc-800 p-3 text-center">
                            <p class="text-xs text-zinc-500">Brass</p>
                            <p class="font-bold text-nrapa-orange">R{{ number_format($brass_price_per_unit * 50, 0) }}/50</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Flags & Notes -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Flags & Notes</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_favorite" class="rounded border-zinc-300">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">⭐ Favorite</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_max_load" class="rounded border-zinc-300 text-red-600">
                        <span class="text-sm text-red-600">⚠️ Maximum Load</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3"
                              class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                </div>

                @if($is_max_load)
                    <div>
                        <label class="block text-sm font-medium text-red-700 dark:text-red-400 mb-1">Safety Notes</label>
                        <textarea wire:model="safety_notes" rows="2"
                                  class="w-full rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                    </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('load-data.show', $load) }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Save Changes
            </button>
        </div>
    </form>
</div>
