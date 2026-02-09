<?php

use App\Models\Bullet;
use App\Models\InventoryLog;
use App\Models\LoadData;
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

    // Bullet-specific fields
    public ?int $bullet_library_id = null;
    public ?float $form_bullet_weight = null;
    public ?float $form_bullet_bc = null;
    public ?float $form_bullet_bc_g7 = null;
    public string $form_bullet_bc_type = 'G1';
    public string $form_bullet_type = '';
    public string $form_calibre = '';

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

    // Low stock threshold (entered in display units: grains for powder, count for others)
    public ?float $form_low_stock_threshold = null;

    // Manual adjustment
    public bool $showAdjustForm = false;
    public ?int $adjustItemId = null;
    public string $adjust_type = 'add';      // add, remove, set
    public ?float $adjust_quantity = null;
    public string $adjust_unit_size = '1';    // multiplier (e.g., 453.592 for 1lb powder)
    public string $adjust_unit_label = 'Per unit';
    public string $adjust_reason = '';

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

    public function updatedBulletLibraryId($value): void
    {
        if ($value) {
            $bullet = Bullet::find($value);
            if ($bullet) {
                $this->form_make = $bullet->manufacturer;
                $this->form_name = $bullet->brand_line . ' ' . $bullet->weight_gr . 'gr';
                $this->form_bullet_weight = $bullet->weight_gr;
                $this->form_bullet_bc = $bullet->bc_g1;
                $this->form_bullet_bc_g7 = $bullet->bc_g7;
                $this->form_bullet_bc_type = $bullet->bc_g1 ? 'G1' : ($bullet->bc_g7 ? 'G7' : 'G1');
                $this->form_bullet_type = $bullet->brand_line;
                $this->form_calibre = $bullet->caliber_label;
            }
        }
    }

    public function addItem(?string $type = null): void
    {
        $this->resetForm();
        if ($type && in_array($type, ['powder', 'primer', 'bullet', 'brass'])) {
            $this->form_type = $type;
        }
        $this->showForm = true;
        $this->showRestockForm = false;
        $this->showAdjustForm = false;
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
        $this->form_bullet_weight = $item->bullet_weight;
        $this->form_bullet_bc = $item->bullet_bc;
        $this->form_bullet_bc_type = $item->bullet_bc_type ?? 'G1';
        $this->form_bullet_type = $item->bullet_type ?? '';
        $this->form_calibre = $item->calibre ?? '';

        // Convert threshold from storage units (grams for powder, count for others) to display units
        if ($item->low_stock_threshold !== null) {
            $this->form_low_stock_threshold = $item->type === 'powder'
                ? round($item->low_stock_threshold * 15.4324, 0)
                : (float) $item->low_stock_threshold;
        } else {
            // Show default as placeholder — leave null so placeholder shows
            $this->form_low_stock_threshold = null;
        }

        $this->showForm = true;
        $this->showRestockForm = false;
        $this->showAdjustForm = false;
    }

    public function save(): void
    {
        if ($this->editingId) {
            // Editing existing item details (make, name, notes + bullet fields)
            $rules = [
                'form_make' => ['required', 'string', 'max:255'],
                'form_name' => ['required', 'string', 'max:255'],
                'form_low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            ];
            if ($this->form_type === 'bullet') {
                $rules['form_bullet_weight'] = ['required', 'numeric', 'min:1', 'max:999'];
                $rules['form_calibre'] = ['required', 'string', 'max:50'];
            }
            $this->validate($rules);

            // Convert threshold from display units to storage units
            $thresholdValue = null;
            if ($this->form_low_stock_threshold !== null && $this->form_low_stock_threshold !== '') {
                $thresholdValue = $this->form_type === 'powder'
                    ? (float) $this->form_low_stock_threshold / 15.4324  // grains → grams
                    : (float) $this->form_low_stock_threshold;
            }

            $updateData = [
                'make' => $this->form_make,
                'name' => $this->form_name,
                'notes' => $this->form_notes ?: null,
                'low_stock_threshold' => $thresholdValue,
            ];

            if ($this->form_type === 'bullet') {
                $updateData['bullet_weight'] = $this->form_bullet_weight;
                $updateData['bullet_bc'] = $this->form_bullet_bc;
                $updateData['bullet_bc_type'] = $this->form_bullet_bc_type;
                $updateData['bullet_type'] = $this->form_bullet_type ?: null;
                $updateData['calibre'] = $this->form_calibre ?: null;
            }

            ReloadingInventory::where('id', $this->editingId)
                ->where('user_id', auth()->id())
                ->update($updateData);

            $this->showForm = false;
            session()->flash('success', 'Item updated.');
        } else {
            // Creating new item + first purchase
            $rules = [
                'form_type' => ['required', 'in:powder,primer,bullet,brass'],
                'form_make' => ['required', 'string', 'max:255'],
                'form_name' => ['required', 'string', 'max:255'],
                'purchase_qty' => ['required', 'integer', 'min:1'],
                'purchase_unit_size' => ['required', 'numeric', 'min:0.01'],
                'purchase_price' => ['required', 'numeric', 'min:0.01'],
                'purchase_date' => ['required', 'date'],
            ];
            if ($this->form_type === 'bullet') {
                $rules['form_bullet_weight'] = ['required', 'numeric', 'min:1', 'max:999'];
                $rules['form_calibre'] = ['required', 'string', 'max:50'];
            }
            $this->validate($rules);

            $defaults = ReloadingInventory::defaultUnits();
            $unitSize = (float) $this->purchase_unit_size;
            $qtyAdded = $this->purchase_qty * $unitSize;
            $pricePerBase = $this->purchase_price / $qtyAdded;

            $createData = [
                'user_id' => auth()->id(),
                'type' => $this->form_type,
                'make' => $this->form_make,
                'name' => $this->form_name,
                'quantity' => $qtyAdded,
                'unit' => $defaults[$this->form_type] ?? 'units',
                'cost_per_unit' => $pricePerBase,
                'notes' => $this->form_notes ?: null,
            ];

            if ($this->form_type === 'bullet') {
                $createData['bullet_weight'] = $this->form_bullet_weight;
                $createData['bullet_bc'] = $this->form_bullet_bc;
                $createData['bullet_bc_type'] = $this->form_bullet_bc_type;
                $createData['bullet_type'] = $this->form_bullet_type ?: null;
                $createData['calibre'] = $this->form_calibre ?: null;
            }

            $item = ReloadingInventory::create($createData);

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

            InventoryLog::record($item->id, auth()->id(), 'restock', $qtyAdded, null, 'Initial purchase', null, null, null, $this->purchase_date);

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
        $this->showAdjustForm = false;
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

        InventoryLog::record($item->id, auth()->id(), 'restock', $qtyAdded, null, 'Purchase', null, null, $this->purchase_notes ?: null, $this->purchase_date);

        $this->showRestockForm = false;
        $this->restockItemId = null;
        $addedDisplay = $item->type === 'powder'
            ? number_format($qtyAdded * 15.4324, 0) . ' grains'
            : number_format($qtyAdded, 0) . ' ' . $item->unit;
        session()->flash('success', $item->display_name . ' restocked — ' . $addedDisplay . ' added.');
    }

    public function startAdjust(int $id): void
    {
        $item = ReloadingInventory::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->adjustItemId = $item->id;
        $this->adjust_type = 'add';
        $this->adjust_quantity = null;
        $this->adjust_reason = '';

        // Default to the simplest unit for the type
        if ($item->type === 'powder') {
            $this->adjust_unit_size = '1';      // 1 gram
            $this->adjust_unit_label = 'grams';
        } else {
            $this->adjust_unit_size = '1';
            $this->adjust_unit_label = 'units';
        }

        $this->showAdjustForm = true;
        $this->showRestockForm = false;
        $this->showForm = false;
    }

    public function saveAdjust(): void
    {
        $this->validate([
            'adjustItemId' => ['required', 'integer'],
            'adjust_type' => ['required', 'in:add,remove,set'],
            'adjust_quantity' => ['required', 'numeric', $this->adjust_type === 'set' ? 'min:0' : 'min:0.01'],
            'adjust_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $item = ReloadingInventory::where('id', $this->adjustItemId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $unitSize = (float) $this->adjust_unit_size;
        $rawQty = $this->adjust_quantity * $unitSize;
        $oldQty = (float) $item->quantity;

        if ($this->adjust_type === 'add') {
            $item->increment('quantity', $rawQty);
            $logChange = $rawQty;
            $actionLabel = 'Added';
        } elseif ($this->adjust_type === 'remove') {
            $removeQty = min($rawQty, $oldQty); // Don't go below zero
            $item->decrement('quantity', $removeQty);
            $rawQty = $removeQty;
            $logChange = -$removeQty;
            $actionLabel = 'Removed';
        } else {
            // Set to exact amount
            $item->update(['quantity' => $rawQty]);
            $logChange = $rawQty - $oldQty;
            $actionLabel = 'Set to';
        }

        InventoryLog::record($item->id, auth()->id(), 'adjustment', $logChange, null, $actionLabel, null, null, $this->adjust_reason ?: null);

        $displayQty = $item->type === 'powder'
            ? number_format($rawQty * 15.4324, 0) . ' grains'
            : number_format($rawQty, 0) . ' ' . $item->unit;

        $this->showAdjustForm = false;
        $this->adjustItemId = null;

        $msg = "{$item->display_name}: {$actionLabel} {$displayQty}.";
        if ($this->adjust_reason) {
            $msg .= " Reason: {$this->adjust_reason}";
        }
        session()->flash('success', $msg);
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
        $this->form_low_stock_threshold = null;
        $this->form_bullet_weight = null;
        $this->form_bullet_bc = null;
        $this->form_bullet_bc_type = 'G1';
        $this->form_bullet_type = '';
        $this->form_calibre = '';
        $this->purchase_qty = 1;
        $this->purchase_price = null;
        $this->purchase_date = now()->format('Y-m-d');
        $this->purchase_notes = '';
    }

    public function with(): array
    {
        $eagerLoad = ['purchases'];
        if ($this->showHistoryId && \Illuminate\Support\Facades\Schema::hasTable('inventory_logs')) {
            $eagerLoad[] = 'logs';
        }

        $query = ReloadingInventory::where('user_id', auth()->id())
            ->with($eagerLoad);

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('make', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('calibre', 'like', '%' . $search . '%')
                  ->orWhere('bullet_type', 'like', '%' . $search . '%');
            });
        }

        $items = $query->orderBy('type')->orderBy('make')->orderBy('name')->get();

        // Estimate rounds per load recipe based on linked inventory
        $loadEstimates = [];
        $loads = LoadData::where('user_id', auth()->id())
            ->where(function ($q) {
                $q->whereNotNull('powder_inventory_id')
                  ->orWhereNotNull('primer_inventory_id')
                  ->orWhereNotNull('bullet_inventory_id')
                  ->orWhereNotNull('brass_inventory_id');
            })
            ->with(['powderInventory', 'primerInventory', 'bulletInventory', 'brassInventory', 'userFirearm'])
            ->get();

        foreach ($loads as $load) {
            $limits = [];

            if ($load->powderInventory && $load->powder_charge) {
                $gramsPerRound = $load->powder_charge * 0.06479891;
                $powderStock = (float) $load->powderInventory->quantity;
                $rounds = $gramsPerRound > 0 ? (int) floor($powderStock / $gramsPerRound) : 0;
                $limits['Powder'] = $rounds;
            }
            if ($load->primerInventory) {
                $limits['Primers'] = (int) floor((float) $load->primerInventory->quantity);
            }
            if ($load->bulletInventory) {
                $limits['Bullets'] = (int) floor((float) $load->bulletInventory->quantity);
            }
            if ($load->brassInventory) {
                $limits['Brass'] = (int) floor((float) $load->brassInventory->quantity);
            }

            if (!empty($limits)) {
                $bottleneck = min($limits);
                $bottleneckComponent = array_search($bottleneck, $limits);
                $loadEstimates[] = [
                    'load' => $load,
                    'limits' => $limits,
                    'estimated_rounds' => $bottleneck,
                    'bottleneck' => $bottleneckComponent,
                ];
            }
        }

        // Sort by name
        usort($loadEstimates, fn ($a, $b) => strcmp($a['load']->name, $b['load']->name));

        return [
            'items' => $items,
            'groupedItems' => $items->groupBy('type'),
            'types' => ReloadingInventory::types(),
            'bulletTypes' => ReloadingInventory::bulletTypes(),
            'purchaseUnits' => ReloadingInventory::purchaseUnits(),
            'lowStockCount' => $items->filter(fn ($i) => $i->is_low_stock)->count(),
            'loadEstimates' => $loadEstimates,
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
        </div>
        <x-virtual-safe-tabs current="inventory" />
    </x-slot>

    <!-- Quick-add buttons -->
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mr-1">Add:</span>
        <button wire:click="addItem('powder')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-nrapa-blue text-nrapa-blue px-3 py-1.5 text-sm font-medium hover:bg-nrapa-blue hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Powder
        </button>
        <button wire:click="addItem('primer')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-nrapa-blue text-nrapa-blue px-3 py-1.5 text-sm font-medium hover:bg-nrapa-blue hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Primer
        </button>
        <button wire:click="addItem('bullet')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-nrapa-blue text-nrapa-blue px-3 py-1.5 text-sm font-medium hover:bg-nrapa-blue hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Bullet
        </button>
        <button wire:click="addItem('brass')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-nrapa-blue text-nrapa-blue px-3 py-1.5 text-sm font-medium hover:bg-nrapa-blue hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Brass
        </button>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Low Stock Warning -->
    @if($lowStockCount > 0)
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ $lowStockCount }} item(s) running low on stock</p>
            </div>
        </div>
    @endif

    <!-- Estimated Rounds per Load -->
    @if(!empty($loadEstimates))
        <div class="mb-6 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
            <div class="flex items-center gap-2 mb-4">
                <svg class="h-5 w-5 text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Estimated Rounds from Current Stock</h3>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($loadEstimates as $est)
                    <div class="rounded-lg border {{ $est['estimated_rounds'] < 20 ? 'border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-900/10' : 'border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50' }} p-4">
                        <div class="flex items-start justify-between mb-2">
                            <a href="{{ route('load-data.show', $est['load']) }}" wire:navigate
                               class="text-sm font-semibold text-nrapa-blue hover:text-nrapa-blue-dark truncate mr-2">
                                {{ $est['load']->name }}
                            </a>
                            <span class="text-lg font-bold {{ $est['estimated_rounds'] < 20 ? 'text-amber-600' : ($est['estimated_rounds'] < 100 ? 'text-nrapa-orange' : 'text-emerald-600') }} whitespace-nowrap">
                                {{ number_format($est['estimated_rounds']) }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500 mb-2">
                            @if($est['load']->calibre_name)
                                {{ $est['load']->calibre_name }} &mdash;
                            @endif
                            @if($est['load']->powder_charge)
                                {{ $est['load']->powder_charge }}gr charge
                            @endif
                        </p>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                            @foreach($est['limits'] as $component => $count)
                                <span class="{{ $component === $est['bottleneck'] ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-zinc-500' }}">
                                    {{ $component }}: {{ number_format($count) }}
                                    @if($component === $est['bottleneck'])
                                        <span class="text-[10px]">(limiting)</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-zinc-400">Based on load recipes linked to your inventory items. The lowest component determines the max rounds.</p>
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
                        <input type="text" wire:model="form_name" placeholder="{{ match($form_type) { 'powder' => 'e.g., H4350, N555, S365', 'primer' => 'e.g., BR2, 210M, KVB-7', 'bullet' => 'e.g., MatchKing, ELD-X, A-TIP', 'brass' => 'e.g., .308 Win, 6.5 Creedmoor' } }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('form_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Bullet Details (shown only for bullet type) -->
                @if($form_type === 'bullet')
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Bullet Details</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Calibre *</label>
                            <input type="text" wire:model="form_calibre" placeholder="e.g., .308, 6.5mm, .223"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('form_calibre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Weight (gr) *</label>
                            <input type="number" wire:model="form_bullet_weight" step="0.1" min="1" placeholder="e.g., 168"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('form_bullet_weight') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Bullet Type</label>
                            <select wire:model="form_bullet_type"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="">-- Select --</option>
                                @foreach($bulletTypes as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">BC</label>
                            <input type="number" wire:model="form_bullet_bc" step="0.001" min="0" max="1" placeholder="e.g., 0.462"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">BC Type</label>
                            <select wire:model="form_bullet_bc_type"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="G1">G1</option>
                                <option value="G7">G7</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif

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
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
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
        <div class="mb-6 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Edit Item Details</h2>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Low Stock Alert
                            <span class="text-xs text-zinc-400 font-normal">({{ $form_type === 'powder' ? 'grains' : 'units' }})</span>
                        </label>
                        <input type="number" wire:model="form_low_stock_threshold" step="1" min="0"
                               placeholder="{{ $form_type === 'powder' ? number_format(500 * 15.4324, 0) : ($form_type === 'primer' ? '100' : '50') }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-400">Leave blank for default</p>
                        @error('form_low_stock_threshold') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Bullet Details (edit) -->
                @if($form_type === 'bullet')
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Bullet Details</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Calibre *</label>
                            <input type="text" wire:model="form_calibre" placeholder="e.g., .308, 6.5mm"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('form_calibre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Weight (gr) *</label>
                            <input type="number" wire:model="form_bullet_weight" step="0.1" min="1" placeholder="168"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('form_bullet_weight') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Bullet Type</label>
                            <select wire:model="form_bullet_type"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="">-- Select --</option>
                                @foreach($bulletTypes as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">BC</label>
                            <input type="number" wire:model="form_bullet_bc" step="0.001" min="0" max="1" placeholder="0.462"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">BC Type</label>
                            <select wire:model="form_bullet_bc_type"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="G1">G1</option>
                                <option value="G7">G7</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">Update</button>
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
                <p class="text-sm text-zinc-500 mb-4">Current stock: {{ $restockItem->stock_display }}{{ $restockItem->type !== 'powder' ? ' ' . $restockItem->unit : '' }}</p>
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
                        <button type="submit" class="rounded-lg bg-nrapa-orange px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-orange-dark transition-colors">
                            Record Purchase
                        </button>
                        <button type="button" wire:click="$set('showRestockForm', false)"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400">Cancel</button>
                    </div>
                </form>
            </div>
        @endif
    @endif

    <!-- Manual Adjustment Form -->
    @if($showAdjustForm && $adjustItemId)
        @php $adjustItem = $items->firstWhere('id', $adjustItemId); @endphp
        @if($adjustItem)
            <div class="mb-6 rounded-lg border border-purple-300/50 dark:border-purple-700/50 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Adjust Stock: {{ $adjustItem->display_name }}
                    </h2>
                    <button wire:click="$set('showAdjustForm', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-zinc-500 mb-4">Current stock: <strong>{{ $adjustItem->stock_display }}{{ $adjustItem->type !== 'powder' ? ' ' . $adjustItem->unit : '' }}</strong></p>

                <form wire:submit="saveAdjust" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Action *</label>
                            <select wire:model.live="adjust_type"
                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                <option value="add">Add stock (received / gifted)</option>
                                <option value="remove">Remove stock (gave away / lost)</option>
                                <option value="set">Set exact amount (correction)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Quantity *</label>
                            <input type="number" wire:model="adjust_quantity" step="0.01" min="{{ $adjust_type === 'set' ? '0' : '0.01' }}"
                                   placeholder="{{ $adjustItem->type === 'powder' ? 'e.g., 1' : 'e.g., 50' }}"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            @error('adjust_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Unit</label>
                            @if($adjustItem->type === 'powder')
                                <select wire:model="adjust_unit_size"
                                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                    <option value="1">grams</option>
                                    <option value="0.06479891">grains</option>
                                    <option value="453.592">pounds (lb)</option>
                                    <option value="1000">kilograms (kg)</option>
                                </select>
                            @else
                                <select wire:model="adjust_unit_size"
                                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                                    <option value="1">individual units</option>
                                    @if(in_array($adjustItem->type, ['primer']))
                                        <option value="100">boxes of 100</option>
                                        <option value="1000">bricks of 1,000</option>
                                    @endif
                                    @if(in_array($adjustItem->type, ['bullet']))
                                        <option value="50">boxes of 50</option>
                                        <option value="100">boxes of 100</option>
                                    @endif
                                    @if(in_array($adjustItem->type, ['brass']))
                                        <option value="20">bags of 20</option>
                                        <option value="50">bags of 50</option>
                                        <option value="100">bags of 100</option>
                                    @endif
                                </select>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">Reason</label>
                            <input type="text" wire:model="adjust_reason" placeholder="{{ match($adjust_type) { 'add' => 'e.g., Gift from a friend', 'remove' => 'e.g., Spilled, gave to buddy', 'set' => 'e.g., Physical count correction' } }}"
                                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        </div>
                    </div>

                    @if($adjust_quantity && $adjust_quantity > 0)
                        @php
                            $unitSize = (float) $adjust_unit_size;
                            $rawQty = $adjust_quantity * $unitSize;
                            $previewDisplay = $adjustItem->type === 'powder'
                                ? number_format($rawQty * 15.4324, 0) . ' grains (' . number_format($rawQty, 1) . 'g)'
                                : number_format($rawQty, 0) . ' ' . $adjustItem->unit;
                            $currentRaw = (float) $adjustItem->quantity;
                            if ($adjust_type === 'add') {
                                $newRaw = $currentRaw + $rawQty;
                            } elseif ($adjust_type === 'remove') {
                                $newRaw = max(0, $currentRaw - $rawQty);
                            } else {
                                $newRaw = $rawQty;
                            }
                            $newDisplay = $adjustItem->type === 'powder'
                                ? number_format($newRaw * 15.4324, 0) . ' grains'
                                : number_format($newRaw, 0) . ' ' . $adjustItem->unit;
                        @endphp
                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-600 px-4 py-3 text-sm">
                            <span class="text-zinc-500">Preview:</span>
                            @if($adjust_type === 'add')
                                <span class="font-medium text-emerald-600">+{{ $previewDisplay }}</span>
                            @elseif($adjust_type === 'remove')
                                <span class="font-medium text-red-600">-{{ $previewDisplay }}</span>
                            @else
                                <span class="font-medium text-nrapa-blue">= {{ $previewDisplay }}</span>
                            @endif
                            <span class="text-zinc-400 mx-2">&rarr;</span>
                            <span class="font-semibold text-zinc-900 dark:text-white">New stock: {{ $newDisplay }}</span>
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <button type="submit"
                                class="rounded-lg bg-purple-600 px-6 py-2 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                            Apply Adjustment
                        </button>
                        <button type="button" wire:click="$set('showAdjustForm', false)"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
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
                    <div class="rounded-xl border {{ $item->is_low_stock ? 'border-amber-300 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-800 overflow-hidden">
                        <!-- Item Row -->
                        <div class="flex items-center justify-between px-5 py-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ $item->display_name }}</h4>
                                    @if($item->is_low_stock)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                            Low Stock
                                            <span class="ml-1 opacity-70">(below {{ $item->type === 'powder' ? number_format($item->effective_threshold * 15.4324, 0) . 'gr' : number_format($item->effective_threshold, 0) }})</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-y-1 mt-1 text-sm text-zinc-500">
                                    <span class="font-medium {{ $item->is_low_stock ? 'text-amber-600' : 'text-zinc-900 dark:text-white' }}">{{ $item->stock_display }}{{ $item->type !== 'powder' ? ' ' . $item->unit : '' }}</span>
                                    @if($item->type === 'bullet' && $item->calibre)
                                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
                                        <span>{{ $item->calibre }}</span>
                                    @endif
                                    @if($item->type === 'bullet' && $item->bullet_bc)
                                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
                                        <span>BC {{ $item->bullet_bc }} ({{ $item->bullet_bc_type ?? 'G1' }})</span>
                                    @endif
                                    @if($item->friendly_price)
                                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
                                        <span class="text-nrapa-orange font-medium">{{ $item->friendly_price }}</span>
                                    @endif
                                    @if($item->purchases->count() > 0)
                                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
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
                                <button wire:click="startAdjust({{ $item->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 px-3 py-1.5 text-xs font-medium hover:bg-purple-500/20">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                    Adjust
                                </button>
                                <button wire:click="toggleHistory({{ $item->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 dark:border-zinc-600 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Activity
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

                        <!-- Activity Log (expandable) -->
                        @if($showHistoryId === $item->id)
                            <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-5 py-4">
                                {{-- Purchases --}}
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Purchases</h4>
                                @if($item->purchases->count() > 0)
                                    <div class="space-y-2 mb-5">
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
                                    <p class="text-sm text-zinc-400 mb-5">No purchases recorded yet.</p>
                                @endif

                                {{-- Usage & Adjustment Log --}}
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Usage & Activity Log</h4>
                                @if($item->relationLoaded('logs') && $item->logs->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($item->logs->take(20) as $log)
                                            <div class="flex items-center justify-between text-sm rounded-lg bg-white dark:bg-zinc-800 px-4 py-2.5 border border-zinc-100 dark:border-zinc-700">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $log->badge_color }} whitespace-nowrap">
                                                        {{ $log->type_label }}
                                                    </span>
                                                    <span class="text-zinc-900 dark:text-white truncate">{{ $log->display }}</span>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs text-zinc-500 whitespace-nowrap ml-3">
                                                    @if($log->balance_after !== null)
                                                        <span class="text-zinc-400">
                                                            Bal:
                                                            @if($item->type === 'powder')
                                                                {{ number_format($log->balance_after * 15.4324, 0) }}gr
                                                            @else
                                                                {{ number_format($log->balance_after, 0) }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                    <span>{{ $log->logged_at->format('d M Y') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($item->logs->count() > 20)
                                            <p class="text-xs text-zinc-400 text-center pt-1">Showing latest 20 of {{ $item->logs->count() }} entries</p>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-zinc-400">No usage recorded yet. Load ammo from a recipe to start tracking.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">No inventory items yet</h3>
            <p class="mt-2 text-sm text-zinc-500">Start tracking your reloading components and purchase prices.</p>
            <button wire:click="addItem"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                Add First Component
            </button>
        </div>
    @endforelse
</div>
