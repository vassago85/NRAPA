<?php

use App\Models\UserFirearm;
use App\Models\FirearmType;
use App\Models\Calibre;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // Basic info - SAPS 271 canonical
    public string $firearm_type = ''; // rifle|shotgun|handgun|hand_machine_carbine|combination
    public string $action = ''; // semi_automatic|automatic|manual|other
    public string $other_action_text = '';
    public ?int $calibre_id = null;
    public string $calibre_code = '';
    public string $make = '';
    public string $model = '';
    public string $nickname = '';

    // SAPS 271 component serials (at least one required)
    public string $barrel_serial = '';
    public string $barrel_make = '';
    public string $frame_serial = '';
    public string $frame_make = '';
    public string $receiver_serial = '';
    public string $receiver_make = '';

    // Legacy support (kept for backwards compatibility)
    public ?int $firearm_type_id = null;
    public string $serial_number = ''; // Legacy, will be migrated to receiver

    // Category filters for calibre selector
    public ?string $selectedCategory = null;
    public ?string $selectedIgnition = null;

    // Barrel details
    public string $barrel_length = '';
    public string $barrel_twist = '';
    public string $barrel_profile = '';

    // Stock
    public string $stock_type = '';
    public string $stock_make = '';

    // Optics
    public string $scope_make = '';
    public string $scope_model = '';
    public string $scope_magnification = '';

    // License
    public string $license_number = '';
    public ?string $license_issue_date = null;
    public ?string $license_expiry_date = null;
    public string $license_type = '';

    // Notes
    public string $notes = '';

    // File uploads
    public $firearm_image = null;
    public $license_document = null;

    public function updatedFirearmType($value): void
    {
        // Auto-set category filter based on SAPS 271 firearm type
        if ($value) {
            $categoryMap = [
                'rifle' => 'rifle',
                'shotgun' => 'shotgun',
                'handgun' => 'handgun',
                'hand_machine_carbine' => 'handgun',
                'combination' => null,
            ];
            $this->selectedCategory = $categoryMap[$value] ?? null;
        } else {
            $this->selectedCategory = null;
            $this->selectedIgnition = null;
        }
    }

    public function rules(): array
    {
        return [
            // SAPS 271 canonical fields
            'firearm_type' => ['required', 'in:rifle,shotgun,handgun,hand_machine_carbine,combination'],
            'action' => ['required', 'in:semi_automatic,automatic,manual,other'],
            'other_action_text' => ['required_if:action,other', 'nullable', 'string', 'max:255'],
            'calibre_id' => ['nullable', 'exists:calibres,id'],
            'calibre_code' => ['nullable', 'string', 'max:50'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            
            // Component serials (at least one required - validated in custom rule)
            'barrel_serial' => ['nullable', 'string', 'max:255'],
            'barrel_make' => ['nullable', 'string', 'max:255'],
            'frame_serial' => ['nullable', 'string', 'max:255'],
            'frame_make' => ['nullable', 'string', 'max:255'],
            'receiver_serial' => ['nullable', 'string', 'max:255'],
            'receiver_make' => ['nullable', 'string', 'max:255'],
            
            // Legacy fields (kept for backwards compatibility)
            'firearm_type_id' => ['nullable', 'exists:firearm_types,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            
            // Other fields
            'barrel_length' => ['nullable', 'string', 'max:50'],
            'barrel_twist' => ['nullable', 'string', 'max:50'],
            'barrel_profile' => ['nullable', 'string', 'max:100'],
            'stock_type' => ['nullable', 'string', 'max:100'],
            'stock_make' => ['nullable', 'string', 'max:100'],
            'scope_make' => ['nullable', 'string', 'max:100'],
            'scope_model' => ['nullable', 'string', 'max:100'],
            'scope_magnification' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_issue_date' => ['nullable', 'date'],
            'license_expiry_date' => ['nullable', 'date', 'after:license_issue_date'],
            'license_type' => ['nullable', 'in:self_defence,occasional_sport,dedicated_sport,dedicated_hunting,business,private_collection'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'firearm_image' => ['nullable', 'image', 'max:5120'], // 5MB
            'license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
        ];
    }

    /**
     * Validate that at least one serial number is provided (SAPS 271 requirement).
     */
    public function validateSerialRequirement(): void
    {
        if (empty($this->barrel_serial) && empty($this->frame_serial) && empty($this->receiver_serial) && empty($this->serial_number)) {
            $this->addError('barrel_serial', 'Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271.');
        }
    }

    public function save(): void
    {
        $this->validate();
        $this->validateSerialRequirement();

        $data = [
            'user_id' => auth()->id(),
            // SAPS 271 canonical fields
            'firearm_type' => $this->firearm_type,
            'action' => $this->action,
            'other_action_text' => $this->action === 'other' ? $this->other_action_text : null,
            'calibre_id' => $this->calibre_id,
            'calibre_code' => $this->calibre_code ?: null,
            'make' => $this->make ?: null,
            'model' => $this->model ?: null,
            'nickname' => $this->nickname ?: null,
            // Legacy fields (kept for backwards compatibility)
            'firearm_type_id' => $this->firearm_type_id,
            'serial_number' => $this->serial_number ?: null, // Will be migrated to receiver if provided
            // Other fields
            'barrel_length' => $this->barrel_length ?: null,
            'barrel_twist' => $this->barrel_twist ?: null,
            'barrel_profile' => $this->barrel_profile ?: null,
            'stock_type' => $this->stock_type ?: null,
            'stock_make' => $this->stock_make ?: null,
            'scope_make' => $this->scope_make ?: null,
            'scope_model' => $this->scope_model ?: null,
            'scope_magnification' => $this->scope_magnification ?: null,
            'license_number' => $this->license_number ?: null,
            'license_issue_date' => $this->license_issue_date ?: null,
            'license_expiry_date' => $this->license_expiry_date ?: null,
            'license_type' => $this->license_type ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->firearm_image) {
            $data['image_path'] = $this->firearm_image->store('firearms', 'public');
        }

        if ($this->license_document) {
            $data['license_document_path'] = $this->license_document->store('licenses', 'private');
        }

        $firearm = UserFirearm::create($data);

        // Create firearm components (SAPS 271 canonical)
        $components = [];
        
        if (!empty($this->barrel_serial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'barrel',
                'serial' => $this->barrel_serial,
                'make' => $this->barrel_make ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($this->frame_serial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'frame',
                'serial' => $this->frame_serial,
                'make' => $this->frame_make ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($this->receiver_serial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'receiver',
                'serial' => $this->receiver_serial,
                'make' => $this->receiver_make ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } elseif (!empty($this->serial_number)) {
            // Migrate legacy serial_number to receiver component
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'receiver',
                'serial' => $this->serial_number,
                'make' => null,
                'notes' => 'Migrated from legacy serial_number field',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($components)) {
            \App\Models\FirearmComponent::insert($components);
        }

        session()->flash('success', 'Firearm added to your Virtual Safe successfully.');
        $this->redirect(route('armoury.show', $firearm), navigate: true);
    }

    public function with(): array
    {
        // Get firearm types grouped by category
        $firearmTypes = FirearmType::active()
            ->ordered()
            ->get()
            ->groupBy('category');

        return [
            'firearmTypesByCategory' => $firearmTypes,
            'categoryLabels' => FirearmType::getCategoryOptions(),
            'licenseTypes' => UserFirearm::licenseTypes(),
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
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Add a new firearm to your armoury</p>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nickname (optional)</label>
                    <input type="text" wire:model="nickname" placeholder="e.g., Match Rifle, Hunting Rifle"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('nickname') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm Type <span class="text-red-500">*</span></label>
                    <select wire:model.live="firearm_type"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select type...</option>
                        <option value="rifle">Rifle</option>
                        <option value="shotgun">Shotgun</option>
                        <option value="handgun">Handgun</option>
                        <option value="hand_machine_carbine">Hand Machine Carbine</option>
                        <option value="combination">Combination</option>
                    </select>
                    @error('firearm_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">SAPS 271 classification</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action <span class="text-red-500">*</span></label>
                    <select wire:model.live="action"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <option value="">Select action...</option>
                        <option value="semi_automatic">Semi-Automatic</option>
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                        <option value="other">Other</option>
                    </select>
                    @error('action') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                @if($action === 'other')
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Specify Other Action <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="other_action_text" placeholder="e.g., Lever action, Pump action"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('other_action_text') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                    <livewire:components.calibre-selector 
                        wire:model="calibre_id"
                        :category-filter="$selectedCategory"
                        :ignition-filter="$selectedIgnition"
                    />
                    @error('calibre_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre Code (SAPS)</label>
                    <input type="text" wire:model="calibre_code" placeholder="e.g., 308, 9MM"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('calibre_code') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Official SAPS calibre code</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                    <input type="text" wire:model="make" placeholder="e.g., Howa, Tikka, CZ"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('make') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                    <input type="text" wire:model="model" placeholder="e.g., 1500, T3x, 455"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        <!-- SAPS 271 Component Serial Numbers -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Component Serial Numbers (SAPS 271)</h2>
            <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271 requirements.
            </p>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Barrel -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Barrel</h3>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number</label>
                        <input type="text" wire:model="barrel_serial"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('barrel_serial') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make (optional)</label>
                        <input type="text" wire:model="barrel_make"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>

                <!-- Frame -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Frame</h3>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number</label>
                        <input type="text" wire:model="frame_serial"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('frame_serial') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make (optional)</label>
                        <input type="text" wire:model="frame_make"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>

                <!-- Receiver -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Receiver</h3>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number</label>
                        <input type="text" wire:model="receiver_serial"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('receiver_serial') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make (optional)</label>
                        <input type="text" wire:model="receiver_make"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
            </div>
            @if(empty($barrel_serial) && empty($frame_serial) && empty($receiver_serial) && empty($serial_number))
                <p class="mt-4 text-sm text-red-600 dark:text-red-400">
                    ⚠️ Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271.
                </p>
            @endif
        </div>

        <!-- Barrel & Stock Details -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Barrel & Stock Details</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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

        <!-- Optics -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Optics</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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

        <!-- License Information -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">License Information</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Number</label>
                    <input type="text" wire:model="license_number"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('license_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                    @error('license_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Issue Date</label>
                    <input type="date" wire:model="license_issue_date"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('license_issue_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Expiry Date</label>
                    <input type="date" wire:model="license_expiry_date"
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    @error('license_expiry_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-500">You'll receive notifications before this date.</p>
                </div>
            </div>
        </div>

        <!-- Notes & Documents -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Notes & Documents</h2>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3" placeholder="Any additional notes about this firearm..."
                              class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm Photo (optional)</label>
                        <input type="file" wire:model="firearm_image" accept="image/*"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-sm file:bg-emerald-50 file:text-emerald-700">
                        @error('firearm_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">License Document (optional)</label>
                        <input type="file" wire:model="license_document" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-sm file:bg-emerald-50 file:text-emerald-700">
                        @error('license_document') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('armoury.index') }}" wire:navigate
               class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Add to Virtual Safe
            </button>
        </div>
    </form>
</div>
