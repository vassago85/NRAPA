<?php

use App\Models\Calibre;
use App\Models\FirearmType;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new #[Title('Firearm Settings')] class extends Component {
    use WithPagination;

    public string $activeTab = 'firearm-types';
    
    // Filters
    public string $categoryFilter = '';
    public string $ignitionFilter = '';
    public string $searchTerm = '';
    
    // Firearm Type form
    public ?int $editingFirearmTypeId = null;
    public string $firearmTypeName = '';
    public string $firearmTypeCategory = 'rifle';
    public ?string $firearmTypeIgnition = null;
    public ?string $firearmTypeAction = null;
    public string $firearmTypeDescription = '';
    public int $firearmTypeSortOrder = 0;
    
    // Calibre form
    public ?int $editingCalibreId = null;
    public string $calibreName = '';
    public string $calibreCategory = 'rifle';
    public string $calibreIgnition = 'centerfire';
    public string $calibreAliases = '';
    public bool $calibreIsCommon = false;
    public bool $calibreIsObsolete = false;
    public int $calibreSortOrder = 0;

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedIgnitionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearchTerm(): void
    {
        $this->resetPage();
    }

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

        // Get calibres with filtering and pagination
        $calibres = Calibre::query()
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->ignitionFilter, fn($q) => $q->where('ignition_type', $this->ignitionFilter))
            ->when($this->searchTerm, fn($q) => $q->search($this->searchTerm))
            ->ordered()
            ->paginate(25);

        // Get calibre stats
        $calibreStats = [
            'total' => Calibre::count(),
            'handgun' => Calibre::where('category', 'handgun')->count(),
            'rifle' => Calibre::where('category', 'rifle')->count(),
            'shotgun' => Calibre::where('category', 'shotgun')->count(),
            'rimfire' => Calibre::where('ignition_type', 'rimfire')->count(),
            'centerfire' => Calibre::where('ignition_type', 'centerfire')->count(),
            'common' => Calibre::where('is_common', true)->count(),
        ];

        return [
            'firearmTypes' => $firearmTypes,
            'calibres' => $calibres,
            'calibreStats' => $calibreStats,
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

    // ===== Calibres =====

    public function saveCalibre(): void
    {
        $this->validate([
            'calibreName' => ['required', 'string', 'max:255'],
            'calibreCategory' => ['required', 'in:handgun,rifle,shotgun,other'],
            'calibreIgnition' => ['required', 'in:rimfire,centerfire'],
        ]);

        $aliases = array_filter(array_map('trim', explode(',', $this->calibreAliases)));

        Calibre::updateOrCreate(
            ['id' => $this->editingCalibreId],
            [
                'name' => $this->calibreName,
                'slug' => Str::slug($this->calibreName),
                'category' => $this->calibreCategory,
                'ignition_type' => $this->calibreIgnition,
                'aliases' => !empty($aliases) ? $aliases : null,
                'is_active' => true,
                'is_common' => $this->calibreIsCommon,
                'is_obsolete' => $this->calibreIsObsolete,
                'sort_order' => $this->calibreSortOrder,
            ]
        );

        $this->resetCalibreForm();
        session()->flash('success', 'Calibre saved successfully.');
    }

    public function editCalibre(Calibre $calibre): void
    {
        $this->editingCalibreId = $calibre->id;
        $this->calibreName = $calibre->name;
        $this->calibreCategory = $calibre->category;
        $this->calibreIgnition = $calibre->ignition_type;
        $this->calibreAliases = $calibre->aliases ? implode(', ', $calibre->aliases) : '';
        $this->calibreIsCommon = $calibre->is_common;
        $this->calibreIsObsolete = $calibre->is_obsolete;
        $this->calibreSortOrder = $calibre->sort_order;
    }

    public function deleteCalibre(Calibre $calibre): void
    {
        $calibre->delete();
        session()->flash('success', 'Calibre deleted.');
    }

    public function toggleCalibreCommon(Calibre $calibre): void
    {
        $calibre->update(['is_common' => !$calibre->is_common]);
    }

    public function toggleCalibreActive(Calibre $calibre): void
    {
        $calibre->update(['is_active' => !$calibre->is_active]);
    }

    public function resetCalibreForm(): void
    {
        $this->editingCalibreId = null;
        $this->calibreName = '';
        $this->calibreCategory = 'rifle';
        $this->calibreIgnition = 'centerfire';
        $this->calibreAliases = '';
        $this->calibreIsCommon = false;
        $this->calibreIsObsolete = false;
        $this->calibreSortOrder = 0;
    }

    public function clearFilters(): void
    {
        $this->categoryFilter = '';
        $this->ignitionFilter = '';
        $this->searchTerm = '';
        $this->resetPage();
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Firearm Settings</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Configure firearm types and calibres for the Virtual Safe and endorsements</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats Overview --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $calibreStats['total'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Calibres</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-blue-600">{{ $calibreStats['handgun'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Handgun</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-emerald-600">{{ $calibreStats['rifle'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Rifle</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-amber-600">{{ $calibreStats['shotgun'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Shotgun</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-purple-600">{{ $calibreStats['rimfire'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Rimfire</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-red-600">{{ $calibreStats['centerfire'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Centerfire</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-yellow-600">{{ $calibreStats['common'] }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Common</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4">
            <button wire:click="$set('activeTab', 'firearm-types')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'firearm-types' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Firearm Types
            </button>
            <button wire:click="$set('activeTab', 'calibres')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'calibres' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Calibres
            </button>
        </nav>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Category</label>
            <select wire:model.live="categoryFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All Categories</option>
                <option value="handgun">Handgun</option>
                <option value="rifle">Rifle</option>
                <option value="shotgun">Shotgun</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Ignition Type</label>
            <select wire:model.live="ignitionFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">All Types</option>
                <option value="rimfire">Rimfire</option>
                <option value="centerfire">Centerfire</option>
            </select>
        </div>
        @if($activeTab === 'calibres')
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Search</label>
            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search calibres..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        @endif
        @if($categoryFilter || $ignitionFilter || $searchTerm)
        <div class="self-end">
            <button wire:click="clearFilters" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                Clear Filters
            </button>
        </div>
        @endif
    </div>

    {{-- Firearm Types Tab --}}
    @if($activeTab === 'firearm-types')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Form --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
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
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingFirearmTypeId)
                            <button type="button" wire:click="resetFirearmTypeForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Ignition</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Action</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">Active</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($firearmTypes as $type)
                                <tr class="{{ !$type->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                        {{ $type->name }}
                                        @if($type->description)
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs">{{ $type->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium 
                                            {{ $type->category === 'handgun' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                            {{ $type->category === 'rifle' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : '' }}
                                            {{ $type->category === 'shotgun' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}">
                                            {{ ucfirst($type->category) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $type->ignition_type_label ?? 'Any' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $type->action_type_label ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="toggleFirearmTypeActive({{ $type->id }})" class="text-lg">
                                            @if($type->is_active)
                                                <span class="text-green-500">✓</span>
                                            @else
                                                <span class="text-zinc-300">○</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="editFirearmType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                        <button wire:click="deleteFirearmType({{ $type->id }})" wire:confirm="Are you sure you want to delete this firearm type?" class="ml-3 text-red-600 hover:text-red-700 text-sm">Delete</button>
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
    @endif

    {{-- Calibres Tab --}}
    @if($activeTab === 'calibres')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Form --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingCalibreId ? 'Edit' : 'Add' }} Calibre</h3>
                <form wire:submit="saveCalibre" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="calibreName" placeholder="e.g. .308 Winchester" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('calibreName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category <span class="text-red-500">*</span></label>
                            <select wire:model="calibreCategory" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="handgun">Handgun</option>
                                <option value="rifle">Rifle</option>
                                <option value="shotgun">Shotgun</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition <span class="text-red-500">*</span></label>
                            <select wire:model="calibreIgnition" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="rimfire">Rimfire</option>
                                <option value="centerfire">Centerfire</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Aliases (comma-separated)</label>
                        <input type="text" wire:model="calibreAliases" placeholder="e.g. .308 Win, .308, 7.62x51" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Alternative names users might search for</p>
                    </div>

                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="calibreIsCommon" class="rounded border-zinc-300 dark:border-zinc-600 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Common calibre</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="calibreIsObsolete" class="rounded border-zinc-300 dark:border-zinc-600 text-amber-600 focus:ring-amber-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Obsolete</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                        <input type="number" wire:model="calibreSortOrder" min="0" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingCalibreId)
                            <button type="button" wire:click="resetCalibreForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Ignition</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">Common</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">Active</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($calibres as $calibre)
                                <tr class="{{ !$calibre->is_active ? 'opacity-50' : '' }} {{ $calibre->is_obsolete ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            {{ $calibre->name }}
                                            @if($calibre->is_obsolete)
                                                <span class="inline-flex rounded px-1.5 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">Obsolete</span>
                                            @endif
                                        </div>
                                        @if($calibre->aliases && count($calibre->aliases) > 0)
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs">{{ implode(', ', array_slice($calibre->aliases, 0, 3)) }}{{ count($calibre->aliases) > 3 ? '...' : '' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium 
                                            {{ $calibre->category === 'handgun' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                            {{ $calibre->category === 'rifle' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : '' }}
                                            {{ $calibre->category === 'shotgun' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}
                                            {{ $calibre->category === 'other' ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' : '' }}">
                                            {{ ucfirst($calibre->category) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium 
                                            {{ $calibre->ignition_type === 'rimfire' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ ucfirst($calibre->ignition_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="toggleCalibreCommon({{ $calibre->id }})" class="text-lg">
                                            @if($calibre->is_common)
                                                <span class="text-yellow-500">★</span>
                                            @else
                                                <span class="text-zinc-300 dark:text-zinc-600">☆</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="toggleCalibreActive({{ $calibre->id }})" class="text-lg">
                                            @if($calibre->is_active)
                                                <span class="text-green-500">✓</span>
                                            @else
                                                <span class="text-zinc-300">○</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="editCalibre({{ $calibre->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                        <button wire:click="deleteCalibre({{ $calibre->id }})" wire:confirm="Are you sure you want to delete this calibre?" class="ml-3 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        No calibres found. Add one above or run the seeder.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                @if($calibres->hasPages())
                    <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $calibres->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
