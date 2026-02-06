<?php

use App\Models\UserFirearm;
use App\Models\FirearmType;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // FirearmSearchPanel data
    public ?array $firearmPanelData = null;

    // Basic info - SAPS 271 canonical (kept for backward compatibility during transition)
    public string $firearm_type = ''; // rifle|shotgun|handgun|hand_machine_carbine|combination
    public string $action = ''; // semi_automatic|automatic|manual|other
    public string $other_action_text = '';
    public ?int $calibre_id = null;
    public string $calibre_code = '';
    public string $make = '';
    public string $model = '';
    public string $nickname = '';

    // SAPS 271 component serials (at least one required) - kept for backward compatibility
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

    public function mount(): void
    {
        $this->firearmPanelData = [];
    }

    /**
     * Sync FirearmSearchPanel data back to component properties.
     */
    public function syncFirearmPanelData(array $data): void
    {
        $this->firearmPanelData = $data;
        
        // Sync to legacy properties for backward compatibility
        $this->firearm_type = $data['firearm_type'] ?? '';
        $this->action = $data['action_type'] ?? '';
        $this->other_action_text = $data['action_type_other'] ?? '';
        $this->calibre_code = $data['calibre_code'] ?? '';
        $this->barrel_serial = $data['barrel_serial_number'] ?? '';
        $this->barrel_make = $data['barrel_make_text'] ?? '';
        $this->frame_serial = $data['frame_serial_number'] ?? '';
        $this->frame_make = $data['frame_make_text'] ?? '';
        $this->receiver_serial = $data['receiver_serial_number'] ?? '';
        $this->receiver_make = $data['receiver_make_text'] ?? '';
    }

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
        $firearmData = $this->firearmPanelData ?? [];
        $barrelSerial = $firearmData['barrel_serial_number'] ?? $this->barrel_serial;
        $frameSerial = $firearmData['frame_serial_number'] ?? $this->frame_serial;
        $receiverSerial = $firearmData['receiver_serial_number'] ?? $this->receiver_serial;
        
        if (empty($barrelSerial) && empty($frameSerial) && empty($receiverSerial) && empty($this->serial_number)) {
            $this->addError('barrel_serial', 'Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271.');
        }
    }

    public function save(): void
    {
        $this->validate();
        $this->validateSerialRequirement();

        // Get data from FirearmSearchPanel if available
        $firearmData = $this->firearmPanelData ?? [];
        
        $data = [
            'user_id' => auth()->id(),
            // SAPS 271 canonical fields - prefer FirearmSearchPanel data
            'firearm_type' => $firearmData['firearm_type'] ?? $this->firearm_type,
            'action' => $firearmData['action_type'] ?? $this->action,
            'other_action_text' => ($firearmData['action_type'] ?? $this->action) === 'other' 
                ? ($firearmData['action_type_other'] ?? $this->other_action_text) 
                : null,
            'calibre_id' => $this->calibre_id, // Legacy, kept for backward compatibility
            'firearm_calibre_id' => $firearmData['firearm_calibre_id'] ?? null,
            'calibre_text_override' => $firearmData['calibre_text_override'] ?? null,
            'calibre_code' => $firearmData['calibre_code'] ?? $this->calibre_code ?: null,
            'firearm_make_id' => $firearmData['firearm_make_id'] ?? null,
            'make_text_override' => $firearmData['make_text_override'] ?? null,
            'make' => $firearmData['make_text_override'] ?? $this->make ?: null, // Legacy fallback
            'firearm_model_id' => $firearmData['firearm_model_id'] ?? null,
            'model_text_override' => $firearmData['model_text_override'] ?? null,
            'model' => $firearmData['model_text_override'] ?? $this->model ?: null, // Legacy fallback
            'nickname' => $this->nickname ?: null,
            // SAPS 271 serial fields
            'barrel_serial_number' => $firearmData['barrel_serial_number'] ?? $this->barrel_serial ?: null,
            'barrel_make_text' => $firearmData['barrel_make_text'] ?? $this->barrel_make ?: null,
            'frame_serial_number' => $firearmData['frame_serial_number'] ?? $this->frame_serial ?: null,
            'frame_make_text' => $firearmData['frame_make_text'] ?? $this->frame_make ?: null,
            'receiver_serial_number' => $firearmData['receiver_serial_number'] ?? $this->receiver_serial ?: null,
            'receiver_make_text' => $firearmData['receiver_make_text'] ?? $this->receiver_make ?: null,
            'engraved_text' => $firearmData['engraved_text'] ?? null,
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

        // Create firearm components (SAPS 271 canonical) - prefer FirearmSearchPanel data
        $barrelSerial = $firearmData['barrel_serial_number'] ?? $this->barrel_serial;
        $frameSerial = $firearmData['frame_serial_number'] ?? $this->frame_serial;
        $receiverSerial = $firearmData['receiver_serial_number'] ?? $this->receiver_serial;
        $barrelMake = $firearmData['barrel_make_text'] ?? $this->barrel_make;
        $frameMake = $firearmData['frame_make_text'] ?? $this->frame_make;
        $receiverMake = $firearmData['receiver_make_text'] ?? $this->receiver_make;
        
        $components = [];
        
        if (!empty($barrelSerial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'barrel',
                'serial' => $barrelSerial,
                'make' => $barrelMake ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($frameSerial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'frame',
                'serial' => $frameSerial,
                'make' => $frameMake ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($receiverSerial)) {
            $components[] = [
                'firearm_id' => $firearm->id,
                'type' => 'receiver',
                'serial' => $receiverSerial,
                'make' => $receiverMake ?: null,
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
            </div>
        </div>

        <!-- Firearm Details (SAPS 271) - Using FirearmSearchPanel -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6"
             x-data="{ panelData: @entangle('firearmPanelData') }"
             @firearm-data-updated.window="panelData = $event.detail.data; $wire.syncFirearmPanelData(panelData)">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Firearm Details (SAPS 271 Form Section E)</h2>
            @php
                if (!isset($firearmPanelData) || $firearmPanelData === null) {
                    $firearmPanelData = [];
                }
            @endphp
            <livewire:firearm-search-panel 
                wire:key="armoury-create-firearm-panel"
                :initial-data="$firearmPanelData"
            />
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
