<?php

use App\Models\LadderTest;
use App\Models\LadderTestStep;
use App\Models\LoadData;
use App\Models\ReloadingInventory;
use App\Models\UserFirearm;
use Livewire\Component;

new class extends Component {
    public ?int $load_data_id = null;
    public ?int $user_firearm_id = null;
    public string $name = '';
    public string $calibre = '';
    public string $bullet_make = '';
    public ?float $bullet_weight = null;
    public string $bullet_type = '';
    public string $powder_type = '';
    public string $primer_type = '';
    public ?float $start_charge = null;
    public ?float $end_charge = null;
    public float $increment = 0.3;
    public int $rounds_per_step = 3;
    public string $notes = '';
    public bool $deductFromInventory = false;

    public function mount(): void
    {
        $loadId = request()->query('load');
        if ($loadId) {
            $load = LoadData::where('uuid', $loadId)->where('user_id', auth()->id())->first();
            if ($load) {
                $this->load_data_id = $load->id;
                $this->user_firearm_id = $load->user_firearm_id;
                $this->calibre = $load->calibre_name ?? '';
                $this->bullet_make = $load->bullet_make ?? '';
                $this->bullet_weight = $load->bullet_weight;
                $this->bullet_type = $load->bullet_type ?? '';
                $this->powder_type = $load->powder_type ?? '';
                $this->primer_type = $load->primer_type ?? '';
                $this->name = 'Ladder Test - ' . $load->name;
                if ($load->powder_charge) {
                    $this->start_charge = round($load->powder_charge - 1.5, 1);
                    $this->end_charge = round($load->powder_charge + 1.5, 1);
                }
            }
        }
    }

    public function updatedLoadDataId($value): void
    {
        if ($value) {
            $load = LoadData::find($value);
            if ($load) {
                $this->user_firearm_id = $load->user_firearm_id;
                $this->calibre = $load->calibre_name ?? '';
                $this->bullet_make = $load->bullet_make ?? '';
                $this->bullet_weight = $load->bullet_weight;
                $this->bullet_type = $load->bullet_type ?? '';
                $this->powder_type = $load->powder_type ?? '';
                $this->primer_type = $load->primer_type ?? '';
            }
        }
    }

    public function getPreviewStepsProperty(): array
    {
        if (!$this->start_charge || !$this->end_charge || !$this->increment) {
            return [];
        }
        return LadderTest::generateSteps($this->start_charge, $this->end_charge, $this->increment);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_charge' => ['required', 'numeric', 'min:0.1'],
            'end_charge' => ['required', 'numeric', 'gt:start_charge'],
            'increment' => ['required', 'numeric', 'min:0.1', 'max:5'],
            'rounds_per_step' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $test = LadderTest::create([
            'user_id' => auth()->id(),
            'load_data_id' => $this->load_data_id,
            'user_firearm_id' => $this->user_firearm_id,
            'name' => $this->name,
            'calibre' => $this->calibre ?: null,
            'bullet_make' => $this->bullet_make ?: null,
            'bullet_weight' => $this->bullet_weight,
            'bullet_type' => $this->bullet_type ?: null,
            'powder_type' => $this->powder_type ?: null,
            'primer_type' => $this->primer_type ?: null,
            'start_charge' => $this->start_charge,
            'end_charge' => $this->end_charge,
            'increment' => $this->increment,
            'rounds_per_step' => $this->rounds_per_step,
            'notes' => $this->notes ?: null,
        ]);

        // Generate steps
        $steps = LadderTest::generateSteps($this->start_charge, $this->end_charge, $this->increment);
        foreach ($steps as $step) {
            LadderTestStep::create([
                'ladder_test_id' => $test->id,
                'step_number' => $step['step_number'],
                'charge_weight' => $step['charge_weight'],
            ]);
        }

        // Deduct from inventory if requested
        if ($this->deductFromInventory) {
            $totalRounds = count($steps) * $this->rounds_per_step;
            $userId = auth()->id();

            if ($this->powder_type) {
                $avgCharge = ($this->start_charge + $this->end_charge) / 2;
                $powderGrams = $totalRounds * $avgCharge * 0.0648;
                $powderInv = ReloadingInventory::where('user_id', $userId)->where('type', 'powder')
                    ->where('name', 'like', '%' . $this->powder_type . '%')->first();
                if ($powderInv) $powderInv->decrement('quantity', $powderGrams);
            }
            if ($this->primer_type) {
                $primerInv = ReloadingInventory::where('user_id', $userId)->where('type', 'primer')
                    ->where('name', 'like', '%' . $this->primer_type . '%')->first();
                if ($primerInv) $primerInv->decrement('quantity', $totalRounds);
            }
            if ($this->bullet_make) {
                $bulletInv = ReloadingInventory::where('user_id', $userId)->where('type', 'bullet')
                    ->where('make', 'like', '%' . $this->bullet_make . '%')->first();
                if ($bulletInv) $bulletInv->decrement('quantity', $totalRounds);
            }
        }

        session()->flash('success', 'Ladder test created with ' . count($steps) . ' steps.');
        $this->redirect(route('ladder-test.show', $test), navigate: true);
    }

    public function with(): array
    {
        return [
            'firearms' => UserFirearm::forUser(auth()->id())->active()->with('firearmCalibre')->get(),
            'loads' => LoadData::forUser(auth()->id())->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('ladder-test.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">New Ladder Test</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Set up an incremental charge series for load development</p>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save" class="space-y-8">
        <!-- Base Load -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Base Information</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Test Name *</label>
                    <input type="text" wire:model="name" placeholder="e.g., H4350 Ladder Test"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Base Load Recipe (optional)</label>
                    <select wire:model.live="load_data_id"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select existing load...</option>
                        @foreach($loads as $l)
                            <option value="{{ $l->id }}">{{ $l->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm</label>
                    <select wire:model="user_firearm_id"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select firearm...</option>
                        @foreach($firearms as $f)
                            <option value="{{ $f->id }}">{{ $f->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                    <input type="text" wire:model="calibre" placeholder="e.g., .308 Win"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Components -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Components</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bullet Make</label>
                    <input type="text" wire:model="bullet_make"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bullet Weight (gr)</label>
                    <input type="number" wire:model="bullet_weight" step="0.1"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bullet Type</label>
                    <input type="text" wire:model="bullet_type"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Powder Type</label>
                    <input type="text" wire:model="powder_type"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Primer Type</label>
                    <input type="text" wire:model="primer_type"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Charge Settings -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Charge Series</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Charge (gr) *</label>
                    <input type="number" wire:model.live="start_charge" step="0.1" placeholder="e.g., 40.0"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('start_charge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Charge (gr) *</label>
                    <input type="number" wire:model.live="end_charge" step="0.1" placeholder="e.g., 44.0"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('end_charge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Increment (gr) *</label>
                    <input type="number" wire:model.live="increment" step="0.1" placeholder="e.g., 0.3"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('increment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rounds per Step</label>
                    <input type="number" wire:model.live="rounds_per_step" min="1" max="20"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
            </div>

            <!-- Step Preview -->
            @if(count($this->previewSteps) > 0)
                <div class="mt-6 p-4 bg-nrapa-blue-light dark:bg-nrapa-blue/10 rounded-lg">
                    <h3 class="text-sm font-semibold text-nrapa-blue mb-2">Step Preview ({{ count($this->previewSteps) }} steps, {{ count($this->previewSteps) * $rounds_per_step }} total rounds)</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->previewSteps as $step)
                            <span class="inline-flex items-center rounded-full bg-white dark:bg-zinc-800 px-3 py-1 text-xs font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600">
                                #{{ $step['step_number'] }}: {{ $step['charge_weight'] }}gr
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Options -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Options</h2>
            <div class="space-y-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="deductFromInventory" class="rounded border-zinc-300">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Deduct components from inventory when creating test</span>
                </label>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3" placeholder="e.g., Testing for optimal node in .308..."
                              class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('ladder-test.index') }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Create Ladder Test
            </button>
        </div>
    </form>
</div>
