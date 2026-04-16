{{-- Identity --}}
<div class="rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Identity</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Manufacturer *</label>
            <input type="text" wire:model="manufacturer" list="mfr-list" placeholder="e.g., Hornady"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            <datalist id="mfr-list">
                @foreach($manufacturers as $m)
                    <option value="{{ $m }}">
                @endforeach
            </datalist>
            @error('manufacturer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Brand Line *</label>
            <input type="text" wire:model="brand_line" list="bl-list" placeholder="e.g., ELD Match"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            <datalist id="bl-list">
                @foreach($brandLines as $bl)
                    <option value="{{ $bl }}">
                @endforeach
            </datalist>
            @error('brand_line') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Bullet Label *</label>
            <input type="text" wire:model="bullet_label" placeholder="e.g., 6.5mm 140 gr ELD Match"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            @error('bullet_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mt-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Caliber *</label>
            <select wire:model.live="caliber_label" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                <option value="">Select caliber</option>
                @foreach($caliberDiameters as $label => $dims)
                    <option value="{{ $label }}">{{ $label }} ({{ $dims['in'] }}")</option>
                @endforeach
            </select>
            @error('caliber_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Weight (gr) *</label>
            <input type="number" wire:model="weight_gr" min="10" max="1000" placeholder="140"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            @error('weight_gr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SKU / Part No.</label>
            <input type="text" wire:model="sku_or_part_no" placeholder="Optional"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
    </div>
</div>

{{-- Dimensions --}}
<div class="rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Dimensions</h2>
    <p class="text-xs text-zinc-500 mb-4">Enter one unit and the other auto-calculates (1 in = 25.4 mm)</p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Diameter (in) *</label>
            <input type="number" wire:model.live.debounce.300ms="diameter_in" step="0.001" min="0.100" placeholder="0.264"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            @error('diameter_in') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Diameter (mm) *</label>
            <input type="number" wire:model.live.debounce.300ms="diameter_mm" step="0.001" min="2" placeholder="6.706"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            @error('diameter_mm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Length (in)</label>
            <input type="number" wire:model.live.debounce.300ms="length_in" step="0.001" min="0.100" placeholder="Optional"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Length (mm)</label>
            <input type="number" wire:model.live.debounce.300ms="length_mm" step="0.001" min="2" placeholder="Optional"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
    </div>
</div>

{{-- Ballistics --}}
<div class="rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Ballistic Coefficients</h2>
    <p class="text-xs text-zinc-500 mb-4">Only enter published values from official sources</p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">BC (G1)</label>
            <input type="number" wire:model="bc_g1" step="0.001" min="0.010" max="2" placeholder="0.646"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">BC (G7)</label>
            <input type="number" wire:model="bc_g7" step="0.001" min="0.010" max="2" placeholder="0.326"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">BC Reference</label>
            <input type="text" wire:model="bc_reference" placeholder="e.g., Mach 2.25"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
    </div>
</div>

{{-- Classification --}}
<div class="rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Classification & Source</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Construction *</label>
            <select wire:model="construction" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                @foreach($constructionTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Intended Use *</label>
            <select wire:model="intended_use" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                @foreach($intendedUses as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status *</label>
            <select wire:model="status" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                @foreach($statuses as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mt-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Twist Note</label>
            <input type="text" wire:model="twist_note" placeholder="e.g., Requires 1:8 twist or faster"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Source URL *</label>
            <div class="flex gap-2">
                <input type="url" wire:model="source_url" placeholder="https://..."
                       class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                @if($source_url)
                <a href="{{ $source_url }}" target="_blank" class="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700" title="Verify Source">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                @endif
            </div>
            @error('source_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Last Verified *</label>
            <input type="datetime-local" wire:model="last_verified_at"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            @error('last_verified_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
