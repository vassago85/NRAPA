<?php

use App\Models\MembershipType;
use App\Models\DocumentType;
use App\Models\CertificateType;
use App\Models\ConfigurationChangeRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings - Admin')] class extends Component {
    public string $activeTab = 'membership-types';

    // Renewal Policy
    public int $renewalWindowDays = 30;
    public int $renewalGracePeriodDays = 90;

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
    public string $membershipTypeChangeReason = '';

    // Document Type Form
    public ?int $editingDocumentTypeId = null;
    public string $documentTypeName = '';
    public string $documentTypeDescription = '';
    public string $documentTypeSlug = '';
    public ?int $documentTypeExpiryMonths = null;
    public int $documentTypeArchiveMonths = 12;
    public bool $documentTypeIsActive = true;
    public string $documentTypeChangeReason = '';

    // Document Requirements
    public ?int $configuringMembershipTypeId = null;
    public array $selectedDocumentTypes = [];
    public string $documentRequirementsChangeReason = '';

    #[Computed]
    public function membershipTypes()
    {
        return MembershipType::ordered()->get();
    }

    #[Computed]
    public function documentTypes()
    {
        return DocumentType::ordered()->get();
    }

    #[Computed]
    public function certificateTypes()
    {
        return CertificateType::orderBy('name')->get();
    }

    #[Computed]
    public function pendingRequests()
    {
        return ConfigurationChangeRequest::pending()
            ->where('requested_by', auth()->id())
            ->latest()
            ->get();
    }

    public function mount(): void
    {
        $this->renewalWindowDays = (int) SystemSetting::get('renewal_window_days', 30);
        $this->renewalGracePeriodDays = (int) SystemSetting::get('renewal_grace_period_days', 90);
    }

    // Check if user is owner or developer (can directly edit without approval)
    protected function canEditDirectly(): bool
    {
        return auth()->user()->hasRoleLevel(User::ROLE_OWNER);
    }

    // ==================== RENEWAL POLICY METHODS ====================

    public function saveRenewalPolicy(): void
    {
        $this->validate([
            'renewalWindowDays' => ['required', 'integer', 'min:1', 'max:365'],
            'renewalGracePeriodDays' => ['required', 'integer', 'min:0', 'max:730'],
        ], [
            'renewalWindowDays.min' => 'Renewal window must be at least 1 day.',
            'renewalGracePeriodDays.min' => 'Grace period cannot be negative.',
        ]);

        SystemSetting::set('renewal_window_days', $this->renewalWindowDays, 'integer', 'renewal', 'Days before expiry when renewal becomes available');
        SystemSetting::set('renewal_grace_period_days', $this->renewalGracePeriodDays, 'integer', 'renewal', 'Days after expiry a member can still renew (0 = no grace period)');

        session()->flash('success', 'Renewal policy updated successfully.');
    }

    // ==================== MEMBERSHIP TYPE METHODS ====================

    public function editMembershipType(int $id): void
    {
        $type = MembershipType::findOrFail($id);
        $this->editingMembershipTypeId = $id;
        $this->membershipTypeName = $type->name;
        $this->membershipTypeDescription = $type->description ?? '';
        $this->membershipTypeSlug = $type->slug;
        $this->membershipTypePrice = $type->initial_price;
        $this->membershipTypeDurationType = $type->duration_type;
        $this->membershipTypeDurationMonths = $type->duration_months ?? 12;
        $this->membershipTypeRequiresRenewal = $type->requires_renewal;
        $this->membershipTypeExpiryRule = $type->expiry_rule;
        $this->membershipTypePricingModel = $type->pricing_model;
        $this->membershipTypeAllowsDedicatedStatus = $type->allows_dedicated_status;
        $this->membershipTypeRequiresKnowledgeTest = $type->requires_knowledge_test;
        $this->membershipTypeIsActive = $type->is_active;
        $this->membershipTypeChangeReason = '';
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
            'membershipTypeChangeReason' => $this->canEditDirectly() ? [] : ['required', 'string', 'min:10'],
        ], [
            'membershipTypeChangeReason.required' => 'Please provide a reason for this change (required for owner approval).',
        ]);

        $newData = [
            'name' => $this->membershipTypeName,
            'description' => $this->membershipTypeDescription,
            'slug' => $this->membershipTypeSlug,
            'initial_price' => $this->membershipTypePrice,
            'duration_type' => $this->membershipTypeDurationType,
            'duration_months' => $this->membershipTypeDurationType === 'lifetime' ? null : $this->membershipTypeDurationMonths,
            'requires_renewal' => $this->membershipTypeDurationType !== 'lifetime' && $this->membershipTypeRequiresRenewal,
            'expiry_rule' => $this->membershipTypeExpiryRule,
            'pricing_model' => $this->membershipTypePricingModel,
            'allows_dedicated_status' => $this->membershipTypeAllowsDedicatedStatus,
            'requires_knowledge_test' => $this->membershipTypeRequiresKnowledgeTest,
            'is_active' => $this->membershipTypeIsActive,
        ];

        // If owner/developer, apply directly
        if ($this->canEditDirectly()) {
            if ($this->editingMembershipTypeId) {
                MembershipType::findOrFail($this->editingMembershipTypeId)->update($newData);
                session()->flash('success', 'Membership type updated successfully.');
            } else {
                $newData['sort_order'] = MembershipType::max('sort_order') + 1;
                MembershipType::create($newData);
                session()->flash('success', 'Membership type created successfully.');
            }
        } else {
            // Admin - create change request for owner approval
            $oldData = null;
            if ($this->editingMembershipTypeId) {
                $oldData = MembershipType::findOrFail($this->editingMembershipTypeId)->toArray();
            } else {
                $newData['sort_order'] = MembershipType::max('sort_order') + 1;
            }

            ConfigurationChangeRequest::create([
                'requested_by' => auth()->id(),
                'configuration_type' => ConfigurationChangeRequest::TYPE_MEMBERSHIP_TYPE,
                'target_id' => $this->editingMembershipTypeId,
                'action' => $this->editingMembershipTypeId ? ConfigurationChangeRequest::ACTION_UPDATE : ConfigurationChangeRequest::ACTION_CREATE,
                'old_values' => $oldData,
                'new_values' => $newData,
                'reason' => $this->membershipTypeChangeReason,
            ]);

            session()->flash('success', 'Change request submitted for owner approval.');
        }

        $this->cancelEditMembershipType();
    }

    public function toggleMembershipTypeActive(int $id): void
    {
        if ($this->canEditDirectly()) {
            $type = MembershipType::findOrFail($id);
            $type->update(['is_active' => !$type->is_active]);
        } else {
            session()->flash('error', 'Status changes require owner approval. Please edit the membership type to request a change.');
        }
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
        $this->membershipTypeChangeReason = '';
    }

    // ==================== DOCUMENT TYPE METHODS ====================

    public function editDocumentType(int $id): void
    {
        $type = DocumentType::findOrFail($id);
        $this->editingDocumentTypeId = $id;
        $this->documentTypeName = $type->name;
        $this->documentTypeDescription = $type->description ?? '';
        $this->documentTypeSlug = $type->slug;
        $this->documentTypeExpiryMonths = $type->expiry_months;
        $this->documentTypeArchiveMonths = $type->archive_months ?? 12;
        $this->documentTypeIsActive = $type->is_active;
        $this->documentTypeChangeReason = '';
    }

    public function cancelEditDocumentType(): void
    {
        $this->editingDocumentTypeId = null;
        $this->resetDocumentTypeForm();
    }

    public function saveDocumentType(): void
    {
        $this->validate([
            'documentTypeName' => ['required', 'string', 'max:255'],
            'documentTypeSlug' => ['required', 'string', 'max:255'],
            'documentTypeArchiveMonths' => ['required', 'integer', 'min:1'],
            'documentTypeChangeReason' => $this->canEditDirectly() ? [] : ['required', 'string', 'min:10'],
        ], [
            'documentTypeChangeReason.required' => 'Please provide a reason for this change (required for owner approval).',
        ]);

        $newData = [
            'name' => $this->documentTypeName,
            'description' => $this->documentTypeDescription,
            'slug' => $this->documentTypeSlug,
            'expiry_months' => $this->documentTypeExpiryMonths,
            'archive_months' => $this->documentTypeArchiveMonths,
            'is_active' => $this->documentTypeIsActive,
        ];

        // If owner/developer, apply directly
        if ($this->canEditDirectly()) {
            if ($this->editingDocumentTypeId) {
                DocumentType::findOrFail($this->editingDocumentTypeId)->update($newData);
                session()->flash('success', 'Document type updated successfully.');
            } else {
                $newData['sort_order'] = DocumentType::max('sort_order') + 1;
                DocumentType::create($newData);
                session()->flash('success', 'Document type created successfully.');
            }
        } else {
            // Admin - create change request for owner approval
            $oldData = null;
            if ($this->editingDocumentTypeId) {
                $oldData = DocumentType::findOrFail($this->editingDocumentTypeId)->toArray();
            } else {
                $newData['sort_order'] = DocumentType::max('sort_order') + 1;
            }

            ConfigurationChangeRequest::create([
                'requested_by' => auth()->id(),
                'configuration_type' => ConfigurationChangeRequest::TYPE_DOCUMENT_TYPE,
                'target_id' => $this->editingDocumentTypeId,
                'action' => $this->editingDocumentTypeId ? ConfigurationChangeRequest::ACTION_UPDATE : ConfigurationChangeRequest::ACTION_CREATE,
                'old_values' => $oldData,
                'new_values' => $newData,
                'reason' => $this->documentTypeChangeReason,
            ]);

            session()->flash('success', 'Change request submitted for owner approval.');
        }

        $this->cancelEditDocumentType();
    }

    public function toggleDocumentTypeActive(int $id): void
    {
        if ($this->canEditDirectly()) {
            $type = DocumentType::findOrFail($id);
            $type->update(['is_active' => !$type->is_active]);
        } else {
            session()->flash('error', 'Status changes require owner approval. Please edit the document type to request a change.');
        }
    }

    protected function resetDocumentTypeForm(): void
    {
        $this->documentTypeName = '';
        $this->documentTypeDescription = '';
        $this->documentTypeSlug = '';
        $this->documentTypeExpiryMonths = null;
        $this->documentTypeArchiveMonths = 12;
        $this->documentTypeIsActive = true;
        $this->documentTypeChangeReason = '';
    }

    // ==================== DOCUMENT REQUIREMENTS METHODS ====================

    public function configureDocumentRequirements(int $membershipTypeId): void
    {
        $membershipType = MembershipType::with('documentTypes')->findOrFail($membershipTypeId);
        $this->configuringMembershipTypeId = $membershipTypeId;
        
        // Build the selected document types with their required status
        $this->selectedDocumentTypes = $membershipType->documentTypes
            ->mapWithKeys(fn ($doc) => [$doc->id => $doc->pivot->is_required])
            ->toArray();
        
        $this->documentRequirementsChangeReason = '';
    }

    public function cancelConfigureDocumentRequirements(): void
    {
        $this->configuringMembershipTypeId = null;
        $this->selectedDocumentTypes = [];
        $this->documentRequirementsChangeReason = '';
    }

    public function toggleDocumentRequirement(int $documentTypeId): void
    {
        if (isset($this->selectedDocumentTypes[$documentTypeId])) {
            unset($this->selectedDocumentTypes[$documentTypeId]);
        } else {
            $this->selectedDocumentTypes[$documentTypeId] = true;
        }
    }

    public function toggleDocumentRequired(int $documentTypeId): void
    {
        if (isset($this->selectedDocumentTypes[$documentTypeId])) {
            $this->selectedDocumentTypes[$documentTypeId] = !$this->selectedDocumentTypes[$documentTypeId];
        }
    }

    public function saveDocumentRequirements(): void
    {
        if (!$this->canEditDirectly()) {
            $this->validate([
                'documentRequirementsChangeReason' => ['required', 'string', 'min:10'],
            ], [
                'documentRequirementsChangeReason.required' => 'Please provide a reason for this change (required for owner approval).',
            ]);
        }

        $membershipType = MembershipType::with('documentTypes')->findOrFail($this->configuringMembershipTypeId);
        
        // Prepare the new document requirements
        $newDocumentTypes = collect($this->selectedDocumentTypes)
            ->map(fn ($isRequired, $id) => ['id' => $id, 'is_required' => $isRequired])
            ->values()
            ->toArray();

        // If owner/developer, apply directly
        if ($this->canEditDirectly()) {
            $syncData = collect($this->selectedDocumentTypes)
                ->mapWithKeys(fn ($isRequired, $id) => [$id => ['is_required' => $isRequired]])
                ->toArray();

            $membershipType->documentTypes()->sync($syncData);
            session()->flash('success', 'Document requirements updated successfully.');
        } else {
            // Admin - create change request for owner approval
            $oldDocumentTypes = $membershipType->documentTypes
                ->map(fn ($doc) => ['id' => $doc->id, 'name' => $doc->name, 'is_required' => $doc->pivot->is_required])
                ->toArray();

            // Add names to new values for better display
            $newDocumentTypesWithNames = collect($newDocumentTypes)->map(function ($doc) {
                $docType = DocumentType::find($doc['id']);
                return [
                    'id' => $doc['id'],
                    'name' => $docType?->name,
                    'is_required' => $doc['is_required'],
                ];
            })->toArray();

            ConfigurationChangeRequest::create([
                'requested_by' => auth()->id(),
                'configuration_type' => ConfigurationChangeRequest::TYPE_DOCUMENT_REQUIREMENTS,
                'target_id' => $this->configuringMembershipTypeId,
                'action' => ConfigurationChangeRequest::ACTION_UPDATE,
                'old_values' => ['membership_type' => $membershipType->name, 'document_types' => $oldDocumentTypes],
                'new_values' => ['membership_type' => $membershipType->name, 'document_types' => $newDocumentTypesWithNames],
                'reason' => $this->documentRequirementsChangeReason,
            ]);

            session()->flash('success', 'Document requirements change request submitted for owner approval.');
        }

        $this->cancelConfigureDocumentRequirements();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">General Settings</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Configure platform-wide settings</p>
    </x-slot>

    {{-- Header --}}
    <div class="flex flex-col gap-2">
        @if(!$this->canEditDirectly())
        <p class="text-sm text-amber-600 dark:text-amber-400">
            <svg class="inline-block size-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            Configuration changes require owner approval.
        </p>
        @endif
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/40">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    {{-- Pending Requests Notice --}}
    @if($this->pendingRequests->isNotEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm text-amber-700 dark:text-amber-300">
                You have {{ $this->pendingRequests->count() }} pending change request(s) awaiting owner approval.
            </p>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-6 overflow-x-auto">
            <button
                wire:click="$set('activeTab', 'membership-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === 'membership-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Membership Types
            </button>
            <button
                wire:click="$set('activeTab', 'renewal-policy')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === 'renewal-policy' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Renewal Policy
            </button>
            <button
                wire:click="$set('activeTab', 'document-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === 'document-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Document Types
            </button>
            <button
                wire:click="$set('activeTab', 'document-requirements')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === 'document-requirements' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Document Requirements
            </button>
            <button
                wire:click="$set('activeTab', 'certificate-types')"
                class="border-b-2 px-1 py-3 text-sm font-medium transition-colors whitespace-nowrap {{ $activeTab === 'certificate-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >
                Certificate Types
            </button>
        </nav>
    </div>

    {{-- Membership Types Tab --}}
    @if($activeTab === 'membership-types')
    <div class="space-y-6">
        {{-- Edit/Create Form --}}
        @if($editingMembershipTypeId !== null)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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

                @if(!$this->canEditDirectly())
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Change *</label>
                    <textarea wire:model="membershipTypeChangeReason" rows="2" placeholder="Explain why this change is needed..." class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('membershipTypeChangeReason') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                @endif

                <div class="flex gap-3">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        {{ $this->canEditDirectly() ? ($editingMembershipTypeId ? 'Update' : 'Create') : 'Submit for Approval' }}
                    </button>
                    <button type="button" wire:click="cancelEditMembershipType" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="flex justify-end">
            <button wire:click="$set('editingMembershipTypeId', 0)" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Membership Type
            </button>
        </div>
        @endif

        {{-- Membership Types List --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                                R{{ number_format($type->initial_price, 2) }}
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
                                <button wire:click="toggleMembershipTypeActive({{ $type->id }})" @if(!$this->canEditDirectly()) title="Status changes require owner approval" @endif>
                                    @if($type->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                    @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button wire:click="editMembershipType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
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

    {{-- Renewal Policy Tab --}}
    @if($activeTab === 'renewal-policy')
    <div class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">Renewal Policy</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                Configure when members can renew their membership and what happens when they expire.
            </p>

            <form wire:submit="saveRenewalPolicy" class="space-y-6">
                <div class="grid gap-6 md:grid-cols-2">
                    {{-- Renewal Window --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white">Renewal Window</h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">How early can members start renewing?</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Days before expiry</label>
                            <input type="number" wire:model="renewalWindowDays" min="1" max="365"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('renewalWindowDays') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Members will see "Expiring Soon" and the renewal button {{ $renewalWindowDays }} days before their membership expires.
                            </p>
                        </div>
                    </div>

                    {{-- Grace Period --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                                <svg class="size-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white">Grace Period After Expiry</h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">How long after expiry can they still renew?</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Days after expiry</label>
                            <input type="number" wire:model="renewalGracePeriodDays" min="0" max="730"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('renewalGracePeriodDays') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                After {{ $renewalGracePeriodDays }} days past expiry, the member must re-apply as a new member and pay the full sign-up fee. Set to 0 for no grace period.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Visual Timeline --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-5">
                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">How it works</h4>
                    <div class="flex items-center gap-2 text-xs overflow-x-auto pb-2">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="size-2 rounded-full bg-emerald-500"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Active (no renewal)</span>
                        </div>
                        <svg class="size-4 text-zinc-300 dark:text-zinc-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="size-2 rounded-full bg-blue-500"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $renewalWindowDays }}d before expiry (can renew)</span>
                        </div>
                        <svg class="size-4 text-zinc-300 dark:text-zinc-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Expired ({{ $renewalGracePeriodDays }}d grace)</span>
                        </div>
                        <svg class="size-4 text-zinc-300 dark:text-zinc-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="size-2 rounded-full bg-red-500"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Must rejoin as new (full fee)</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        Save Renewal Policy
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Document Types Tab --}}
    @if($activeTab === 'document-types')
    <div class="space-y-6">
        {{-- Edit/Create Form --}}
        @if($editingDocumentTypeId !== null)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $editingDocumentTypeId ? 'Edit Document Type' : 'Create Document Type' }}
            </h3>

            <form wire:submit="saveDocumentType" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                        <input type="text" wire:model="documentTypeName" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @error('documentTypeName') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                        <input type="text" wire:model="documentTypeSlug" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @error('documentTypeSlug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea wire:model="documentTypeDescription" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Expiry (Months)</label>
                        <input type="number" wire:model="documentTypeExpiryMonths" placeholder="Leave blank for permanent" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave blank if document doesn't expire</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Archive Period (Months)</label>
                        <input type="number" wire:model="documentTypeArchiveMonths" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">How long to keep after expiry</p>
                        @error('documentTypeArchiveMonths') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="documentTypeIsActive" id="documentTypeIsActive" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                    <label for="documentTypeIsActive" class="text-sm text-zinc-700 dark:text-zinc-300">Active</label>
                </div>

                @if(!$this->canEditDirectly())
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Change *</label>
                    <textarea wire:model="documentTypeChangeReason" rows="2" placeholder="Explain why this change is needed..." class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('documentTypeChangeReason') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                @endif

                <div class="flex gap-3">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        {{ $this->canEditDirectly() ? ($editingDocumentTypeId ? 'Update' : 'Create') : 'Submit for Approval' }}
                    </button>
                    <button type="button" wire:click="cancelEditDocumentType" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="flex justify-end">
            <button wire:click="$set('editingDocumentTypeId', 0)" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Document Type
            </button>
        </div>
        @endif

        {{-- Document Types List --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
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
                                {{ $type->archive_months }} months
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <button wire:click="toggleDocumentTypeActive({{ $type->id }})" @if(!$this->canEditDirectly()) title="Status changes require owner approval" @endif>
                                    @if($type->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                    @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button wire:click="editDocumentType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No document types configured.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Document Requirements Tab --}}
    @if($activeTab === 'document-requirements')
    <div class="space-y-6">
        {{-- Configure Document Requirements Modal/Form --}}
        @if($configuringMembershipTypeId !== null)
        @php $membershipType = $this->membershipTypes->firstWhere('id', $configuringMembershipTypeId); @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                Document Requirements for: {{ $membershipType?->name }}
            </h3>
            <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                Select which documents are required for this membership type. Toggle "Required" to mark as mandatory.
            </p>

            <form wire:submit="saveDocumentRequirements" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($this->documentTypes as $docType)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 {{ isset($selectedDocumentTypes[$docType->id]) ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700' : '' }}">
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                wire:click="toggleDocumentRequirement({{ $docType->id }})"
                                {{ isset($selectedDocumentTypes[$docType->id]) ? 'checked' : '' }}
                                class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                            >
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $docType->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if($docType->expiry_months)
                                        Expires after {{ $docType->expiry_months }} months
                                    @else
                                        Permanent document
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(isset($selectedDocumentTypes[$docType->id]))
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                wire:click="toggleDocumentRequired({{ $docType->id }})"
                                {{ $selectedDocumentTypes[$docType->id] ? 'checked' : '' }}
                                class="size-4 rounded border-zinc-300 text-amber-600 focus:ring-amber-500 dark:border-zinc-600 dark:bg-zinc-700"
                            >
                            <span class="text-zinc-700 dark:text-zinc-300">Required</span>
                        </label>
                        @endif
                    </div>
                    @endforeach
                </div>

                @if($this->documentTypes->isEmpty())
                <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
                    <p class="text-zinc-500 dark:text-zinc-400">No document types available. Create document types first.</p>
                </div>
                @endif

                @if(!$this->canEditDirectly())
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Change *</label>
                    <textarea wire:model="documentRequirementsChangeReason" rows="2" placeholder="Explain why this change is needed..." class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('documentRequirementsChangeReason') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                @endif

                <div class="flex gap-3">
                    <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                        {{ $this->canEditDirectly() ? 'Save Requirements' : 'Submit for Approval' }}
                    </button>
                    <button type="button" wire:click="cancelConfigureDocumentRequirements" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Membership Types with Document Requirements --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Document Requirements by Membership Type</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure which documents are required for each membership type.</p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($this->membershipTypes as $type)
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-medium text-zinc-900 dark:text-white">{{ $type->name }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $type->description }}</p>
                            
                            @if($type->documentTypes->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($type->documentTypes as $doc)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->pivot->is_required ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                    {{ $doc->name }}
                                    @if($doc->pivot->is_required)
                                    <span class="ml-1 text-amber-600">*</span>
                                    @endif
                                </span>
                                @endforeach
                            </div>
                            @else
                            <p class="mt-3 text-sm text-zinc-400 dark:text-zinc-500 italic">No documents required</p>
                            @endif
                        </div>
                        <button
                            wire:click="configureDocumentRequirements({{ $type->id }})"
                            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                        >
                            Configure
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-medium">Legend:</span>
                <span class="ml-3 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Required *</span>
                <span class="ml-2 inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">Optional</span>
            </p>
        </div>
    </div>
    @endif

    {{-- Certificate Types Tab --}}
    @if($activeTab === 'certificate-types')
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
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
