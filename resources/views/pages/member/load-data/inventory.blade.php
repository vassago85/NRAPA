<?php

use App\Models\ReloadingInventory;
use Livewire\Component;

new class extends Component {
    public string $search = '';
    public string $filterType = '';

    // Add/Edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $form_type = 'powder';
    public string $form_make = '';
    public string $form_name = '';
    public ?float $form_quantity = null;
    public string $form_unit = 'grams';
    public ?float $form_cost_per_unit = null;
    public string $form_notes = '';

    public function updatedFormType(): void
    {
        $defaults = ReloadingInventory::defaultUnits();
        $this->form_unit = $defaults[$this->form_type] ?? 'units';
    }

    public function addItem(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editItem(int $id): void
    {
        $item = ReloadingInventory::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->editingId = $item->id;
        $this->form_type = $item->type;
        $this->form_make = $item->make;
        $this->form_name = $item->name;
        $this->form_quantity = $item->quantity;
        $this->form_unit = $item->unit;
        $this->form_cost_per_unit = $item->cost_per_unit;
        $this->form_notes = $item->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_type' => ['required', 'in:powder,primer,bullet,brass'],
            'form_make' => ['required', 'string', 'max:255'],
            'form_name' => ['required', 'string', 'max:255'],
            'form_quantity' => ['required', 'numeric', 'min:0'],
            'form_unit' => ['required', 'string', 'max:50'],
            'form_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = [
            'user_id' => auth()->id(),
            'type' => $this->form_type,
            'make' => $this->form_make,
            'name' => $this->form_name,
            'quantity' => $this->form_quantity,
            'unit' => $this->form_unit,
            'cost_per_unit' => $this->form_cost_per_unit,
            'notes' => $this->form_notes ?: null,
        ];

        if ($this->editingId) {
            ReloadingInventory::where('id', $this->editingId)
                ->where('user_id', auth()->id())
                ->update(collect($data)->except('user_id')->toArray());
        } else {
            ReloadingInventory::create($data);
        }

        $this->resetForm();
        $this->showForm = false;

        session()->flash('success', $this->editingId ? 'Item updated.' : 'Item added to inventory.');
    }

    public function deleteItem(int $id): void
    {
        ReloadingInventory::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        session()->flash('success', 'Item removed from inventory.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form_type = 'powder';
        $this->form_make = '';
        $this->form_name = '';
        $this->form_quantity = null;
        $this->form_unit = 'grams';
        $this->form_cost_per_unit = null;
        $this->form_notes = '';
    }

    public function with(): array
    {
        $query = ReloadingInventory::where('user_id', auth()->id());

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('make', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%');
            });
        }

        $items = $query->orderBy('type')->orderBy('make')->orderBy('name')->get();

        return [
            'items' => $items,
            'groupedItems' => $items->groupBy('type'),
            'types' => ReloadingInventory::types(),
            'lowStockCount' => $items->filter(fn ($i) => $i->is_low_stock)->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('load-data.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Reloading Inventory</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Track your powder, primers, bullets, and brass</p>
                </div>
            </div>
            <button wire:click="addItem"
                    class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Item
            </button>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Low Stock Warning -->
    @if($lowStockCount > 0)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ $lowStockCount }} item(s) running low on stock</p>
            </div>
        </div>
    @endif

    <!-- Add/Edit Form Modal -->
    @if($showForm)
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingId ? 'Edit' : 'Add' }} Inventory Item</h2>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type *</label>
                        <select wire:model.live="form_type"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make *</label>
                        <input type="text" wire:model="form_make" placeholder="e.g., Hodgdon, CCI, Sierra"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_make') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                        <input type="text" wire:model="form_name" placeholder="e.g., H4350, BR2, MatchKing 168gr"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Quantity *</label>
                        <input type="number" wire:model="form_quantity" step="0.01" min="0"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Unit</label>
                        <input type="text" wire:model="form_unit"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Cost per Unit (R)</label>
                        <input type="number" wire:model="form_cost_per_unit" step="0.01" min="0"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <input type="text" wire:model="form_notes" placeholder="Optional notes..."
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                        {{ $editingId ? 'Update' : 'Add' }} Item
                    </button>
                    <button type="button" wire:click="$set('showForm', false)"
                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search inventory..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
        </div>
        <select wire:model.live="filterType"
                class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
            <option value="">All Types</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Inventory Grouped by Type -->
    @forelse($groupedItems as $type => $typeItems)
        <div class="mb-6">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-nrapa-blue">
                {{ $types[$type] ?? ucfirst($type) }}
                <span class="text-zinc-400 font-normal">({{ $typeItems->count() }})</span>
            </h3>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Make</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Unit</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Cost/Unit</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($typeItems as $item)
                            <tr class="{{ $item->is_low_stock ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ $item->make }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-zinc-900 dark:text-white">{{ $item->name }}</span>
                                    @if($item->is_low_stock)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Low</span>
                                    @endif
                                    @if($item->notes)
                                        <p class="text-xs text-zinc-400">{{ $item->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">{{ number_format($item->quantity, $type === 'powder' ? 0 : 0) }}</td>
                                <td class="px-4 py-3 text-right text-zinc-500">{{ $item->unit }}</td>
                                <td class="px-4 py-3 text-right text-zinc-500">{{ $item->cost_per_unit ? 'R' . number_format($item->cost_per_unit, 2) : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="editItem({{ $item->id }})"
                                                class="text-nrapa-blue hover:text-nrapa-blue-dark text-xs font-medium">
                                            Edit
                                        </button>
                                        <button wire:click="deleteItem({{ $item->id }})" wire:confirm="Delete this inventory item?"
                                                class="text-red-500 hover:text-red-700 text-xs font-medium ml-2">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">No inventory items yet</h3>
            <p class="mt-2 text-sm text-zinc-500">Start tracking your reloading components by adding items.</p>
            <button wire:click="addItem"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Add First Item
            </button>
        </div>
    @endforelse
</div>
