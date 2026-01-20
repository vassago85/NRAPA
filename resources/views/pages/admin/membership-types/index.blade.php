<?php

use App\Models\MembershipType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Membership Types - Admin')] class extends Component {
    public bool $showEditModal = false;
    public ?MembershipType $editingType = null;
    
    // Form fields
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';
    
    #[Validate('nullable|string|max:1000')]
    public string $description = '';
    
    #[Validate('required|numeric|min:0')]
    public float $price = 0;
    
    #[Validate('required|numeric|min:0')]
    public float $admin_fee = 0;
    
    #[Validate('required|in:annual,lifetime,custom')]
    public string $duration_type = 'annual';
    
    #[Validate('nullable|integer|min:1|max:120')]
    public ?int $duration_months = 12;
    
    #[Validate('nullable|in:hunter,sport,both')]
    public ?string $dedicated_type = null;
    
    public bool $is_active = true;
    public bool $is_featured = false;
    public bool $display_on_landing = false;
    public bool $allows_dedicated_status = true;
    public bool $requires_knowledge_test = true;
    
    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::ordered()->get();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingType = null;
        $this->showEditModal = true;
    }

    public function openEditModal(MembershipType $type): void
    {
        $this->editingType = $type;
        $this->name = $type->name;
        $this->slug = $type->slug;
        $this->description = $type->description ?? '';
        $this->price = (float) $type->price;
        $this->admin_fee = (float) $type->admin_fee;
        $this->duration_type = $type->duration_type;
        $this->duration_months = $type->duration_months;
        $this->dedicated_type = $type->dedicated_type;
        $this->is_active = $type->is_active;
        $this->is_featured = $type->is_featured;
        $this->display_on_landing = $type->display_on_landing;
        $this->allows_dedicated_status = $type->allows_dedicated_status;
        $this->requires_knowledge_test = $type->requires_knowledge_test;
        $this->sort_order = $type->sort_order;
        $this->showEditModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'admin_fee' => $this->admin_fee,
            'duration_type' => $this->duration_type,
            'duration_months' => $this->duration_type === 'lifetime' ? null : $this->duration_months,
            'dedicated_type' => $this->dedicated_type ?: null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'display_on_landing' => $this->display_on_landing,
            'allows_dedicated_status' => $this->allows_dedicated_status,
            'requires_knowledge_test' => $this->requires_knowledge_test,
            'sort_order' => $this->sort_order,
            'requires_renewal' => $this->duration_type !== 'lifetime',
            'expiry_rule' => $this->duration_type === 'lifetime' ? 'none' : 'rolling',
            'pricing_model' => $this->duration_type === 'lifetime' ? 'once_off' : 'annual',
        ];

        // If setting as featured, unset other featured
        if ($this->is_featured) {
            MembershipType::where('is_featured', true)
                ->where('id', '!=', $this->editingType?->id)
                ->update(['is_featured' => false]);
        }

        if ($this->editingType) {
            $this->editingType->update($data);
            session()->flash('success', 'Membership type updated successfully.');
        } else {
            MembershipType::create($data);
            session()->flash('success', 'Membership type created successfully.');
        }

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function toggleActive(MembershipType $type): void
    {
        $type->update(['is_active' => !$type->is_active]);
    }

    public function toggleLanding(MembershipType $type): void
    {
        $type->update(['display_on_landing' => !$type->display_on_landing]);
    }

    public function setFeatured(MembershipType $type): void
    {
        // Unset all featured
        MembershipType::where('is_featured', true)->update(['is_featured' => false]);
        // Set this one
        $type->update(['is_featured' => true]);
        session()->flash('success', "{$type->name} is now the featured membership.");
    }

    public function moveUp(MembershipType $type): void
    {
        $prev = MembershipType::where('sort_order', '<', $type->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
        
        if ($prev) {
            $tempOrder = $type->sort_order;
            $type->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tempOrder]);
        }
    }

    public function moveDown(MembershipType $type): void
    {
        $next = MembershipType::where('sort_order', '>', $type->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();
        
        if ($next) {
            $tempOrder = $type->sort_order;
            $type->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tempOrder]);
        }
    }

    protected function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->price = 0;
        $this->admin_fee = 0;
        $this->duration_type = 'annual';
        $this->duration_months = 12;
        $this->dedicated_type = null;
        $this->is_active = true;
        $this->is_featured = false;
        $this->display_on_landing = false;
        $this->allows_dedicated_status = true;
        $this->requires_knowledge_test = true;
        $this->sort_order = MembershipType::max('sort_order') + 1;
        $this->editingType = null;
    }

    public function updatedName(): void
    {
        if (!$this->editingType) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Membership Types</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage membership packages displayed on the landing page.</p>
        </div>
        <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Membership Type
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-300 bg-green-100 p-4 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Membership Types Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Landing</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Featured</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->membershipTypes as $type)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ !$type->is_active ? 'opacity-50' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3">
                            <div class="flex items-center gap-1">
                                <button wire:click="moveUp({{ $type->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" title="Move up">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button wire:click="moveDown({{ $type->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" title="Move down">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <span class="text-xs text-zinc-400 ml-1">{{ $type->sort_order }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $type->slug }}</p>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">R{{ number_format($type->total_price, 0) }}</p>
                                @if($type->admin_fee > 0)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">R{{ number_format($type->price, 0) }} + R{{ number_format($type->admin_fee, 0) }} fee</p>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($type->dedicated_type)
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $type->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($type->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                {{ ucfirst($type->dedicated_type) }}
                            </span>
                            @else
                            <span class="text-zinc-400 text-xs">General</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="toggleActive({{ $type->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $type->is_active ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $type->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="toggleLanding({{ $type->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $type->display_on_landing ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $type->display_on_landing ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            @if($type->is_featured)
                            <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </span>
                            @else
                            <button wire:click="setFeatured({{ $type->id }})" class="text-zinc-400 hover:text-amber-500 dark:hover:text-amber-400" title="Set as featured">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </button>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button wire:click="openEditModal({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit/Create Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showEditModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">
                    {{ $editingType ? 'Edit Membership Type' : 'Create Membership Type' }}
                </h2>
                
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name *</label>
                            <input type="text" wire:model.live="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Slug *</label>
                            <input type="text" wire:model="slug" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" {{ $editingType ? 'disabled' : '' }}>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Base Price (R) *</label>
                            <input type="number" wire:model="price" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Fee (R) *</label>
                            <input type="number" wire:model="admin_fee" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('admin_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Total</label>
                            <div class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white font-medium">
                                R{{ number_format($price + $admin_fee, 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Duration Type *</label>
                            <select wire:model="duration_type" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="annual">Annual</option>
                                <option value="lifetime">Lifetime</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        @if($duration_type !== 'lifetime')
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Duration (Months)</label>
                            <input type="number" wire:model="duration_months" min="1" max="120" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type</label>
                            <select wire:model="dedicated_type" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <option value="">General (No dedicated status)</option>
                                <option value="hunter">Dedicated Hunter</option>
                                <option value="sport">Dedicated Sport Shooter</option>
                                <option value="both">Both (Hunter & Sport)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="display_on_landing" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Show on Landing</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_featured" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Featured</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="allows_dedicated_status" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Allows Dedicated</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="requires_knowledge_test" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Requires Test</span>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showEditModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ $editingType ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
