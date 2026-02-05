<?php

use App\Models\LoadData;
use App\Models\UserFirearm;
use App\Models\Calibre;
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
        $this->is_favorite = $load->is_favorite;
        $this->is_max_load = $load->is_max_load;
        $this->notes = $load->notes ?? '';
        $this->safety_notes = $load->safety_notes ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_firearm_id' => ['nullable', 'exists:user_firearms,id'],
            'calibre_id' => ['nullable', 'exists:calibres,id'],
            'status' => ['required', 'in:development,tested,approved,retired'],
        ]);

        $this->load->update([
            'user_firearm_id' => $this->user_firearm_id,
            'calibre_id' => $this->calibre_id,
            'name' => $this->name,
            'status' => $this->status,
            'bullet_make' => $this->bullet_make ?: null,
            'bullet_model' => $this->bullet_model ?: null,
            'bullet_weight' => $this->bullet_weight,
            'bullet_bc' => $this->bullet_bc,
            'bullet_type' => $this->bullet_type ?: null,
            'powder_make' => $this->powder_make ?: null,
            'powder_type' => $this->powder_type ?: null,
            'powder_charge' => $this->powder_charge,
            'primer_make' => $this->primer_make ?: null,
            'primer_type' => $this->primer_type ?: null,
            'brass_make' => $this->brass_make ?: null,
            'brass_firings' => $this->brass_firings,
            'brass_annealed' => $this->brass_annealed,
            'coal' => $this->coal,
            'cbto' => $this->cbto,
            'jump_to_lands' => $this->jump_to_lands,
            'muzzle_velocity' => $this->muzzle_velocity,
            'velocity_es' => $this->velocity_es,
            'velocity_sd' => $this->velocity_sd,
            'group_size' => $this->group_size,
            'group_size_unit' => $this->group_size_unit,
            'tested_date' => $this->tested_date ?: null,
            'tested_distance' => $this->tested_distance,
            'tested_distance_unit' => $this->tested_distance_unit,
            'tested_temperature' => $this->tested_temperature,
            'tested_altitude' => $this->tested_altitude,
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
        return [
            'firearms' => UserFirearm::forUser(auth()->id())->active()->with(['firearmCalibre', 'firearmMake', 'firearmModel'])->get(),
            'calibres' => FirearmCalibre::active()->ordered()->get(),
            'bulletTypes' => LoadData::bulletTypes(),
            'statuses' => LoadData::statuses(),
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
                    <select wire:model="user_firearm_id"
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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Weight (gr)</label>
                    <input type="number" wire:model="bullet_weight" step="0.1"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">BC (G1)</label>
                    <input type="number" wire:model="bullet_bc" step="0.001"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Charge (gr)</label>
                    <input type="number" wire:model="powder_charge" step="0.1"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
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

        <!-- Performance -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Performance Data</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Velocity (fps)</label>
                    <input type="number" wire:model="muzzle_velocity"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">ES (fps)</label>
                    <input type="number" wire:model="velocity_es"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SD (fps)</label>
                    <input type="number" wire:model="velocity_sd"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Group Size</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="group_size" step="0.01"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <select wire:model="group_size_unit"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="moa">MOA</option>
                            <option value="inches">inches</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

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
                    class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Save Changes
            </button>
        </div>
    </form>
</div>
