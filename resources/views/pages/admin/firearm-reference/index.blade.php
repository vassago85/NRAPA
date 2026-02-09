<?php

use App\Models\FirearmCalibre;
use App\Models\FirearmMake;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app.sidebar')] #[Title('Firearm Reference Data')] class extends Component {
    use WithPagination;

    public string $activeTab = 'calibres';
    public bool $showUploadModal = false;
    public string $uploadType = 'calibres';
    public $uploadFile = null;
    
    // Add form modals
    public bool $showAddCalibreModal = false;
    public bool $showAddMakeModal = false;
    public bool $showAddModelModal = false;
    
    // Calibre form fields
    public string $calibreName = '';
    public string $calibreCategory = 'rifle';
    public ?string $calibreIgnition = null;
    public ?string $calibreFamily = null;
    public ?float $calibreBulletDiameter = null;
    public ?float $calibreCaseLength = null;
    public ?string $calibreParent = null;
    public bool $calibreIsWildcat = false;
    public bool $calibreIsObsolete = false;
    public bool $calibreIsActive = true;
    public string $calibreAliases = ''; // Comma-separated
    
    // Make form fields
    public string $makeName = '';
    public ?string $makeCountry = null;
    public bool $makeIsActive = true;
    
    // Model form fields
    public ?int $modelMakeId = null;
    public string $modelName = '';
    public ?string $modelCategoryHint = null;
    public bool $modelIsActive = true;

    public function mount(): void
    {
        // Ensure user is admin/owner/dev
        if (!auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            abort(403);
        }
    }

    public function with(): array
    {
        $calibres = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        $makes = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        
        if ($this->activeTab === 'calibres' && Schema::hasTable('firearm_calibres')) {
            try {
                $calibres = FirearmCalibre::with('aliases')
                    ->orderBy('name')
                    ->paginate(50);
            } catch (\Exception $e) {
                // Keep empty paginator
            }
        }
        
        if ($this->activeTab === 'makes' && Schema::hasTable('firearm_makes')) {
            try {
                $makes = FirearmMake::withCount('models')
                    ->orderBy('name')
                    ->paginate(50);
            } catch (\Exception $e) {
                // Keep empty paginator
            }
        }
        
        $makesForModel = collect();
        if (Schema::hasTable('firearm_makes')) {
            try {
                $makesForModel = FirearmMake::active()->orderBy('name')->get();
            } catch (\Exception $e) {
                // Keep empty collection
            }
        }
        
        return [
            'calibres' => $calibres,
            'makes' => $makes,
            'makesForModel' => $makesForModel,
        ];
    }

    #[Computed(persist: true)]
    public function calibresCount(): int
    {
        if (!Schema::hasTable('firearm_calibres')) {
            return 0;
        }
        
        try {
            return FirearmCalibre::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    #[Computed(persist: true)]
    public function makesCount(): int
    {
        if (!Schema::hasTable('firearm_makes')) {
            return 0;
        }
        
        try {
            return FirearmMake::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    #[Computed]
    public function tablesExist(): bool
    {
        return Schema::hasTable('firearm_calibres') && 
               Schema::hasTable('firearm_makes') &&
               Schema::hasTable('firearm_models') &&
               Schema::hasTable('firearm_calibre_aliases');
    }

    public function uploadCsv(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        // Process CSV upload
        $path = $this->uploadFile->store('temp');
        $fullPath = storage_path("app/{$path}");

        try {
            \Artisan::call('nrapa:import-firearm-reference', [
                '--file' => $this->uploadType,
                '--force' => true,
            ]);

            session()->flash('success', "Successfully imported {$this->uploadType} data.");
            $this->closeUploadModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        } finally {
            \Storage::delete($path);
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploadFile = null;
    }

    // ===== Add Forms =====
    
    public function openAddCalibreModal(): void
    {
        $this->resetCalibreForm();
        $this->showAddCalibreModal = true;
    }

    public function closeAddCalibreModal(): void
    {
        $this->showAddCalibreModal = false;
        $this->resetCalibreForm();
    }

    public function resetCalibreForm(): void
    {
        $this->calibreName = '';
        $this->calibreCategory = 'rifle';
        $this->calibreIgnition = null;
        $this->calibreFamily = null;
        $this->calibreBulletDiameter = null;
        $this->calibreCaseLength = null;
        $this->calibreParent = null;
        $this->calibreIsWildcat = false;
        $this->calibreIsObsolete = false;
        $this->calibreIsActive = true;
        $this->calibreAliases = '';
    }

    public function saveCalibre(): void
    {
        $this->validate([
            'calibreName' => 'required|string|max:255',
            'calibreCategory' => 'required|in:rifle,shotgun,handgun',
            'calibreIgnition' => 'nullable|in:rimfire,centerfire',
            'calibreFamily' => 'nullable|string|max:255',
            'calibreBulletDiameter' => 'nullable|numeric|min:0|max:100',
            'calibreCaseLength' => 'nullable|numeric|min:0|max:200',
            'calibreParent' => 'nullable|string|max:255',
            'calibreIsWildcat' => 'boolean',
            'calibreIsObsolete' => 'boolean',
            'calibreIsActive' => 'boolean',
            'calibreAliases' => 'nullable|string',
        ]);

        try {
            // Check for duplicate
            $existing = FirearmCalibre::where('normalized_name', FirearmCalibre::normalize($this->calibreName))->first();
            if ($existing) {
                session()->flash('error', 'A calibre with this name already exists.');
                return;
            }

            $calibre = FirearmCalibre::create([
                'name' => $this->calibreName,
                'category' => $this->calibreCategory,
                'ignition' => $this->calibreIgnition,
                'family' => $this->calibreFamily,
                'bullet_diameter_mm' => $this->calibreBulletDiameter,
                'case_length_mm' => $this->calibreCaseLength,
                'parent' => $this->calibreParent,
                'is_wildcat' => $this->calibreIsWildcat,
                'is_obsolete' => $this->calibreIsObsolete,
                'is_active' => $this->calibreIsActive,
                'tags' => [],
            ]);

            // Add aliases if provided
            if (!empty($this->calibreAliases)) {
                $aliases = array_map('trim', explode(',', $this->calibreAliases));
                foreach ($aliases as $alias) {
                    if (!empty($alias)) {
                        \App\Models\FirearmCalibreAlias::create([
                            'firearm_calibre_id' => $calibre->id,
                            'alias' => $alias,
                        ]);
                    }
                }
            }

            session()->flash('success', 'Calibre added successfully.');
            $this->closeAddCalibreModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add calibre: ' . $e->getMessage());
        }
    }

    public function openAddMakeModal(): void
    {
        $this->resetMakeForm();
        $this->showAddMakeModal = true;
    }

    public function closeAddMakeModal(): void
    {
        $this->showAddMakeModal = false;
        $this->resetMakeForm();
    }

    public function resetMakeForm(): void
    {
        $this->makeName = '';
        $this->makeCountry = null;
        $this->makeIsActive = true;
    }

    public function saveMake(): void
    {
        $this->validate([
            'makeName' => 'required|string|max:255',
            'makeCountry' => 'nullable|string|max:255',
            'makeIsActive' => 'boolean',
        ]);

        try {
            // Check for duplicate
            $existing = FirearmMake::where('normalized_name', FirearmMake::normalize($this->makeName))->first();
            if ($existing) {
                session()->flash('error', 'A make with this name already exists.');
                return;
            }

            FirearmMake::create([
                'name' => $this->makeName,
                'country' => $this->makeCountry,
                'is_active' => $this->makeIsActive,
            ]);

            session()->flash('success', 'Make added successfully.');
            $this->closeAddMakeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add make: ' . $e->getMessage());
        }
    }

    public function openAddModelModal(): void
    {
        $this->resetModelForm();
        $this->showAddModelModal = true;
    }

    public function closeAddModelModal(): void
    {
        $this->showAddModelModal = false;
        $this->resetModelForm();
    }

    public function resetModelForm(): void
    {
        $this->modelMakeId = null;
        $this->modelName = '';
        $this->modelCategoryHint = null;
        $this->modelIsActive = true;
    }

    public function saveModel(): void
    {
        $this->validate([
            'modelMakeId' => 'required|exists:firearm_makes,id',
            'modelName' => 'required|string|max:255',
            'modelCategoryHint' => 'nullable|string|max:255',
            'modelIsActive' => 'boolean',
        ]);

        try {
            // Check for duplicate
            $existing = \App\Models\FirearmModel::where('firearm_make_id', $this->modelMakeId)
                ->where('normalized_name', \App\Models\FirearmModel::normalize($this->modelName))
                ->first();
            if ($existing) {
                session()->flash('error', 'A model with this name already exists for this make.');
                return;
            }

            \App\Models\FirearmModel::create([
                'firearm_make_id' => $this->modelMakeId,
                'name' => $this->modelName,
                'category_hint' => $this->modelCategoryHint,
                'is_active' => $this->modelIsActive,
            ]);

            session()->flash('success', 'Model added successfully.');
            $this->closeAddModelModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add model: ' . $e->getMessage());
        }
    }

    public function render(): mixed
    {
        return view('pages.admin.firearm-reference.index');
    }
}; ?>

<div wire:key="firearm-reference-main">
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Firearm Reference Data</h1>
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">Manage calibres, makes, and models reference data</p>
            </div>
            <div class="flex gap-3">
                @if($activeTab === 'calibres')
                    <button 
                        wire:click="openAddCalibreModal"
                        type="button"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        Add Calibre
                    </button>
                @elseif($activeTab === 'makes')
                    <button 
                        wire:click="openAddMakeModal"
                        type="button"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        Add Make
                    </button>
                    <button 
                        wire:click="openAddModelModal"
                        type="button"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
                    >
                        Add Model
                    </button>
                @endif
                <button 
                    wire:click="openUploadModal"
                    type="button"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors"
                >
                    Upload CSV
                </button>
            </div>
        </div>
    </div>

    @if(!$this->tablesExist)
        <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Database Setup Required</h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        The firearm reference tables have not been created yet. Please run the following commands:
                    </p>
                    <code class="block mt-2 px-3 py-2 bg-amber-100 dark:bg-amber-900/50 rounded text-xs text-amber-900 dark:text-amber-100">
                        php artisan migrate --force<br>
                        php artisan nrapa:import-firearm-reference
                    </code>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                wire:click="switchTab('calibres')"
                type="button"
                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'calibres' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                Calibres ({{ $this->calibresCount }})
            </button>
            <button
                wire:click="switchTab('makes')"
                type="button"
                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'makes' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                Makes ({{ $this->makesCount }})
            </button>
        </nav>
    </div>

    {{-- Calibres Tab --}}
    @if($activeTab === 'calibres')
        @if(!$this->tablesExist)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <p class="text-zinc-600 dark:text-zinc-400">Please run migrations to create the firearm reference tables.</p>
            </div>
        @elseif($calibres->count() === 0)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <p class="text-zinc-600 dark:text-zinc-400">No calibres found. Import reference data using the "Upload CSV" button.</p>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">SAPS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Ignition</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Family</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Aliases</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($calibres as $calibre)
                            <tr class="{{ !$calibre->is_active ? 'opacity-40' : '' }}">
                                <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-zinc-400 dark:text-zinc-500">
                                    {{ $calibre->saps_code ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $calibre->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $calibre->category_label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($calibre->ignition)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $calibre->ignition === 'rimfire' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                            {{ $calibre->ignition_label }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $calibre->family ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($calibre->aliases->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($calibre->aliases->take(3) as $alias)
                                                <span class="px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded text-xs">{{ $alias->alias }}</span>
                                            @endforeach
                                            @if($calibre->aliases->count() > 3)
                                                <span class="text-xs text-zinc-400">+{{ $calibre->aliases->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($calibre->is_active)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200 rounded">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($calibres->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $calibres->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Makes Tab --}}
    @if($activeTab === 'makes')
        @if(!$this->tablesExist)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <p class="text-zinc-600 dark:text-zinc-400">Please run migrations to create the firearm reference tables.</p>
            </div>
        @elseif($makes->count() === 0)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <p class="text-zinc-600 dark:text-zinc-400">No makes found. Import reference data using the "Upload CSV" button.</p>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">SAPS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Country</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Models</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($makes as $make)
                            <tr class="{{ !$make->is_active ? 'opacity-40' : '' }}">
                                <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-zinc-400 dark:text-zinc-500">
                                    {{ $make->saps_code ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $make->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $make->country ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $make->models_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($make->is_active)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200 rounded">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($makes->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $makes->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Upload Modal --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showUploadModal') }" x-show="show" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-900/50" @click="$wire.closeUploadModal()"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Upload CSV</h3>
                    
                    <form wire:submit.prevent="uploadCsv" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type</label>
                            <select wire:model="uploadType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                <option value="calibres">Calibres</option>
                                <option value="aliases">Calibre Aliases</option>
                                <option value="makes">Makes</option>
                                <option value="models">Models</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">CSV File</label>
                            <input type="file" wire:model="uploadFile" accept=".csv,.txt" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('uploadFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-3 justify-end">
                            <button type="button" wire:click="closeUploadModal" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Add Calibre Modal --}}
    @if($showAddCalibreModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showAddCalibreModal') }" x-show="show" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-900/50" @click="$wire.closeAddCalibreModal()"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Add New Calibre</h3>
                    
                    <form wire:submit.prevent="saveCalibre" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="calibreName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('calibreName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category <span class="text-red-500">*</span></label>
                                <select wire:model="calibreCategory" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="rifle">Rifle</option>
                                    <option value="shotgun">Shotgun</option>
                                    <option value="handgun">Handgun</option>
                                </select>
                                @error('calibreCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition</label>
                                <select wire:model="calibreIgnition" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">None</option>
                                    <option value="rimfire">Rimfire</option>
                                    <option value="centerfire">Centerfire</option>
                                </select>
                                @error('calibreIgnition') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Family</label>
                                <input type="text" wire:model="calibreFamily" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('calibreFamily') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bullet Diameter (mm)</label>
                                <input type="number" step="0.01" wire:model="calibreBulletDiameter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('calibreBulletDiameter') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Case Length (mm)</label>
                                <input type="number" step="0.01" wire:model="calibreCaseLength" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('calibreCaseLength') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Parent Calibre</label>
                            <input type="text" wire:model="calibreParent" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('calibreParent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Aliases (comma-separated)</label>
                            <input type="text" wire:model="calibreAliases" placeholder="e.g., .308 Win, 7.62x51" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('calibreAliases') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="calibreIsWildcat" class="rounded border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Wildcat</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="calibreIsObsolete" class="rounded border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Obsolete</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="calibreIsActive" class="rounded border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-3 justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="closeAddCalibreModal" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Add Calibre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Add Make Modal --}}
    @if($showAddMakeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showAddMakeModal') }" x-show="show" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-900/50" @click="$wire.closeAddMakeModal()"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Add New Make</h3>
                    
                    <form wire:submit.prevent="saveMake" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="makeName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('makeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country</label>
                            <input type="text" wire:model="makeCountry" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('makeCountry') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="makeIsActive" class="rounded border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-3 justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="closeAddMakeModal" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Add Make
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Add Model Modal --}}
    @if($showAddModelModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showAddModelModal') }" x-show="show" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-zinc-900/50" @click="$wire.closeAddModelModal()"></div>
                <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Add New Model</h3>
                    
                    <form wire:submit.prevent="saveModel" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make <span class="text-red-500">*</span></label>
                            <select wire:model="modelMakeId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                <option value="">Select a make...</option>
                                @foreach($makesForModel as $make)
                                    <option value="{{ $make->id }}">{{ $make->name }}</option>
                                @endforeach
                            </select>
                            @error('modelMakeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="modelName" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('modelName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category Hint</label>
                            <input type="text" wire:model="modelCategoryHint" placeholder="e.g., rifle, handgun" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('modelCategoryHint') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="modelIsActive" class="rounded border-zinc-300 dark:border-zinc-600">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                            </label>
                        </div>

                        <div class="flex gap-3 justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <button type="button" wire:click="closeAddModelModal" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                                Add Model
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
