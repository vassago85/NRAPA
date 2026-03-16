@php
use Illuminate\Support\Facades\Schema;
@endphp
<div class="space-y-6">
    @if(!Schema::hasTable('firearm_calibres'))
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Database Setup Required</h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        The firearm reference tables have not been created yet. Please run:
                    </p>
                    <code class="block mt-2 px-3 py-2 bg-amber-100 dark:bg-amber-900/50 rounded text-xs text-amber-900 dark:text-amber-100">
                        php artisan migrate --force<br>
                        php artisan nrapa:import-firearm-reference
                    </code>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Calibre Search Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Calibre</h3>
        
        <div class="space-y-4">
            {{-- Calibre Search Input --}}
            <div class="relative">
                <input 
                    type="text"
                    wire:model.live.debounce.250ms="calibreSearch"
                    placeholder="Search calibre (e.g., 6.5 Creedmoor, .308 Win, 9mm)..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    @focus="$wire.calibreSearch = $event.target.value"
                />
                
                @if($firearmCalibreId || $calibreTextOverride)
                    <button 
                        wire:click="clearCalibre"
                        class="absolute right-2 top-2 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
                        type="button"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif

                {{-- Suggestions Dropdown --}}
                @if(strlen($calibreSearch) >= 2 && !$firearmCalibreId && !$calibreTextOverride)
                    <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        @forelse($this->calibreSuggestions as $calibre)
                            <button
                                type="button"
                                wire:click="selectCalibre({{ $calibre->id }})"
                                class="w-full px-4 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between"
                            >
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $calibre->name }}</div>
                                    @if($calibre->family)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $calibre->family }}</div>
                                    @endif
                                </div>
                                <span class="text-xs text-zinc-400">{{ $calibre->category_label }}</span>
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                No calibres found. 
                                <button 
                                    type="button"
                                    wire:click="useCustomCalibre"
                                    class="text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Use custom value
                                </button>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Selected Calibre Metadata --}}
            @if($this->selectedCalibre)
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Category:</span>
                            <span class="font-medium text-zinc-900 dark:text-white ml-2">{{ $this->selectedCalibre->category_label }}</span>
                        </div>
                        @if($this->selectedCalibre->family)
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Family:</span>
                                <span class="font-medium text-zinc-900 dark:text-white ml-2">{{ $this->selectedCalibre->family }}</span>
                            </div>
                        @endif
                        @if($bulletDiameter = $this->selectedCalibre->bullet_diameter_display)
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Bullet Diameter:</span>
                                <span class="font-medium text-zinc-900 dark:text-white ml-2">{{ $bulletDiameter['value'] }}{{ $bulletDiameter['unit'] }}</span>
                            </div>
                        @endif
                        @if($this->selectedCalibre->tags && count($this->selectedCalibre->tags) > 0)
                            <div class="col-span-2 md:col-span-4">
                                <span class="text-zinc-500 dark:text-zinc-400">Tags:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($this->selectedCalibre->tags as $tag)
                                        <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Custom Calibre Override --}}
            @if($calibreTextOverride || $showCalibreOverride)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-amber-800 dark:text-amber-200">
                            Using custom calibre: <strong>{{ $calibreTextOverride }}</strong>
                        </span>
                        <button 
                            wire:click="clearCalibre"
                            class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200"
                            type="button"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            @endif

            {{-- SAPS Calibre Code --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    SAPS Calibre Code (Optional)
                </label>
                <input 
                    type="text"
                    wire:model="calibreCode"
                    placeholder="e.g., 1300122"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                />
            </div>
        </div>
    </div>

    {{-- Make/Model Search Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Make & Model</h3>
        
        <div class="space-y-4">
            {{-- Make Search --}}
            <div class="relative">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                <input 
                    type="text"
                    wire:model.live.debounce.250ms="makeSearch"
                    placeholder="Search make (e.g., Glock, Howa, Tikka)..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                />
                
                @if($firearmMakeId || $makeTextOverride)
                    <button 
                        wire:click="clearMake"
                        class="absolute right-2 top-8 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
                        type="button"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif

                {{-- Make Suggestions --}}
                @if(strlen($makeSearch) >= 2 && !$firearmMakeId && !$makeTextOverride)
                    <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        @forelse($this->makeSuggestions as $make)
                            <button
                                type="button"
                                wire:click="selectMake({{ $make->id }})"
                                class="w-full px-4 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between"
                            >
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $make->name }}</span>
                                @if($make->country)
                                    <span class="text-xs text-zinc-400">{{ $make->country }}</span>
                                @endif
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                No makes found. 
                                <button 
                                    type="button"
                                    wire:click="useCustomMake"
                                    class="text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Use custom value
                                </button>
                            </div>
                        @endforelse
                    </div>
                @endif

                {{-- Custom Make Override --}}
                @if($makeTextOverride || $showMakeOverride)
                    <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-amber-800 dark:text-amber-200">
                                Using custom make: <strong>{{ $makeTextOverride }}</strong>
                            </span>
                            <button 
                                wire:click="clearMake"
                                class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200"
                                type="button"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Model Search --}}
            <div class="relative">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                <input 
                    type="text"
                    wire:model.live.debounce.250ms="modelSearch"
                    placeholder="Search model..."
                    @disabled(!$firearmMakeId && !$makeTextOverride)
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
                />
                
                @if($firearmModelId || $modelTextOverride)
                    <button 
                        wire:click="clearModel"
                        class="absolute right-2 top-8 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
                        type="button"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif

                {{-- Model Suggestions --}}
                @if(strlen($modelSearch) >= 2 && !$firearmModelId && !$modelTextOverride && ($firearmMakeId || $makeTextOverride))
                    <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        @forelse($this->modelSuggestions as $model)
                            <button
                                type="button"
                                wire:click="selectModel({{ $model->id }})"
                                class="w-full px-4 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            >
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $model->name }}</span>
                            </button>
                        @empty
                            <div class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                No models found. 
                                <button 
                                    type="button"
                                    wire:click="useCustomModel"
                                    class="text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Use custom value
                                </button>
                            </div>
                        @endforelse
                    </div>
                @endif

                {{-- Custom Model Override --}}
                @if($modelTextOverride || $showModelOverride)
                    <div class="mt-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-amber-800 dark:text-amber-200">
                                Using custom model: <strong>{{ $modelTextOverride }}</strong>
                            </span>
                            <button 
                                wire:click="clearModel"
                                class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-200"
                                type="button"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Serial Numbers Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Serial Numbers (SAPS 271)</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">At least one serial number is required.</p>
        
        <div class="space-y-4">
            {{-- Barrel Serial --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Barrel Serial Number
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="barrelSerialNumber"
                        placeholder="Barrel serial..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Barrel Make
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="barrelMakeText"
                        placeholder="Barrel make..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
            </div>

            {{-- Frame Serial --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Frame Serial Number
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="frameSerialNumber"
                        placeholder="Frame serial..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Frame Make
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="frameMakeText"
                        placeholder="Frame make..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
            </div>

            {{-- Receiver Serial --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Receiver Serial Number
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="receiverSerialNumber"
                        placeholder="Receiver serial..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Receiver Make
                    </label>
                    <input 
                        type="text"
                        wire:model.blur="receiverMakeText"
                        placeholder="Receiver make..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    />
                </div>
            </div>
        </div>
    </div>
</div>
