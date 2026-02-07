<?php

use App\Models\ReloadingInventory;
use App\Models\InventoryPurchase;
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
    public string $form_notes = '';

    // Purchase form (used for both new items and restock)
    public bool $showRestockForm = false;
    public ?int $restockItemId = null;
    public int $purchase_qty = 1;
    public string $purchase_unit_size = '453.592';
    public string $purchase_unit_label = '1 lb bottle';
    public ?float $purchase_price = null;
    public ?string $purchase_date = null;
    public string $purchase_notes = '';

    // Purchase history
    public ?int $showHistoryId = null;

    public function mount(): void
    {
        $this->purchase_date = now()->format('Y-m-d');
    }

    public function updatedFormType(): void
    {
        $units = ReloadingInventory::purchaseUnits();
        $first = $units[$this->form_type][0] ?? null;
        if ($first) {
            $this->purchase_unit_size = $first['value'];
            $this->purchase_unit_label = $first['label'];
        }
    }

    public function addItem(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->showRestockForm = false;
        $this->updatedFormType();
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
        $this->form_notes = $item->notes ?? '';
        $this->showForm = true;
        $this->showRestockForm = false;
    }

    public function save(): void
    {
        if ($this->editingId) {
            // Editing existing item details (make, name, notes)
            $this->validate([
                'form_make' => ['required', 'string', 'max:255'],
                'form_name' => ['required', 'string', 'max:255'],
            ]);

            ReloadingInventory::where('id', $this->editingId)
                ->where('user_id', auth()->id())
                ->update([
                    'make' => $this->form_make,
                    'name' => $this->form_name,
                    'notes' => $this->form_notes ?: null,
                ]);

            $this->showForm = false;
            session()->flash('success', 'Item updated.');
        } else {
            // Creating new item + first purchase
            $this->validate([
                'form_type' => ['required', 'in:powder,primer,bullet,brass'],
                'form_make' => ['required', 'string', 'max:255'],
                'form_name' => ['required', 'string', 'max:255'],
                'purchase_qty' => ['required', 'integer', 'min:1'],
                'purchase_unit_size' => ['required', 'numeric', 'min:0.01'],
                'purchase_price' => ['required', 'numeric', 'min:0.01'],
                'purchase_date' => ['required', 'date'],
            ]);

            $defaults = ReloadingInventory::defaultUnits();
            $unitSize = (float) $this->purchase_unit_size;
            $qtyAdded = $this->purchase_qty * $unitSize;
            $pricePerBase = $this->purchase_price / $qtyAdded;

            $item = ReloadingInventory::create([
                'user_id' => auth()->id(),
                'type' => $this->form_type,
                'make' => $this->form_make,
                'name' => $this->form_name,
                'quantity' => $qtyAdded,
                'unit' => $defaults[$this->form_type] ?? 'units',
                'cost_per_unit' => $pricePerBase,
                'notes' => $this->form_notes ?: null,
            ]);

            InventoryPurchase::create([
                'reloading_inventory_id' => $item->id,
                'quantity_purchased' => $this->purchase_qty,
                'purchase_unit_size' => $unitSize,
                'purchase_unit_label' => $this->purchase_unit_label,
                'quantity_added' => $qtyAdded,
                'price_paid' => $this->purchase_price,
                'price_per_base_unit' => $pricePerBase,
                'purchased_at' => $this->purchase_date,
                'notes' => $this->purchase_notes ?: null,
            ]);

            $this->showForm = false;
            session()->flash('success', $this->form_make . ' ' . $this->form_name . ' added with first purchase recorded.');
        }

        $this->resetForm();
    }

    public function startRestock(int $id): void
    {
        $item = ReloadingInventory::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->restockItemId = $item->id;
        $this->purchase_qty = 1;
        $this->purchase_price = null;
        $this->purchase_date = now()->format('Y-m-d');
        $this->purchase_notes = '';

        // Pre-fill from last purchase
        $lastPurchase = $item->purchases()->first();
        if ($lastPurchase) {
            $this->purchase_unit_size = (string) $lastPurchase->purchase_unit_size;
            $this->purchase_unit_label = $lastPurchase->purchase_unit_label;
        } else {
            $units = ReloadingInventory::purchaseUnits();
            $first = $units[$item->type][0] ?? null;
            if ($first) {
                $this->purchase_unit_size = $first['value'];
                $this->purchase_unit_label = $first['label'];
            }
        }

        $this->showRestockForm = true;
        $this->showForm = false;
    }

    public function saveRestock(): void
    {
        $this->validate([
            'restockItemId' => ['required', 'integer'],
            'purchase_qty' => ['required', 'integer', 'min:1'],
            'purchase_unit_size' => ['required', 'numeric', 'min:0.01'],
            'purchase_price' => ['required', 'numeric', 'min:0.01'],
            'purchase_date' => ['required', 'date'],
        ]);

        $item = ReloadingInventory::where('id', $this->restockItemId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $unitSize = (float) $this->purchase_unit_size;
        $qtyAdded = $this->purchase_qty * $unitSize;
        $pricePerBase = $this->purchase_price / $qtyAdded;

        // Record the purchase
        InventoryPurchase::create([
            'reloading_inventory_id' => $item->id,
            'quantity_purchased' => $this->purchase_qty,
            'purchase_unit_size' => $unitSize,
            'purchase_unit_label' => $this->purchase_unit_label,
            'quantity_added' => $qtyAdded,
            'price_paid' => $this->purchase_price,
            'price_per_base_unit' => $pricePerBase,
            'purchased_at' => $this->purchase_date,
            'notes' => $this->purchase_notes ?: null,
        ]);

        // Update inventory
        $item->increment('quantity', $qtyAdded);
        $item->update(['cost_per_unit' => $pricePerBase]);

        $this->showRestockForm = false;
        $this->restockItemId = null;
        session()->flash('success', $item->display_name . ' restocked — ' . number_format($qtyAdded, 0) . ' ' . $item->unit . ' added.');
    }

    public function toggleHistory(int $id): void
    {
        $this->showHistoryId = $this->showHistoryId === $id ? null : $id;
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
        $this->form_notes = '';
        $this->purchase_qty = 1;
        $this->purchase_price = null;
        $this->purchase_date = now()->format('Y-m-d');
        $this->purchase_notes = '';
    }

    public function with(): array
    {
        $query = ReloadingInventory::where('user_id', auth()->id())
            ->with(['purchases']);

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
            'purchaseUnits' => ReloadingInventory::purchaseUnits(),
            'lowStockCount' => $items->filter(fn ($i) => $i->is_low_stock)->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Virtual Safe</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Track your powder, primers, bullets, and brass purchases</p>
            </div>
            <button wire:click="addItem"
                    class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Component
            </button>
        </div>
        <x-virtual-safe-tabs current="inventory" />
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

    <!-- Add New Item Form -->
    @if($showForm && !$editingId)
        <div class="mb-6 rounded-lg border border-nrapa-blue/30 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Add New Component</h2>
            <p class="text-sm text-zinc-500 mb-4">Enter what you bought — we'll track the price and stock for you.</p>
            <form wire:submit="save" class="space-y-5">
                <!-- Component Details -->
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
                        <input type="text" wire:model="form_make" placeholder="{{ match($form_type) { 'powder' => 'e.g., Hodgdon, Vihtavuori, Somchem', 'primer' => 'e.g., CCI, Federal, Murom', 'bullet' => 'e.g., Sierra, Hornady, Lapua', 'brass' => 'e.g., Lapua, Norma, Hornady' } }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_make') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                        <input type="text" wire:model="form_name" placeholder="{{ match($form_type) { 'powder' => 'e.g., H4350, N555, S365', 'primer' => 'e.g., BR2, 210M, KVB-7', 'bullet' => 'e.g., MatchKing 168gr, ELD-X 143gr', 'brass' => 'e.g., .308 Win, 6.5 Creedmoor' } }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- First Purchase -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">First Purchase</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">How many *</label>
                            <input type="number" wire:model="purchase_qty" min="1" placeholder="1"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Size / Pack *</label>
                            <select wire:model="purchase_unit_size"
                                    x-on:change="$wire.set('purchase_unit_label', $event.target.selectedOptions[0].text)"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                @foreach($purchaseUnits[$form_type] ?? [] as $pu)
                                    <option value="{{ $pu['value'] }}">{{ $pu['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Total Price Paid (R) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-400">R</span>
                                <input type="number" wire:model="purchase_price" step="0.01" min="0" placeholder="{{ match($form_type) { 'powder' => 'e.g., 2000', 'primer' => 'e.g., 180', 'bullet' => 'e.g., 650', 'brass' => 'e.g., 1500' } }}"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 pl-7 pr-4 py-2 text-sm text-zinc-900 dark:text-white">
                            </div>
                            @error('purchase_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Date</label>
                            <input type="date" wire:model="purchase_date"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <input type="text" wire:model="form_notes" placeholder="Optional notes..."
                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                        Add to Inventory
                    </button>
                    <button type="button" wire:click="$set('showForm', false)"
                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Edit Item Form (details only, not purchase) -->
    @if($showForm && $editingId)
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Edit Item Details</h2>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make *</label>
                        <input type="text" wire:model="form_make"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_make') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                        <input type="text" wire:model="form_name"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                        <input type="text" wire:model="form_notes"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">Update</button>
                    <button type="button" wire:click="$set('showForm', false)"
                            class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <!-- Restock Form -->
    @if($showRestockForm && $restockItemId)
        @php $restockItem = $items->firstWhere('id', $restockItemId); @endphp
        @if($restockItem)
            <div class="mb-6 rounded-lg border border-nrapa-orange/30 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">
                    Buy More: {{ $restockItem->display_name }}
                </h2>
                <p class="text-sm text-zinc-500 mb-4">Current stock: {{ $restockItem->stock_display }} {{ $restockItem->unit }}</p>
                <form wire:submit="saveRestock" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">How many *</label>
                            <input type="number" wire:model="purchase_qty" min="1"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Size / Pack *</label>
                            <select wire:model="purchase_unit_size"
                                    x-on:change="$wire.set('purchase_unit_label', $event.target.selectedOptions[0].text)"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                @foreach($purchaseUnits[$restockItem->type] ?? [] as $pu)
                                    <option value="{{ $pu['value'] }}" @if($purchase_unit_size == $pu['value']) selected @endif>{{ $pu['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Total Price Paid (R) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-400">R</span>
                                <input type="number" wire:model="purchase_price" step="0.01" min="0"
                                       class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 pl-7 pr-4 py-2 text-sm text-zinc-900 dark:text-white">
                            </div>
                            @error('purchase_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Date</label>
                            <input type="date" wire:model="purchase_date"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 mb-1">Notes</label>
                        <input type="text" wire:model="purchase_notes" placeholder="e.g., Bought from Safari Outdoor"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-nrapa-orange px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-orange-dark">
                            Record Purchase
                        </button>
                        <button type="button" wire:click="$set('showRestockForm', false)"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400">Cancel</button>
                    </div>
                </form>
            </div>
        @endif
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
            <div class="space-y-3">
                @foreach($typeItems as $item)
                    <div class="rounded-lg border {{ $item->is_low_stock ? 'border-amber-300 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-800 overflow-hidden">
                        <!-- Item Row -->
                        <div class="flex items-center justify-between px-5 py-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ $item->make }} {{ $item->name }}</h4>
                                    @if($item->is_low_stock)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Low Stock</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 mt-1 text-sm text-zinc-500">
                                    <span class="font-medium {{ $item->is_low_stock ? 'text-amber-600' : 'text-zinc-900 dark:text-white' }}">{{ $item->stock_display }} {{ $item->unit }}</span>
                                    @if($item->friendly_price)
                                        <span class="text-nrapa-orange font-medium">{{ $item->friendly_price }}</span>
                                    @endif
                                    @if($item->purchases->count() > 0)
                                        <span>{{ $item->purchases->count() }} purchase{{ $item->purchases->count() > 1 ? 's' : '' }}</span>
                                    @endif
                                </div>
                                @if($item->notes)
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $item->notes }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="startRestock({{ $item->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg bg-nrapa-orange/10 text-nrapa-orange px-3 py-1.5 text-xs font-medium hover:bg-nrapa-orange/20">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Buy More
                                </button>
                                <button wire:click="toggleHistory({{ $item->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 dark:border-zinc-600 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    History
                                </button>
                                <button wire:click="editItem({{ $item->id }})"
                                        class="text-nrapa-blue hover:text-nrapa-blue-dark text-xs font-medium px-2 py-1.5">
                                    Edit
                                </button>
                                <button wire:click="deleteItem({{ $item->id }})" wire:confirm="Delete {{ $item->display_name }} and all purchase history?"
                                        class="text-red-500 hover:text-red-700 text-xs font-medium px-2 py-1.5">
                                    Delete
                                </button>
                            </div>
                        </div>

                        <!-- Purchase History (expandable) -->
                        @if($showHistoryId === $item->id)
                            <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-5 py-4">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Purchase History</h4>
                                @if($item->purchases->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($item->purchases as $purchase)
                                            <div class="flex items-center justify-between text-sm rounded-lg bg-white dark:bg-zinc-800 px-4 py-2.5 border border-zinc-100 dark:border-zinc-700">
                                                <div>
                                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $purchase->display }}</span>
                                                    @if($purchase->notes)
                                                        <span class="text-zinc-400 ml-2">— {{ $purchase->notes }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-4 text-xs text-zinc-500">
                                                    @if($type === 'powder')
                                                        <span class="text-nrapa-orange font-medium">~R{{ number_format($purchase->price_per_base_unit * 453.592, 0) }}/lb</span>
                                                    @else
                                                        <span class="text-nrapa-orange font-medium">R{{ number_format($purchase->price_per_base_unit, 2) }}/unit</span>
                                                    @endif
                                                    <span>{{ $purchase->purchased_at->format('d M Y') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-zinc-400">No purchases recorded yet. Use "Buy More" to start tracking.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">No inventory items yet</h3>
            <p class="mt-2 text-sm text-zinc-500">Start tracking your reloading components and purchase prices.</p>
            <button wire:click="addItem"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark">
                Add First Component
            </button>
        </div>
    @endforelse
</div>
