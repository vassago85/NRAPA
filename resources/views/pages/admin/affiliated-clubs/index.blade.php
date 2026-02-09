<?php

use App\Models\AffiliatedClub;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Affiliated Clubs - Admin')] class extends Component {
    public bool $showEditModal = false;
    public ?AffiliatedClub $editingClub = null;

    // Form fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|in:hunter,sport,both')]
    public string $dedicated_type = 'both';

    #[Validate('required|numeric|min:0')]
    public float $initial_fee = 0;

    #[Validate('required|numeric|min:0')]
    public float $renewal_fee = 0;

    public bool $requires_competency = true;

    #[Validate('required|integer|min:0|max:12')]
    public int $required_activities_per_year = 2;

    #[Validate('nullable|string|max:255')]
    public ?string $contact_name = null;

    #[Validate('nullable|email|max:255')]
    public ?string $contact_email = null;

    #[Validate('nullable|string|max:50')]
    public ?string $contact_phone = null;

    public bool $is_active = true;

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    #[Computed]
    public function clubs()
    {
        return AffiliatedClub::ordered()->withCount('memberships')->get();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingClub = null;
        $this->showEditModal = true;
    }

    public function openEditModal(int $clubId): void
    {
        $club = AffiliatedClub::findOrFail($clubId);
        $this->editingClub = $club;
        $this->name = $club->name;
        $this->slug = $club->slug;
        $this->description = $club->description ?? '';
        $this->dedicated_type = $club->dedicated_type;
        $this->initial_fee = (float) $club->initial_fee;
        $this->renewal_fee = (float) $club->renewal_fee;
        $this->requires_competency = $club->requires_competency;
        $this->required_activities_per_year = $club->required_activities_per_year;
        $this->contact_name = $club->contact_name;
        $this->contact_email = $club->contact_email;
        $this->contact_phone = $club->contact_phone;
        $this->is_active = $club->is_active;
        $this->sort_order = $club->sort_order;
        $this->showEditModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'dedicated_type' => $this->dedicated_type,
            'initial_fee' => $this->initial_fee,
            'renewal_fee' => $this->renewal_fee,
            'requires_competency' => $this->requires_competency,
            'required_activities_per_year' => $this->required_activities_per_year,
            'contact_name' => $this->contact_name ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingClub) {
            $this->editingClub->update($data);
            session()->flash('success', 'Affiliated club updated successfully.');
        } else {
            AffiliatedClub::create($data);
            session()->flash('success', 'Affiliated club created successfully.');
        }

        $this->showEditModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $clubId): void
    {
        $club = AffiliatedClub::findOrFail($clubId);
        $club->update(['is_active' => !$club->is_active]);
    }

    protected function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->dedicated_type = 'both';
        $this->initial_fee = 0;
        $this->renewal_fee = 0;
        $this->requires_competency = true;
        $this->required_activities_per_year = 2;
        $this->contact_name = null;
        $this->contact_email = null;
        $this->contact_phone = null;
        $this->is_active = true;
        $this->sort_order = (int) AffiliatedClub::max('sort_order') + 1;
        $this->editingClub = null;
    }

    public function updatedName(): void
    {
        if (!$this->editingClub) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Affiliated Clubs</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage affiliated clubs with custom fee schedules for their members.</p>
        </div>
        <button wire:click="openCreateModal" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Club
        </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-300 bg-green-100 p-4 text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Clubs Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Club</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Initial Fee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Renewal Fee</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Members</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->clubs as $club)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ !$club->is_active ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $club->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $club->slug }}</p>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $club->dedicated_type === 'both' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ($club->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                {{ $club->dedicated_type === 'both' ? 'Hunter & Sport' : ucfirst($club->dedicated_type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">R{{ number_format($club->initial_fee, 2) }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">R{{ number_format($club->renewal_fee, 2) }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $club->memberships_count }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <button wire:click="toggleActive({{ $club->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $club->is_active ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $club->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button wire:click="openEditModal({{ $club->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            <p>No affiliated clubs configured yet.</p>
                            <p class="text-sm mt-1">Click "Add Club" to create one.</p>
                        </td>
                    </tr>
                    @endforelse
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
                    {{ $editingClub ? 'Edit Affiliated Club' : 'Create Affiliated Club' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Club Name *</label>
                            <input type="text" wire:model.live="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Slug *</label>
                            <input type="text" wire:model="slug" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white" {{ $editingClub ? 'disabled' : '' }}>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type *</label>
                        <select wire:model="dedicated_type" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <option value="hunter">Dedicated Hunter</option>
                            <option value="sport">Dedicated Sport Shooter</option>
                            <option value="both">Both (Hunter & Sport)</option>
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Determines the dedicated status members of this club receive.</p>
                    </div>

                    {{-- Fee Schedule --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Fee Schedule</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Initial Fee (R) *</label>
                                <input type="number" wire:model="initial_fee" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sign-up fee for new club members</p>
                                @error('initial_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Renewal Fee (R) *</label>
                                <input type="number" wire:model="renewal_fee" step="0.01" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Annual renewal fee for club members</p>
                                @error('renewal_fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Requirements --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Requirements</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="requires_competency" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Require SAPS Firearm Competency</span>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activities per Year *</label>
                                <input type="number" wire:model="required_activities_per_year" min="0" max="12" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Match results with member name as evidence</p>
                                @error('required_activities_per_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Contact Info --}}
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Name</label>
                                <input type="text" wire:model="contact_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Email</label>
                                <input type="email" wire:model="contact_email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Contact Phone</label>
                                <input type="text" wire:model="contact_phone" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
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
                            {{ $editingClub ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
