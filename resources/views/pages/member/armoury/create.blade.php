<?php

use App\Models\UserFirearm;
use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // Essential fields only
    public string $nickname = '';
    public string $firearm_type = '';
    public string $action = '';
    public ?int $firearm_calibre_id = null;
    public ?int $firearm_make_id = null;
    public ?int $firearm_model_id = null;
    public string $serial_number = '';
    public string $license_number = '';
    public ?string $license_expiry_date = null;
    public $firearm_image = null;

    // Search helpers
    public string $calibre_search = '';
    public string $make_search = '';

    public function rules(): array
    {
        return [
            'firearm_type' => ['required', 'in:rifle,shotgun,handgun,hand_machine_carbine,combination'],
            'action' => ['required', 'in:semi_automatic,automatic,bolt_action,pump_action,lever_action,manual,other'],
            'serial_number' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'firearm_calibre_id' => ['nullable', 'exists:firearm_calibres,id'],
            'firearm_make_id' => ['nullable', 'exists:firearm_makes,id'],
            'firearm_model_id' => ['nullable', 'exists:firearm_models,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry_date' => ['nullable', 'date'],
            'firearm_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'serial_number.required' => 'A serial number is required to register a firearm.',
        ];
    }

    public function updatedFirearmMakeId($value): void
    {
        // Reset model when make changes
        if (!$value) {
            $this->firearm_model_id = null;
        }
    }

    public function save(): void
    {
        $this->validate();

        // Build display values from reference IDs
        $calibre = $this->firearm_calibre_id ? FirearmCalibre::find($this->firearm_calibre_id) : null;
        $make = $this->firearm_make_id ? FirearmMake::find($this->firearm_make_id) : null;
        $model = $this->firearm_model_id ? FirearmModel::find($this->firearm_model_id) : null;

        $data = [
            'user_id' => auth()->id(),
            'firearm_type' => $this->firearm_type,
            'action' => $this->action,
            'firearm_calibre_id' => $this->firearm_calibre_id,
            'firearm_make_id' => $this->firearm_make_id,
            'firearm_model_id' => $this->firearm_model_id,
            'make' => $make?->name,
            'model' => $model?->name,
            'nickname' => $this->nickname ?: null,
            'receiver_serial_number' => $this->serial_number,
            'license_number' => $this->license_number ?: null,
            'license_expiry_date' => $this->license_expiry_date ?: null,
        ];

        if ($this->firearm_image) {
            $data['image_path'] = $this->firearm_image->store('firearms', 'public');
        }

        $firearm = UserFirearm::create($data);

        // Create a single receiver component for the serial
        \App\Models\FirearmComponent::create([
            'firearm_id' => $firearm->id,
            'type' => 'receiver',
            'serial' => $this->serial_number,
        ]);

        session()->flash('success', 'Firearm added to your Virtual Safe.');
        $this->redirect(route('armoury.show', $firearm), navigate: true);
    }

    public function with(): array
    {
        $calibreQuery = FirearmCalibre::active()->ordered();
        if ($this->firearm_type) {
            $categoryMap = [
                'rifle' => 'rifle',
                'shotgun' => 'shotgun',
                'handgun' => 'handgun',
                'hand_machine_carbine' => 'handgun',
                'combination' => null,
            ];
            $cat = $categoryMap[$this->firearm_type] ?? null;
            if ($cat) {
                $calibreQuery->where('category', $cat);
            }
        }

        $models = collect();
        if ($this->firearm_make_id) {
            $models = FirearmModel::where('firearm_make_id', $this->firearm_make_id)->orderBy('name')->get();
        }

        return [
            'calibres' => $calibreQuery->get(),
            'makes' => FirearmMake::orderBy('name')->get(),
            'models' => $models,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('armoury.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Add Firearm</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Quick-add a firearm to your Virtual Safe</p>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="space-y-5">

                <!-- Nickname -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nickname <span class="text-zinc-400 font-normal">(optional)</span></label>
                    <input type="text" wire:model="nickname" placeholder="e.g., Match Rifle, Bush Gun"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>

                <!-- Type & Action -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm Type *</label>
                        <select wire:model.live="firearm_type"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select type...</option>
                            <option value="rifle">Rifle</option>
                            <option value="shotgun">Shotgun</option>
                            <option value="handgun">Handgun</option>
                            <option value="hand_machine_carbine">Hand Machine Carbine</option>
                            <option value="combination">Combination</option>
                        </select>
                        @error('firearm_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action *</label>
                        <select wire:model="action"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select action...</option>
                            <option value="bolt_action">Bolt Action</option>
                            <option value="semi_automatic">Semi-Automatic</option>
                            <option value="lever_action">Lever Action</option>
                            <option value="pump_action">Pump Action</option>
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual (Other)</option>
                            <option value="other">Other</option>
                        </select>
                        @error('action') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Calibre -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                    <x-searchable-select
                        :options="$calibres"
                        wire-model="firearm_calibre_id"
                        placeholder="Type to search calibres..."
                    />
                </div>

                <!-- Make & Model -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                        <x-searchable-select
                            :options="$makes"
                            wire-model="firearm_make_id"
                            placeholder="Type to search makes..."
                            :live="true"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                        <x-searchable-select
                            :options="$models"
                            wire-model="firearm_model_id"
                            placeholder="{{ $models->isEmpty() ? 'Select a make first' : 'Type to search models...' }}"
                            :disabled="$models->isEmpty()"
                        />
                    </div>
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number *</label>
                    <input type="text" wire:model="serial_number" placeholder="Primary serial number"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('serial_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- License Number & Expiry -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Number</label>
                        <input type="text" wire:model="license_number" placeholder="e.g., 12345678"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Expiry Date</label>
                        <input type="date" wire:model="license_expiry_date"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-400">You'll get reminders before it expires.</p>
                    </div>
                </div>

                <!-- Photo -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Photo <span class="text-zinc-400 font-normal">(optional)</span></label>
                    <input type="file" wire:model="firearm_image" accept="image/*"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-nrapa-blue-light file:text-nrapa-blue">
                    @error('firearm_image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if($firearm_image)
                        <div class="mt-2">
                            <img src="{{ $firearm_image->temporaryUrl() }}" alt="Preview" class="h-24 w-auto rounded-lg object-cover border border-zinc-200 dark:border-zinc-600">
                        </div>
                    @endif
                </div>

            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('armoury.index') }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                Add to Virtual Safe
            </button>
        </div>
    </form>
</div>
