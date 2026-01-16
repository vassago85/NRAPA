<?php

use App\Models\MembershipType;
use App\Models\DocumentType;
use App\Models\CertificateType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings - Admin')] class extends Component {
    public string $activeTab = 'membership-types';

    // Membership Type Form
    public ?int $editingMembershipTypeId = null;
    public string $membershipTypeName = '';
    public string $membershipTypeDescription = '';
    public string $membershipTypeSlug = '';
    public float $membershipTypePrice = 0;
    public string $membershipTypeDurationType = 'annual';
    public int $membershipTypeDurationMonths = 12;
    public bool $membershipTypeRequiresRenewal = true;
    public string $membershipTypeExpiryRule = 'rolling';
    public string $membershipTypePricingModel = 'annual';
    public bool $membershipTypeAllowsDedicatedStatus = false;
    public bool $membershipTypeRequiresKnowledgeTest = false;
    public bool $membershipTypeIsActive = true;

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::ordered()->get();
    }

    #[Computed]
    public function documentTypes()
    {
        return DocumentType::orderBy('name')->get();
    }

    #[Computed]
    public function certificateTypes()
    {
        return CertificateType::orderBy('name')->get();
    }

    public function editMembershipType(int $id): void
    {
        $type = MembershipType::findOrFail($id);
        $this->editingMembershipTypeId = $id;
        $this->membershipTypeName = $type->name;
        $this->membershipTypeDescription = $type->description ?? '';
        $this->membershipTypeSlug = $type->slug;
        $this->membershipTypePrice = $type->price;
        $this->membershipTypeDurationType = $type->duration_type;
        $this->membershipTypeDurationMonths = $type->duration_months ?? 12;
        $this->membershipTypeRequiresRenewal = $type->requires_renewal;
        $this->membershipTypeExpiryRule = $type->expiry_rule;
        $this->membershipTypePricingModel = $type->pricing_model;
        $this->membershipTypeAllowsDedicatedStatus = $type->allows_dedicated_status;
        $this->membershipTypeRequiresKnowledgeTest = $type->requires_knowledge_test;
        $this->membershipTypeIsActive = $type->is_active;
    }

    public function cancelEditMembershipType(): void
    {
        $this->editingMembershipTypeId = null;
        $this->resetMembershipTypeForm();
    }

    public function saveMembershipType(): void
    {
        $this->validate([
            'membershipTypeName' => ['required', 'string', 'max:255'],
            'membershipTypeSlug' => ['required', 'string', 'max:255'],
            'membershipTypePrice' => ['required', 'numeric', 'min:0'],
            'membershipTypeDurationType' => ['required', 'in:annual,lifetime,custom'],
            'membershipTypePricingModel' => ['required', 'in:annual,once_off,none'],
            'membershipTypeExpiryRule' => ['required', 'in:fixed_date,rolling,none'],
        ]);

        $data = [
            'name' => $this->membershipTypeName,
            'description' => $this->membershipTypeDescription,
            'slug' => $this->membershipTypeSlug,
            'price' => $this->membershipTypePrice,
            'duration_type' => $this->membershipTypeDurationType,
            'duration_months' => $this->membershipTypeDurationType === 'lifetime' ? null : $this->membershipTypeDurationMonths,
            'requires_renewal' => $this->membershipTypeDurationType !== 'lifetime' && $this->membershipTypeRequiresRenewal,
            'expiry_rule' => $this->membershipTypeExpiryRule,
            'pricing_model' => $this->membershipTypePricingModel,
            'allows_dedicated_status' => $this->membershipTypeAllowsDedicatedStatus,
            'requires_knowledge_test' => $this->membershipTypeRequiresKnowledgeTest,
            'is_active' => $this->membershipTypeIsActive,
        ];

        if ($this->editingMembershipTypeId) {
            MembershipType::findOrFail($this->editingMembershipTypeId)->update($data);
            session()->flash('success', 'Membership type updated successfully.');
        } else {
            $data['sort_order'] = MembershipType::max('sort_order') + 1;
            MembershipType::create($data);
            session()->flash('success', 'Membership type created successfully.');
        }

        $this->cancelEditMembershipType();
    }

    public function toggleMembershipTypeActive(int $id): void
    {
        $type = MembershipType::findOrFail($id);
        $type->update(['is_active' => !$type->is_active]);
    }

    protected function resetMembershipTypeForm(): void
    {
        $this->membershipTypeName = '';
        $this->membershipTypeDescription = '';
        $this->membershipTypeSlug = '';
        $this->membershipTypePrice = 0;
        $this->membershipTypeDurationType = 'annual';
        $this->membershipTypeDurationMonths = 12;
        $this->membershipTypeRequiresRenewal = true;
        $this->membershipTypeExpiryRule = 'rolling';
        $this->membershipTypePricingModel = 'annual';
        $this->membershipTypeAllowsDedicatedStatus = false;
        $this->membershipTypeRequiresKnowledgeTest = false;
        $this->membershipTypeIsActive = true;
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Settings</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Configure membership types, document requirements, and certificate templates.</p>
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-6">
            <button
                wire:click="$set('activeTab', 'membership-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'membership-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Membership Types
            </button>
            <button
                wire:click="$set('activeTab', 'document-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'document-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Document Types
            </button>
            <button
                wire:click="$set('activeTab', 'certificate-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $activeTab === 'certificate-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Certificate Types
            </button>
        </nav>
    </div>

    {{-- Membership Types Tab --}}
    @if($activeTab === 'membership-types')
    <div class="space-y-6">
        {{-- Edit/Create Form --}}
        @if($editingMembershipTypeId !== null || $editingMembershipTypeId === 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $editingMembershipTypeId ? 'Edit Membership Type' : 'Create Membership Type' }}
            </h3>

            <form wire:submit="saveMembershipType" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                        <input type="text" wire:model="membershipTypeName" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @error('membershipTypeName') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                        <input type="text" wire:model="membershipTypeSlug" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @error('membershipTypeSlug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea wire:model="membershipTypeDescription" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Price (R)</label>
                        <input type="number" step="0.01" wire:model="membershipTypePrice" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Duration Type</label>
                        <select wire:model.live="membershipTypeDurationType" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <option value="annual">Annual</option>
                            <option value="lifetime">Lifetime</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    @if($membershipTypeDurationType !== 'lifetime')
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Duration (Months)</label>
                        <input type="number" wire:model="membershipTypeDurationMonths" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    </div>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pricing Model</label>
                        <select wire:model="membershipTypePricingModel" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <option value="annual">Annual</option>
                            <option value="once_off">Once-Off</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Expiry Rule</label>
                        <select wire:model="membershipTypeExpiryRule" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <option value="rolling">Rolling</option>
                            <option value="fixed_date">Fixed Date</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="membershipTypeRequiresRenewal" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700" {{ $membershipTypeDurationType === 'lifetime' ? 'disabled' : '' }}>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Requires Renewal</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="membershipTypeAllowsDedicatedStatus" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Allows Dedicated Status</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="membershipTypeRequiresKnowledgeTest" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Requires Knowledge Test</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="membershipTypeIsActive" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        {{ $editingMembershipTypeId ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="cancelEditMembershipType" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="flex justify-end">
            <button wire:click="$set('editingMembershipTypeId', 0)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Membership Type
            </button>
        </div>
        @endif

        {{-- Membership Types List --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Features</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->membershipTypes as $type)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $type->slug }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-zinc-900 dark:text-white">
                                R{{ number_format($type->price, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                @if($type->isLifetime())
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Lifetime</span>
                                @else
                                    {{ $type->duration_months }} months
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex gap-1">
                                    @if($type->allows_dedicated_status)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Dedicated</span>
                                    @endif
                                    @if($type->requires_knowledge_test)
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Test</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <button wire:click="toggleMembershipTypeActive({{ $type->id }})">
                                    @if($type->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                    @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button wire:click="editMembershipType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Document Types Tab --}}
    @if($activeTab === 'document-types')
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Document Types</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure required documents for membership applications.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Expiry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Archive Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->documentTypes as $type)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $type->description }}</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            @if($type->expiry_months)
                                {{ $type->expiry_months }} months
                            @else
                                <span class="text-amber-600 dark:text-amber-400">Permanent</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            @if($type->archive_after_months)
                                {{ $type->archive_after_months }} months
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($type->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No document types configured.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Certificate Types Tab --}}
    @if($activeTab === 'certificate-types')
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Certificate Types</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure certificate templates for members.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Validity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->certificateTypes as $type)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $type->description }}</p>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                            @if($type->validity_months)
                                {{ $type->validity_months }} months
                            @else
                                <span class="text-amber-600 dark:text-amber-400">Indefinite</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($type->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No certificate types configured.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
