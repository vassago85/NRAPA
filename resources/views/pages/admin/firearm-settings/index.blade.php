<?php

use App\Models\FirearmType;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;

new #[Title('Firearm Settings')] class extends Component {
    // Filters
    public string $categoryFilter = '';
    public string $ignitionFilter = '';
    
    // Firearm Type form
    public ?int $editingFirearmTypeId = null;
    public string $firearmTypeName = '';
    public string $firearmTypeCategory = 'rifle';
    public ?string $firearmTypeIgnition = null;
    public ?string $firearmTypeAction = null;
    public string $firearmTypeDescription = '';
    public int $firearmTypeSortOrder = 0;

    public function with(): array
    {
        // Get firearm types with filtering
        $firearmTypes = FirearmType::query()
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->ignitionFilter, fn($q) => $q->where(function($q2) {
                $q2->where('ignition_type', $this->ignitionFilter)
                   ->orWhere('ignition_type', 'both');
            }))
            ->ordered()
            ->get();

        return [
            'firearmTypes' => $firearmTypes,
            'categoryOptions' => FirearmType::getCategoryOptions(),
            'ignitionOptions' => FirearmType::getIgnitionTypeOptions(),
            'actionOptions' => FirearmType::getActionTypeOptions($this->firearmTypeCategory),
        ];
    }

    // ===== Firearm Types =====
    
    public function saveFirearmType(): void
    {
        $this->validate([
            'firearmTypeName' => ['required', 'string', 'max:255'],
            'firearmTypeCategory' => ['required', 'in:handgun,rifle,shotgun'],
        ]);

        FirearmType::updateOrCreate(
            ['id' => $this->editingFirearmTypeId],
            [
                'name' => $this->firearmTypeName,
                'slug' => Str::slug($this->firearmTypeName),
                'category' => $this->firearmTypeCategory,
                'ignition_type' => $this->firearmTypeIgnition,
                'action_type' => $this->firearmTypeAction,
                'description' => $this->firearmTypeDescription ?: null,
                'dedicated_type' => 'both',
                'is_active' => true,
                'sort_order' => $this->firearmTypeSortOrder,
            ]
        );

        $this->resetFirearmTypeForm();
        session()->flash('success', 'Firearm type saved successfully.');
    }

    public function editFirearmType(FirearmType $type): void
    {
        $this->editingFirearmTypeId = $type->id;
        $this->firearmTypeName = $type->name;
        $this->firearmTypeCategory = $type->category;
        $this->firearmTypeIgnition = $type->ignition_type;
        $this->firearmTypeAction = $type->action_type;
        $this->firearmTypeDescription = $type->description ?? '';
        $this->firearmTypeSortOrder = $type->sort_order;
    }

    public function deleteFirearmType(FirearmType $type): void
    {
        $type->delete();
        session()->flash('success', 'Firearm type deleted.');
    }

    public function toggleFirearmTypeActive(FirearmType $type): void
    {
        $type->update(['is_active' => !$type->is_active]);
    }

    public function resetFirearmTypeForm(): void
    {
        $this->editingFirearmTypeId = null;
        $this->firearmTypeName = '';
        $this->firearmTypeCategory = 'rifle';
        $this->firearmTypeIgnition = null;
        $this->firearmTypeAction = null;
        $this->firearmTypeDescription = '';
        $this->firearmTypeSortOrder = 0;
    }

    public function clearFilters(): void
    {
        $this->categoryFilter = '';
        $this->ignitionFilter = '';
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Configuration</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Configure firearm-related settings</p>
    </x-slot>

    <x-admin-config-tabs current="firearm-settings" />

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4 text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            {{-- Form --}}
            <div class="rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingFirearmTypeId ? 'Edit' : 'Add' }} Firearm Type</h3>
                <form wire:submit="saveFirearmType" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="firearmTypeName" placeholder="e.g. Bolt Action Rifle" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('firearmTypeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category <span class="text-red-500">*</span></label>
                        <select wire:model.live="firearmTypeCategory" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @foreach($categoryOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type</label>
                        <select wire:model="firearmTypeIgnition" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Both / Any</option>
                            @foreach($ignitionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Type</label>
                        <select wire:model="firearmTypeAction" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select Action Type</option>
                            @foreach($actionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                        <textarea wire:model="firearmTypeDescription" rows="2" placeholder="Optional description..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                        <input type="number" wire:model="firearmTypeSortOrder" min="0" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">Save</button>
                        @if($editingFirearmTypeId)
                            <button type="button" wire:click="resetFirearmTypeForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="xl:col-span-3 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-3">
                    <select wire:model.live="categoryFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                        <option value="">All Categories</option>
                        <option value="handgun">Handgun</option>
                        <option value="rifle">Rifle</option>
                        <option value="shotgun">Shotgun</option>
                    </select>
                    <select wire:model.live="ignitionFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                        <option value="">All Types</option>
                        <option value="rimfire">Rimfire</option>
                        <option value="centerfire">Centerfire</option>
                    </select>
                    @if($categoryFilter || $ignitionFilter)
                    <button wire:click="clearFilters" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Clear</button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Category</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Ignition</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Action</th>
                                <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">Active</th>
                                <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($firearmTypes as $type)
                                <tr class="{{ !$type->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-3 py-2.5 text-sm text-zinc-900 dark:text-white">
                                        {{ $type->name }}
                                        @if($type->description)
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs">{{ $type->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium 
                                            {{ $type->category === 'handgun' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : '' }}
                                            {{ $type->category === 'rifle' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : '' }}
                                            {{ $type->category === 'shotgun' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : '' }}">
                                            {{ ucfirst($type->category) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $type->ignition_type_label ?? 'Any' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $type->action_type_label ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <button wire:click="toggleFirearmTypeActive({{ $type->id }})" class="text-lg transition-colors">
                                            @if($type->is_active)
                                                <span class="text-emerald-500">✓</span>
                                            @else
                                                <span class="text-zinc-300">○</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <button wire:click="editFirearmType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm transition-colors">Edit</button>
                                        <button wire:click="deleteFirearmType({{ $type->id }})" wire:confirm="Are you sure you want to delete this firearm type?" class="ml-2 text-red-600 hover:text-red-700 text-sm transition-colors">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        No firearm types found. Add one above.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>
