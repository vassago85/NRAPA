<?php

use App\Models\KnowledgeTest;
use App\Models\MembershipType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Membership Types - Admin')] class extends Component {
    public bool $showEditModal = false;
    public bool $showTestsModal = false;
    public ?MembershipType $editingType = null;
    public ?MembershipType $configuringTestsType = null;
    
    // Form fields
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';
    
    #[Validate('nullable|string')]
    public ?string $icon = null;
    
    #[Validate('nullable|string|max:1000')]
    public string $description = '';
    
    #[Validate('required|numeric|min:0')]
    public float $initial_price = 0;

    #[Validate('required|numeric|min:0')]
    public float $renewal_price = 0;

    #[Validate('nullable|numeric|min:0')]
    public ?float $upgrade_price = null;
    
    #[Validate('required|in:annual,lifetime,custom')]
    public string $duration_type = 'annual';
    
    #[Validate('nullable|integer|min:1|max:120')]
    public ?int $duration_months = 12;
    
    #[Validate('nullable|in:hunter,sport,both')]
    public ?string $dedicated_type = null;
    
    public bool $is_active = true;
    public bool $is_featured = false;
    public bool $display_on_landing = false;
    public bool $display_on_signup = true;
    public bool $allows_dedicated_status = true;
    public bool $requires_knowledge_test = true;
    
    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;
    
    // Required tests selection
    public array $selectedTestIds = [];

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::ordered()->with('requiredKnowledgeTests')->get();
    }

    #[Computed]
    public function availableTests()
    {
        if (!$this->configuringTestsType) {
            return collect();
        }
        return $this->configuringTestsType->availableKnowledgeTests;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingType = null;
        $this->showEditModal = true;
    }

    public function openEditModal(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        $this->editingType = $type;
        $this->name = $type->name;
        $this->slug = $type->slug;
        $this->icon = $type->icon;
        $this->description = $type->description ?? '';
        $this->initial_price = (float) $type->initial_price;
        $this->renewal_price = (float) $type->renewal_price;
        $this->upgrade_price = $type->upgrade_price !== null ? (float) $type->upgrade_price : null;
        $this->duration_type = $type->duration_type;
        $this->duration_months = $type->duration_months;
        $this->dedicated_type = $type->dedicated_type;
        $this->is_active = $type->is_active;
        $this->is_featured = $type->is_featured;
        $this->display_on_landing = $type->display_on_landing;
        $this->display_on_signup = $type->display_on_signup;
        $this->allows_dedicated_status = $type->allows_dedicated_status;
        $this->requires_knowledge_test = $type->requires_knowledge_test;
        $this->sort_order = $type->sort_order;
        $this->showEditModal = true;
    }

    public function openTestsModal(int $typeId): void
    {
        $this->configuringTestsType = MembershipType::with('requiredKnowledgeTests')->findOrFail($typeId);
        $this->selectedTestIds = $this->configuringTestsType->requiredKnowledgeTests->pluck('id')->toArray();
        $this->showTestsModal = true;
    }

    public function saveRequiredTests(): void
    {
        if (!$this->configuringTestsType) {
            return;
        }

        // Sync the required tests
        $syncData = [];
        foreach ($this->selectedTestIds as $testId) {
            $syncData[$testId] = ['is_required' => true];
        }
        
        $this->configuringTestsType->knowledgeTests()->sync($syncData);
        
        session()->flash('success', "Required tests updated for {$this->configuringTestsType->name}.");
        $this->showTestsModal = false;
        $this->configuringTestsType = null;
        $this->selectedTestIds = [];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon ?: null,
            'description' => $this->description ?: null,
            'initial_price' => $this->initial_price,
            'renewal_price' => $this->renewal_price,
            'upgrade_price' => $this->upgrade_price,
            'duration_type' => $this->duration_type,
            'duration_months' => $this->duration_type === 'lifetime' ? null : $this->duration_months,
            'dedicated_type' => $this->dedicated_type ?: null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'display_on_landing' => $this->display_on_landing,
            'display_on_signup' => $this->display_on_signup,
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

    public function toggleActive(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        $type->update(['is_active' => !$type->is_active]);
    }

    public function toggleLanding(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        $type->update(['display_on_landing' => !$type->display_on_landing]);
    }

    public function toggleSignup(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        $type->update(['display_on_signup' => !$type->display_on_signup]);
    }

    public function setFeatured(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        // Unset all featured
        MembershipType::where('is_featured', true)->update(['is_featured' => false]);
        // Set this one
        $type->update(['is_featured' => true]);
        session()->flash('success', "{$type->name} is now the featured membership.");
    }

    public function moveUp(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
        $prev = MembershipType::where('sort_order', '<', $type->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
        
        if ($prev) {
            $tempOrder = $type->sort_order;
            $type->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tempOrder]);
        }
    }

    public function moveDown(int $typeId): void
    {
        $type = MembershipType::findOrFail($typeId);
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
        $this->icon = null;
        $this->description = '';
        $this->initial_price = 0;
        $this->renewal_price = 0;
        $this->upgrade_price = null;
        $this->duration_type = 'annual';
        $this->duration_months = 12;
        $this->dedicated_type = null;
        $this->is_active = true;
        $this->is_featured = false;
        $this->display_on_landing = false;
        $this->display_on_signup = true;
        $this->allows_dedicated_status = true;
        $this->requires_knowledge_test = true;
        $this->sort_order = MembershipType::max('sort_order') + 1;
        $this->editingType = null;
    }
    
    public function with(): array
    {
        return [
            'availableIcons' => MembershipType::AVAILABLE_ICONS,
        ];
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
        <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Membership Type
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-100 p-4 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Membership Types Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Fees</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400" title="Show on public landing page">Landing</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400" title="Show on signup/apply pages for new members">Signup</th>
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
                        <td class="px-4 py-3">
                            <div class="space-y-0.5 text-xs">
                                @if($type->initial_price > 0)
                                <p><span class="text-zinc-500 dark:text-zinc-400">Sign-up:</span> <span class="font-medium text-zinc-900 dark:text-white">R{{ number_format($type->initial_price, 0) }}</span></p>
                                @endif
                                @if($type->renewal_price > 0)
                                <p><span class="text-zinc-500 dark:text-zinc-400">Renewal:</span> <span class="font-medium text-zinc-900 dark:text-white">R{{ number_format($type->renewal_price, 0) }}</span></p>
                                @endif
                                @if($type->upgrade_price !== null && $type->upgrade_price > 0)
                                <p><span class="text-zinc-500 dark:text-zinc-400">Upgrade:</span> <span class="font-medium text-emerald-600 dark:text-emerald-400">R{{ number_format($type->upgrade_price, 0) }}</span></p>
                                @endif
                                @if($type->initial_price == 0 && $type->renewal_price == 0 && ($type->upgrade_price === null || $type->upgrade_price == 0))
                                <p class="text-zinc-400">Not set</p>
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
                            <button wire:click="toggleLanding({{ $type->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $type->display_on_landing ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}" title="Toggle landing page visibility">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $type->display_on_landing ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="toggleSignup({{ $type->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $type->display_on_signup ? 'bg-blue-500' : 'bg-zinc-300 dark:bg-zinc-600' }}" title="Toggle signup page visibility">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $type->display_on_signup ? 'translate-x-6' : 'translate-x-1' }}"></span>
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
                            <div class="flex items-center justify-end gap-2">
                                @if($type->allows_dedicated_status)
                                <button wire:click="openTestsModal({{ $type->id }})" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:hover:bg-purple-900/50" title="Configure required tests">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Tests ({{ $type->requiredKnowledgeTests->count() }})
                                </button>
                                @endif
                                <button wire:click="openEditModal({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                    Edit
                                </button>
                            </div>
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
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Icon</label>
                        <select wire:model="icon" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="">No Icon</option>
                            @foreach($availableIcons as $iconKey => $iconLabel)
                                <option value="{{ $iconKey }}">{{ $iconLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Icons from <a href="https://heroicons.com" target="_blank" class="text-emerald-600 hover:underline">heroicons.com</a> (MIT License, free for commercial use)
                        </p>
                        @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sign-up Fee (R) *</label>
                            <input type="number" wire:model="initial_price" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Initial fee for new members</p>
                            @error('initial_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Annual Renewal (R) *</label>
                            <input type="number" wire:model="renewal_price" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Yearly renewal fee</p>
                            @error('renewal_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upgrade Fee (R)</label>
                            <input type="number" wire:model="upgrade_price" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" placeholder="N/A for basic">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Once-off dedicated upgrade (leave empty for basic)</p>
                            @error('upgrade_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                            <input type="checkbox" wire:model="display_on_signup" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Show on Signup</span>
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
                        <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                            {{ $editingType ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Required Tests Modal --}}
    @if($showTestsModal && $configuringTestsType)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="$set('showTestsModal', false)" class="fixed inset-0 bg-black/50"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Required Knowledge Tests</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                    Select the tests members must pass to receive endorsements for <strong>{{ $configuringTestsType->name }}</strong>.
                </p>

                @if($configuringTestsType->dedicated_type)
                    <div class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Dedicated Type:</strong> 
                            {{ $configuringTestsType->dedicated_type === 'both' ? 'Hunter & Sport Shooter' : ucfirst($configuringTestsType->dedicated_type) }}
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                            Only tests matching this dedicated type are shown.
                        </p>
                    </div>
                @endif

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse($this->availableTests as $test)
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                            {{ in_array($test->id, $selectedTestIds) 
                                ? 'border-purple-500 bg-purple-50 dark:border-purple-400 dark:bg-purple-900/20' 
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <input type="checkbox" 
                                   wire:model="selectedTestIds" 
                                   value="{{ $test->id }}" 
                                   class="mt-1 rounded border-zinc-300 text-purple-600 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $test->name }}</span>
                                    @if($test->dedicated_type)
                                        <span class="text-xs px-1.5 py-0.5 rounded-full 
                                            {{ $test->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' }}
                                            {{ $test->dedicated_type === 'sport_shooter' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                            {{ $test->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}">
                                            {{ $test->dedicated_type === 'sport_shooter' ? 'Sport' : ucfirst($test->dedicated_type) }}
                                        </span>
                                    @else
                                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            General
                                        </span>
                                    @endif
                                </div>
                                @if($test->description)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">{{ $test->description }}</p>
                                @endif
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                    {{ $test->activeQuestions()->count() }} questions &bull; Pass: {{ $test->passing_score }}%
                                </p>
                            </div>
                        </label>
                    @empty
                        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-2">No matching tests available.</p>
                            <p class="text-xs mt-1">Create a knowledge test that matches this membership's dedicated type.</p>
                        </div>
                    @endforelse
                </div>

                @if(count($selectedTestIds) > 0)
                    <div class="mt-4 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <p class="text-sm text-emerald-800 dark:text-emerald-200">
                            <strong>{{ count($selectedTestIds) }}</strong> test(s) selected. 
                            Members must pass <strong>all</strong> selected tests to receive endorsements.
                        </p>
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" wire:click="$set('showTestsModal', false)" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveRequiredTests" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                        Save Required Tests
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
