{{-- Reusable typeable calibre search dropdown.
     Expects the host Livewire component to expose:
       - $calibreSearch (string), $calibre_id (?int), $showCalibreDropdown (bool)
       - selectCalibre(int) method and filteredCalibres computed
     Optional include data: $label, $note --}}
<div class="relative" x-data="{ open: @entangle('showCalibreDropdown') }" @click.away="open = false">
    <label for="calibre_search" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
        {{ $label ?? 'Calibre / Bore' }} <span class="text-red-500">*</span>
    </label>
    <div class="relative">
        <input
            type="text"
            id="calibre_search"
            wire:model.live.debounce.250ms="calibreSearch"
            x-on:focus="open = true"
            placeholder="Type to search calibre (e.g., 6.5 Creedmoor, .308 Win, 9mm)..."
            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue"
            autocomplete="off"
        />
        @if($calibre_id)
            <button
                type="button"
                wire:click="$set('calibre_id', null); $set('calibreSearch', '')"
                class="absolute right-2 top-2 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif

        @if($showCalibreDropdown && $this->filteredCalibres->count() > 0)
            <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                @foreach($this->filteredCalibres as $calibre)
                    <button
                        type="button"
                        wire:click="selectCalibre({{ $calibre->id }})"
                        x-on:click="open = false"
                        class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white flex items-center justify-between"
                    >
                        <span>{{ $calibre->name }}</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">{{ $calibre->category }}</span>
                    </button>
                @endforeach
            </div>
        @elseif($showCalibreDropdown && strlen($calibreSearch) >= 1 && $this->filteredCalibres->count() === 0)
            <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg p-4 text-sm text-zinc-500 dark:text-zinc-400">
                No calibres found matching "{{ $calibreSearch }}"
            </div>
        @endif
    </div>

    @if(!empty($note))
        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ $note }}</p>
    @endif
    @if($calibre_id)
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Selected: {{ \App\Models\FirearmCalibre::find($calibre_id)?->name ?? '' }}
        </p>
    @endif
    @error('calibre_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
</div>
