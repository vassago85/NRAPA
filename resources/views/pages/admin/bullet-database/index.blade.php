<?php

use App\Models\Bullet;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bullet Database - Admin')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';
    #[Url]
    public string $manufacturer = '';
    #[Url]
    public string $brand_line = '';
    #[Url]
    public string $caliber = '';
    #[Url]
    public string $construction = '';
    #[Url]
    public string $intended_use = '';
    #[Url]
    public string $status = '';
    #[Url]
    public string $has_bc = '';
    #[Url]
    public string $has_length = '';
    #[Url]
    public string $sort = 'manufacturer';
    #[Url]
    public string $direction = 'asc';

    public ?int $weight_min = null;
    public ?int $weight_max = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->manufacturer = '';
        $this->brand_line = '';
        $this->caliber = '';
        $this->construction = '';
        $this->intended_use = '';
        $this->status = '';
        $this->has_bc = '';
        $this->has_length = '';
        $this->weight_min = null;
        $this->weight_max = null;
        $this->resetPage();
    }

    public function deleteBullet(int $id): void
    {
        Bullet::findOrFail($id)->delete();
        session()->flash('success', 'Bullet deleted.');
    }

    public function with(): array
    {
        $query = Bullet::query();

        if ($this->search) {
            $query->search($this->search);
        }
        if ($this->manufacturer) {
            $query->forManufacturer($this->manufacturer);
        }
        if ($this->brand_line) {
            $query->where('brand_line', $this->brand_line);
        }
        if ($this->caliber) {
            $query->forCaliber($this->caliber);
        }
        if ($this->construction) {
            $query->forConstruction($this->construction);
        }
        if ($this->intended_use) {
            $query->forUse($this->intended_use);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->has_bc === 'yes') {
            $query->hasBc();
        } elseif ($this->has_bc === 'no') {
            $query->whereNull('bc_g1')->whereNull('bc_g7');
        }
        if ($this->has_length === 'yes') {
            $query->hasLength();
        } elseif ($this->has_length === 'no') {
            $query->whereNull('length_in');
        }
        if ($this->weight_min || $this->weight_max) {
            $query->weightBetween($this->weight_min, $this->weight_max);
        }

        $allowedSorts = ['manufacturer', 'caliber_label', 'weight_gr', 'bc_g7', 'bc_g1', 'brand_line', 'last_verified_at', 'status'];
        $sortCol = in_array($this->sort, $allowedSorts) ? $this->sort : 'manufacturer';
        $query->orderBy($sortCol, $this->direction);
        if ($sortCol !== 'weight_gr') {
            $query->orderBy('weight_gr', 'asc');
        }

        // Get unique values for filter dropdowns
        $manufacturers = Bullet::select('manufacturer')->distinct()->orderBy('manufacturer')->pluck('manufacturer');
        $brandLines = Bullet::select('brand_line')->distinct()->orderBy('brand_line')->pluck('brand_line');
        $calibers = Bullet::select('caliber_label')->distinct()->orderBy('caliber_label')->pluck('caliber_label');

        return [
            'bullets' => $query->paginate(50),
            'manufacturers' => $manufacturers,
            'brandLines' => $brandLines,
            'calibers' => $calibers,
            'constructionTypes' => Bullet::constructionTypes(),
            'intendedUses' => Bullet::intendedUses(),
            'statuses' => Bullet::statuses(),
            'totalCount' => Bullet::count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Bullet Database</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $totalCount }} bullets in database</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bullet-database.import') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>
            <a href="{{ route('admin.bullet-database.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Bullet
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4" x-data="{ showFilters: false }">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search bullets, SKUs, manufacturers..."
                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400">
            </div>
            <button @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
            </button>
            @if($search || $manufacturer || $brand_line || $caliber || $construction || $intended_use || $status || $has_bc || $has_length || $weight_min || $weight_max)
            <button wire:click="resetFilters" class="text-xs text-red-500 hover:text-red-700">Clear All</button>
            @endif
        </div>

        <div x-show="showFilters" x-collapse class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Manufacturer</label>
                <select wire:model.live="manufacturer" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($manufacturers as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Brand Line</label>
                <select wire:model.live="brand_line" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($brandLines as $bl)
                        <option value="{{ $bl }}">{{ $bl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Caliber</label>
                <select wire:model.live="caliber" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($calibers as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Construction</label>
                <select wire:model.live="construction" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($constructionTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Intended Use</label>
                <select wire:model.live="intended_use" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($intendedUses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Status</label>
                <select wire:model.live="status" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">All</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Has BC</label>
                <select wire:model.live="has_bc" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">Any</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Has Length</label>
                <select wire:model.live="has_length" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
                    <option value="">Any</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Weight Min (gr)</label>
                <input type="number" wire:model.live.debounce.500ms="weight_min" placeholder="Min" min="0"
                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Weight Max (gr)</label>
                <input type="number" wire:model.live.debounce.500ms="weight_max" placeholder="Max" min="0"
                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-1.5 text-sm text-zinc-900 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50 sticky top-0">
                <tr>
                    @php
                        $cols = [
                            'manufacturer' => 'Manufacturer',
                            'brand_line' => 'Line',
                            'caliber_label' => 'Caliber',
                            'weight_gr' => 'Weight',
                            'bc_g1' => 'BC G1',
                            'bc_g7' => 'BC G7',
                        ];
                    @endphp
                    @foreach($cols as $col => $label)
                    <th wire:click="sortBy('{{ $col }}')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300 whitespace-nowrap">
                        {{ $label }}
                        @if($sort === $col)
                            <span class="ml-1">{{ $direction === 'asc' ? '&#9650;' : '&#9660;' }}</span>
                        @endif
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 whitespace-nowrap">Diameter</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 whitespace-nowrap">Construction</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 whitespace-nowrap">Use</th>
                    <th wire:click="sortBy('status')" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 cursor-pointer hover:text-zinc-700 whitespace-nowrap">
                        Status
                        @if($sort === 'status') <span class="ml-1">{{ $direction === 'asc' ? '&#9650;' : '&#9660;' }}</span> @endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @forelse($bullets as $bullet)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white whitespace-nowrap">{{ $bullet->manufacturer }}</td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $bullet->brand_line }}</td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $bullet->caliber_label }}</td>
                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white font-medium whitespace-nowrap">{{ $bullet->weight_gr }}gr</td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $bullet->bc_g1 ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $bullet->bc_g7 ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-zinc-500 whitespace-nowrap">{{ $bullet->diameter_in }}" / {{ $bullet->diameter_mm }}mm</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ match($bullet->construction) {
                                'monolithic_copper' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                'bonded' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                'fmj' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
                                default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            } }}">
                            {{ \App\Models\Bullet::constructionTypes()[$bullet->construction] ?? $bullet->construction }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ match($bullet->intended_use) {
                                'match' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'hunting' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                'tactical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
                            } }}">
                            {{ \App\Models\Bullet::intendedUses()[$bullet->intended_use] ?? $bullet->intended_use }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $bullet->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($bullet->status === 'discontinued' ? 'bg-red-100 text-red-800' : 'bg-zinc-100 text-zinc-600') }}">
                            {{ ucfirst($bullet->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-2">
                            @if($bullet->source_url)
                            <a href="{{ $bullet->source_url }}" target="_blank" title="Verify Source"
                               class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            @endif
                            <a href="{{ route('admin.bullet-database.edit', $bullet) }}" wire:navigate
                               class="text-nrapa-blue hover:text-nrapa-blue-dark text-xs font-medium">Edit</a>
                            <button wire:click="deleteBullet({{ $bullet->id }})" wire:confirm="Delete {{ $bullet->bullet_label }}?"
                                    class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-4 py-12 text-center text-sm text-zinc-500">
                        No bullets found. <a href="{{ route('admin.bullet-database.create') }}" wire:navigate class="text-nrapa-blue hover:underline">Add the first one</a> or
                        <a href="{{ route('admin.bullet-database.import') }}" wire:navigate class="text-nrapa-blue hover:underline">import from CSV</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($bullets->hasPages())
    <div class="mt-2">
        {{ $bullets->links() }}
    </div>
    @endif
</div>
