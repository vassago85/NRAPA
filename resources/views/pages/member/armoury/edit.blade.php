<?php

use App\Models\UserFirearm;
use App\Models\FirearmType;
use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use App\Models\FirearmModel;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public UserFirearm $firearm;

    // Essentials
    public string $nickname = '';
    public string $firearm_type = '';
    public string $action = '';
    public ?int $firearm_calibre_id = null;
    public ?int $firearm_make_id = null;
    public ?int $firearm_model_id = null;
    public string $serial_number = ''; // primary serial (receiver)
    public string $license_number = '';
    public ?string $license_expiry_date = null;

    // Advanced - Barrel & Stock
    public string $barrel_length = '';
    public string $barrel_twist = '';
    public string $barrel_profile = '';
    public string $stock_type = '';
    public string $stock_make = '';

    // Advanced - Optics
    public string $scope_make = '';
    public string $scope_model = '';
    public string $scope_magnification = '';

    // Advanced - License Details
    public ?string $license_issue_date = null;
    public string $license_type = '';
    public string $license_status = 'valid';

    // Advanced - Notes & Documents
    public string $notes = '';
    public $firearm_image = null;
    public $license_document = null;

    // Advanced - SAPS 271 Component Serials
    public string $barrel_serial = '';
    public string $barrel_make_field = '';
    public string $frame_serial = '';
    public string $frame_make_field = '';
    public string $receiver_make_field = '';
    public string $other_action_text = '';

    // Legacy compat
    public ?int $calibre_id = null;
    public ?int $firearm_type_id = null;

    public function mount(UserFirearm $firearm): void
    {
        if ($firearm->user_id !== auth()->id()) {
            abort(403);
        }

        $this->firearm = $firearm->load('components');

        // Essentials
        $this->firearm_type = $firearm->firearm_type ?? '';
        $this->action = $firearm->action ?? '';
        $this->other_action_text = $firearm->other_action_text ?? '';
        $this->firearm_calibre_id = $firearm->firearm_calibre_id;
        $this->firearm_make_id = $firearm->firearm_make_id;
        $this->firearm_model_id = $firearm->firearm_model_id;
        $this->nickname = $firearm->nickname ?? '';
        $this->license_number = $firearm->license_number ?? '';
        $this->license_expiry_date = $firearm->license_expiry_date?->format('Y-m-d');

        // Primary serial from receiver component or legacy
        $receiver = $firearm->receiverComponent();
        $this->serial_number = $receiver?->serial ?? $firearm->receiver_serial_number ?? $firearm->serial_number ?? '';

        // Advanced - Barrel & Stock
        $this->barrel_length = $firearm->barrel_length ?? '';
        $this->barrel_twist = $firearm->barrel_twist ?? '';
        $this->barrel_profile = $firearm->barrel_profile ?? '';
        $this->stock_type = $firearm->stock_type ?? '';
        $this->stock_make = $firearm->stock_make ?? '';

        // Advanced - Optics
        $this->scope_make = $firearm->scope_make ?? '';
        $this->scope_model = $firearm->scope_model ?? '';
        $this->scope_magnification = $firearm->scope_magnification ?? '';

        // Advanced - License
        $this->license_issue_date = $firearm->license_issue_date?->format('Y-m-d');
        $this->license_type = $firearm->license_type ?? '';
        $this->license_status = $firearm->license_status ?? 'valid';

        // Advanced - Notes
        $this->notes = $firearm->notes ?? '';

        // Advanced - SAPS 271 Component Serials
        $barrel = $firearm->barrelComponent();
        $this->barrel_serial = $barrel?->serial ?? $firearm->barrel_serial_number ?? '';
        $this->barrel_make_field = $barrel?->make ?? $firearm->barrel_make_text ?? '';

        $frame = $firearm->frameComponent();
        $this->frame_serial = $frame?->serial ?? $firearm->frame_serial_number ?? '';
        $this->frame_make_field = $frame?->make ?? $firearm->frame_make_text ?? '';

        $this->receiver_make_field = $receiver?->make ?? $firearm->receiver_make_text ?? '';

        // Legacy
        $this->calibre_id = $firearm->calibre_id;
        $this->firearm_type_id = $firearm->firearm_type_id;
    }

    public function updatedFirearmMakeId($value): void
    {
        if (!$value) {
            $this->firearm_model_id = null;
        }
    }

    public function rules(): array
    {
        return [
            'firearm_type' => ['required', 'in:rifle,shotgun,handgun,hand_machine_carbine,combination'],
            'action' => ['required', 'in:semi_automatic,automatic,bolt_action,pump_action,lever_action,manual,other'],
            'other_action_text' => ['required_if:action,other', 'nullable', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'firearm_calibre_id' => ['nullable', 'exists:firearm_calibres,id'],
            'firearm_make_id' => ['nullable', 'exists:firearm_makes,id'],
            'firearm_model_id' => ['nullable', 'exists:firearm_models,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry_date' => ['nullable', 'date'],
            // Advanced
            'barrel_serial' => ['nullable', 'string', 'max:255'],
            'barrel_make_field' => ['nullable', 'string', 'max:255'],
            'frame_serial' => ['nullable', 'string', 'max:255'],
            'frame_make_field' => ['nullable', 'string', 'max:255'],
            'receiver_make_field' => ['nullable', 'string', 'max:255'],
            'barrel_length' => ['nullable', 'string', 'max:50'],
            'barrel_twist' => ['nullable', 'string', 'max:50'],
            'barrel_profile' => ['nullable', 'string', 'max:100'],
            'stock_type' => ['nullable', 'string', 'max:100'],
            'stock_make' => ['nullable', 'string', 'max:100'],
            'scope_make' => ['nullable', 'string', 'max:100'],
            'scope_model' => ['nullable', 'string', 'max:100'],
            'scope_magnification' => ['nullable', 'string', 'max:50'],
            'license_issue_date' => ['nullable', 'date'],
            'license_type' => ['nullable', 'in:self_defence,occasional_sport,dedicated_sport,dedicated_hunting,business,private_collection'],
            'license_status' => ['required', 'in:valid,expired,renewal_pending,revoked'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'firearm_image' => ['nullable', 'image', 'max:5120'],
            'license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $make = $this->firearm_make_id ? FirearmMake::find($this->firearm_make_id) : null;
        $model = $this->firearm_model_id ? FirearmModel::find($this->firearm_model_id) : null;

        $data = [
            'firearm_type' => $this->firearm_type,
            'action' => $this->action,
            'other_action_text' => $this->action === 'other' ? $this->other_action_text : null,
            'firearm_calibre_id' => $this->firearm_calibre_id,
            'firearm_make_id' => $this->firearm_make_id,
            'firearm_model_id' => $this->firearm_model_id,
            'make' => $make?->name,
            'model' => $model?->name,
            'nickname' => $this->nickname ?: null,
            'receiver_serial_number' => $this->serial_number,
            'barrel_serial_number' => $this->barrel_serial ?: null,
            'barrel_make_text' => $this->barrel_make_field ?: null,
            'frame_serial_number' => $this->frame_serial ?: null,
            'frame_make_text' => $this->frame_make_field ?: null,
            'receiver_make_text' => $this->receiver_make_field ?: null,
            'license_number' => $this->license_number ?: null,
            'license_expiry_date' => $this->license_expiry_date ?: null,
            'license_issue_date' => $this->license_issue_date ?: null,
            'license_type' => $this->license_type ?: null,
            'license_status' => $this->license_status,
            'barrel_length' => $this->barrel_length ?: null,
            'barrel_twist' => $this->barrel_twist ?: null,
            'barrel_profile' => $this->barrel_profile ?: null,
            'stock_type' => $this->stock_type ?: null,
            'stock_make' => $this->stock_make ?: null,
            'scope_make' => $this->scope_make ?: null,
            'scope_model' => $this->scope_model ?: null,
            'scope_magnification' => $this->scope_magnification ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->firearm_image) {
            $data['image_path'] = $this->firearm_image->store('firearms', 'public');
        }

        if ($this->license_document) {
            $data['license_document_path'] = $this->license_document->store('licenses', 'private');
        }

        $this->firearm->update($data);

        // Rebuild firearm components
        $this->firearm->components()->delete();

        $components = [];

        // Receiver (always from primary serial)
        if (!empty($this->serial_number)) {
            $components[] = [
                'firearm_id' => $this->firearm->id,
                'type' => 'receiver',
                'serial' => $this->serial_number,
                'make' => $this->receiver_make_field ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($this->barrel_serial)) {
            $components[] = [
                'firearm_id' => $this->firearm->id,
                'type' => 'barrel',
                'serial' => $this->barrel_serial,
                'make' => $this->barrel_make_field ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($this->frame_serial)) {
            $components[] = [
                'firearm_id' => $this->firearm->id,
                'type' => 'frame',
                'serial' => $this->frame_serial,
                'make' => $this->frame_make_field ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($components)) {
            \App\Models\FirearmComponent::insert($components);
        }

        session()->flash('success', 'Firearm updated successfully.');
        $this->redirect(route('armoury.show', $this->firearm), navigate: true);
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
            'licenseTypes' => UserFirearm::licenseTypes(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('armoury.show', $firearm) }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Edit Firearm</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $firearm->display_name }}</p>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save" class="max-w-3xl space-y-6">

        {{-- ─── Section 1: Essentials (always visible) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Essentials</h2>
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
                    @if($firearm_image && !is_string($firearm_image))
                        <div class="mt-2">
                            <img src="{{ $firearm_image->temporaryUrl() }}" alt="Preview" class="h-24 w-auto rounded-lg object-cover border border-zinc-200 dark:border-zinc-600">
                        </div>
                    @elseif($firearm->image_path)
                        <div class="mt-2 flex items-center gap-2">
                            <img src="{{ Storage::disk('public')->url($firearm->image_path) }}" alt="Current photo" class="h-24 w-auto rounded-lg object-cover border border-zinc-200 dark:border-zinc-600">
                            <span class="text-xs text-zinc-400">Current photo — upload new to replace</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Section 2: Barrel & Stock (collapsed) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between p-6 text-left">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Barrel & Stock Details</h2>
                <svg class="h-5 w-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-6 pb-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Length</label>
                        <input type="text" wire:model="barrel_length" placeholder="e.g., 24 inches"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Twist</label>
                        <input type="text" wire:model="barrel_twist" placeholder="e.g., 1:10"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Profile</label>
                        <input type="text" wire:model="barrel_profile" placeholder="e.g., Heavy, Sporter"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Stock Type</label>
                        <input type="text" wire:model="stock_type" placeholder="e.g., Synthetic, Wood, Chassis"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Stock Make</label>
                        <input type="text" wire:model="stock_make" placeholder="e.g., Howa, MDT, KRG"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Section 3: Optics (collapsed) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between p-6 text-left">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Optics</h2>
                <svg class="h-5 w-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-6 pb-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scope Make</label>
                        <input type="text" wire:model="scope_make" placeholder="e.g., Vortex, Nightforce, Leupold"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scope Model</label>
                        <input type="text" wire:model="scope_model" placeholder="e.g., PST Gen II, ATACR, VX-5HD"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Magnification</label>
                        <input type="text" wire:model="scope_magnification" placeholder="e.g., 4-16x44, 5-25x56"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Section 4: License Details (collapsed) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between p-6 text-left">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">License Details</h2>
                <svg class="h-5 w-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-6 pb-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Issue Date</label>
                        <input type="date" wire:model="license_issue_date"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Type</label>
                        <select wire:model="license_type"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select type...</option>
                            @foreach($licenseTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Status</label>
                        <select wire:model="license_status"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="valid">Valid</option>
                            <option value="renewal_pending">Renewal Pending</option>
                            <option value="expired">Expired</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Section 5: Notes & Documents (collapsed) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between p-6 text-left">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Notes & Documents</h2>
                <svg class="h-5 w-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-6 pb-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3" placeholder="Any additional notes about this firearm..."
                              class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Update Photo</label>
                        <input type="file" wire:model="firearm_image" accept="image/*"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-sm file:bg-nrapa-blue-light file:text-nrapa-blue">
                        @if($firearm->image_path)
                            <p class="mt-1 text-xs text-zinc-500">Current photo will be replaced.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Update License Document</label>
                        <input type="file" wire:model="license_document" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-sm file:bg-nrapa-blue-light file:text-nrapa-blue">
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Section 6: SAPS 271 Component Serials (collapsed) ─── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between p-6 text-left">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">SAPS 271 Component Serials</h2>
                <svg class="h-5 w-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-6 pb-6">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">For SAPS 271 compliance. Barrel, frame, and receiver serials as listed on your license documentation.</p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Serial</label>
                        <input type="text" wire:model="barrel_serial"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Make</label>
                        <input type="text" wire:model="barrel_make_field"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Frame Serial</label>
                        <input type="text" wire:model="frame_serial"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Frame Make</label>
                        <input type="text" wire:model="frame_make_field"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Receiver Make</label>
                        <input type="text" wire:model="receiver_make_field" placeholder="Make of receiver (serial is above in Essentials)"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('armoury.show', $firearm) }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
