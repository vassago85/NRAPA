<div class="relative" x-data="{ open: @entangle('showDropdown').live }">
    {{-- Success message --}}
    @if(session('calibre-request-success'))
        <div class="mb-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
            {{ session('calibre-request-success') }}
        </div>
    @endif

    {{-- Selected calibre display --}}
    @if($selectedCalibre && !$showDropdown)
        <div class="flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2">
            <span class="flex-1 text-sm text-zinc-900 dark:text-white">{{ $selectedCalibre->name }}</span>
            <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="rounded px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-600">{{ $selectedCalibre->category_label }}</span>
                <span class="rounded px-1.5 py-0.5 {{ $selectedCalibre->ignition_type === 'rimfire' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">{{ $selectedCalibre->ignition_type_label }}</span>
            </span>
            <button type="button" wire:click="clearSelection" class="ml-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @else
        {{-- Search input --}}
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.200ms="search"
                   @focus="open = true"
                   @click.away="open = false"
                   placeholder="Type to search calibres..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 pr-10 text-sm text-zinc-900 dark:text-white placeholder-zinc-400">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- Dropdown results --}}
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="open = false"
             class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg max-h-64 overflow-y-auto">
            
            @if(strlen($search) >= 1)
                @forelse($calibres as $calibre)
                    <button type="button"
                            wire:click="selectCalibre({{ $calibre->id }})"
                            class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700 border-b border-zinc-100 dark:border-zinc-700 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $calibre->name }}</span>
                            @if($calibre->aliases && count($calibre->aliases) > 0)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    Also: {{ implode(', ', array_slice($calibre->aliases, 0, 2)) }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <span class="text-xs rounded px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300">{{ ucfirst($calibre->category) }}</span>
                            <span class="text-xs rounded px-1.5 py-0.5 {{ $calibre->ignition_type === 'rimfire' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' }}">{{ ucfirst($calibre->ignition_type) }}</span>
                            @if($calibre->is_common)
                                <span class="text-yellow-500 text-xs" title="Common calibre">★</span>
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="px-4 py-3 text-center">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No calibres found for "{{ $search }}"</p>
                    </div>
                @endforelse
                
                {{-- Request new calibre button --}}
                <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                    <button type="button"
                            wire:click="openRequestModal"
                            class="w-full flex items-center justify-center gap-2 text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Can't find your calibre? Request it
                    </button>
                </div>
            @else
                <div class="px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    Start typing to search calibres...
                </div>
            @endif
        </div>
    @endif

    {{-- Request Modal --}}
    @if($showRequestModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-zinc-500 dark:bg-zinc-900 bg-opacity-75 dark:bg-opacity-75" aria-hidden="true" wire:click="$set('showRequestModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-xl rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="modal-title">
                            Request New Calibre
                        </h3>
                        <button type="button" wire:click="$set('showRequestModal', false)" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        If your calibre is not listed, submit a request for admin approval. Once approved, it will be available for selection.
                    </p>

                    <form wire:submit="submitRequest" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="requestName" placeholder="e.g., .300 PRC" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('requestName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category <span class="text-red-500">*</span></label>
                                <select wire:model="requestCategory" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                    @foreach($categoryOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type <span class="text-red-500">*</span></label>
                                <select wire:model="requestIgnition" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                    @foreach($ignitionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Reason (optional)</label>
                            <textarea wire:model="requestReason" rows="2" placeholder="Why do you need this calibre? This helps admins review your request." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white"></textarea>
                            @error('requestReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="$set('showRequestModal', false)" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
