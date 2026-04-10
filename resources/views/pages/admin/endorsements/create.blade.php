<?php

use App\Actions\Endorsements\AutoVerifyEndorsementDocuments;
use App\Models\AuditLog;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Create Endorsement - Admin')] class extends Component {

    // Member selection
    public string $memberSearch = '';
    public ?int $selectedUserId = null;

    // Request type
    public string $requestType = 'new';

    // Firearm details
    public string $firearmCategory = '';
    public string $actionType = '';
    public string $ignitionType = '';
    public string $make = '';
    public string $model = '';
    public string $calibreManual = '';
    public string $sapsReference = '';
    public string $licenceSection = '16';
    public string $componentDiameter = '';

    // Dedicated category
    public string $dedicatedCategory = '';

    // Admin notes
    public string $adminNotes = '';

    public function updatedMemberSearch(): void
    {
        $this->selectedUserId = null;
    }

    public function selectMember(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->memberSearch = '';

        $user = User::find($userId);
        if ($user) {
            $membership = $user->activeMembership;
            $dedicatedType = $membership?->type?->dedicated_type ?? null;
            $this->dedicatedCategory = match ($dedicatedType) {
                'sport' => 'Dedicated Sport Shooter',
                'hunter' => 'Dedicated Hunter',
                default => '',
            };
        }
    }

    public function updatedFirearmCategory(): void
    {
        $available = EndorsementFirearm::getActionTypeOptions($this->firearmCategory ?: null);
        if (!array_key_exists($this->actionType, $available)) {
            $this->actionType = '';
        }
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->memberSearch) < 2 || $this->selectedUserId) {
            return collect();
        }

        $search = $this->memberSearch;

        return User::where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('id_number', 'like', "%{$search}%");
        })
            ->whereHas('memberships', fn ($q) => $q->where('status', 'active'))
            ->with(['activeMembership.type'])
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        if (!$this->selectedUserId) {
            return null;
        }

        return User::with(['activeMembership.type'])->find($this->selectedUserId);
    }

    #[Computed]
    public function eligibility(): array
    {
        if (!$this->selectedUser) {
            return [];
        }

        return EndorsementRequest::getEligibilitySummary($this->selectedUser);
    }

    #[Computed]
    public function actionTypeOptions(): array
    {
        return EndorsementFirearm::getActionTypeOptions($this->firearmCategory ?: null);
    }

    public function createEndorsement(): void
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'requestType' => 'required|in:new,renewal',
            'firearmCategory' => 'required|string',
            'dedicatedCategory' => 'required|in:Dedicated Sport Shooter,Dedicated Hunter',
        ], [
            'selectedUserId.required' => 'Please select a member.',
            'firearmCategory.required' => 'Please select a firearm type.',
            'dedicatedCategory.required' => 'Please select a dedicated category.',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $admin = auth()->user();

        $request = EndorsementRequest::create([
            'user_id' => $user->id,
            'request_type' => $this->requestType,
            'status' => EndorsementRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
            'dedicated_category' => $this->dedicatedCategory,
            'declaration_accepted_at' => now(),
            'declaration_text' => 'Admin-created endorsement on behalf of member.',
            'admin_notes' => trim(($this->adminNotes ? $this->adminNotes . "\n\n" : '') . '[Created by admin: ' . $admin->name . ']'),
        ]);

        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
        $firearmData = [
            'endorsement_request_id' => $request->id,
            'firearm_category' => $this->firearmCategory,
            'licence_section' => $this->licenceSection ?: null,
            'saps_reference' => $this->sapsReference ?: null,
        ];

        if ($isComponent) {
            $firearmData['component_diameter'] = $this->firearmCategory === 'barrel' ? ($this->componentDiameter ?: null) : null;
            $firearmData['make'] = $this->make ?: null;
        } else {
            $firearmData['ignition_type'] = $this->ignitionType ?: null;
            $firearmData['action_type'] = $this->actionType ?: null;
            $firearmData['make'] = $this->make ?: null;
            $firearmData['model'] = $this->model ?: null;
            $firearmData['calibre_manual'] = $this->calibreManual ?: null;
        }

        EndorsementFirearm::create($firearmData);

        app(AutoVerifyEndorsementDocuments::class)->execute($request, $user);

        AuditLog::create([
            'user_id' => $admin->id,
            'event' => 'endorsement_created_by_admin',
            'auditable_type' => EndorsementRequest::class,
            'auditable_id' => $request->id,
            'old_values' => [],
            'new_values' => [
                'member_id' => $user->id,
                'member_name' => $user->name,
                'request_type' => $this->requestType,
                'firearm_category' => $this->firearmCategory,
                'dedicated_category' => $this->dedicatedCategory,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session()->flash('success', 'Endorsement created for ' . $user->name . '. You can now review and approve it.');
        $this->redirect(route('admin.endorsements.show', $request), navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Create Endorsement</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Create an endorsement request on behalf of a member</p>
    </x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.endorsements.index') }}" wire:navigate
            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to Endorsements
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Form --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Step 1: Select Member --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">1. Select Member</h2>
                </div>
                <div class="p-6">
                    @if($this->selectedUser)
                        <div class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $this->selectedUser->initials() }}
                                </div>
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedUser->name }}</p>
                                    <p class="text-sm text-zinc-500">{{ $this->selectedUser->email }}</p>
                                    @if($this->selectedUser->activeMembership?->type)
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $this->selectedUser->activeMembership->type->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <button wire:click="$set('selectedUserId', null)" class="text-sm text-zinc-500 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                Change
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text"
                                wire:model.live.debounce.300ms="memberSearch"
                                placeholder="Search by name, email, or ID number..."
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('selectedUserId')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($this->searchResults->isNotEmpty())
                            <div class="mt-2 border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-200 dark:divide-zinc-700 max-h-64 overflow-y-auto">
                                @foreach($this->searchResults as $user)
                                    <button wire:click="selectMember({{ $user->id }})"
                                        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors text-left">
                                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                            {{ $user->initials() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                            <p class="text-sm text-zinc-500">{{ $user->email }}
                                                @if($user->activeMembership?->type)
                                                    · {{ $user->activeMembership->type->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen($memberSearch) >= 2 && !$this->selectedUser)
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No active members found matching "{{ $memberSearch }}".</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Step 2: Request Type & Firearm --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">2. Endorsement Details</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Request Type</label>
                            <select wire:model="requestType" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                                @foreach(EndorsementRequest::getRequestTypeOptions() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Category <span class="text-red-500">*</span></label>
                            <select wire:model="dedicatedCategory" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                                <option value="">Select...</option>
                                <option value="Dedicated Sport Shooter">Dedicated Sport Shooter</option>
                                <option value="Dedicated Hunter">Dedicated Hunter</option>
                            </select>
                            @error('dedicatedCategory') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Firearm Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="firearmCategory" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                            <option value="">Select firearm type...</option>
                            @foreach(EndorsementFirearm::getCategoryOptions() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('firearmCategory') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($firearmCategory && !EndorsementFirearm::isComponentCategory($firearmCategory))
                        @if(!empty($this->actionTypeOptions))
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Type</label>
                                <select wire:model="actionType" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($this->actionTypeOptions as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type</label>
                            <select wire:model="ignitionType" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                                <option value="">Select...</option>
                                @foreach(EndorsementFirearm::getIgnitionTypeOptions() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                                <input type="text" wire:model="make" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white" placeholder="e.g. Howa">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                                <input type="text" wire:model="model" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white" placeholder="e.g. 1500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                            <input type="text" wire:model="calibreManual" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white" placeholder="e.g. .308 Winchester">
                        </div>
                    @endif

                    @if(EndorsementFirearm::isComponentCategory($firearmCategory))
                        @if($firearmCategory === 'barrel')
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Barrel Diameter</label>
                                <input type="text" wire:model="componentDiameter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                            <input type="text" wire:model="make" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                        </div>
                    @endif

                    @if($firearmCategory)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SAPS Reference</label>
                                <input type="text" wire:model="sapsReference" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Licence Section</label>
                                <select wire:model="licenceSection" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach(EndorsementFirearm::getLicenceSectionOptions() as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Admin Notes (optional)</label>
                        <textarea wire:model="adminNotes" rows="2"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"
                            placeholder="Internal notes about this endorsement..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">

            {{-- Eligibility Warnings --}}
            @if($this->selectedUser)
                <div class="bg-white dark:bg-zinc-800 rounded-xl border {{ ($this->eligibility['eligible'] ?? false) ? 'border-emerald-300 dark:border-emerald-700' : 'border-amber-300 dark:border-amber-700' }} overflow-hidden">
                    <div class="px-6 py-4 border-b {{ ($this->eligibility['eligible'] ?? false) ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20' : 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20' }}">
                        <h3 class="font-semibold {{ ($this->eligibility['eligible'] ?? false) ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200' }}">
                            Member Eligibility
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        {{-- Knowledge Test --}}
                        <div class="flex items-center gap-2 text-sm">
                            @if($this->eligibility['knowledge_test_passed'] ?? false)
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <span class="text-zinc-700 dark:text-zinc-300">Knowledge Test</span>
                        </div>

                        {{-- Documents --}}
                        <div class="flex items-center gap-2 text-sm">
                            @if($this->eligibility['documents_complete'] ?? false)
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <span class="text-zinc-700 dark:text-zinc-300">Required Documents</span>
                        </div>

                        {{-- Activities --}}
                        <div class="flex items-center gap-2 text-sm">
                            @if($this->eligibility['activities_met'] ?? false)
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <span class="text-zinc-700 dark:text-zinc-300">
                                Activities ({{ $this->eligibility['activity_details']['approved_count'] ?? 0 }}/{{ $this->eligibility['activity_details']['required'] ?? 2 }})
                            </span>
                        </div>

                        @if(!($this->eligibility['eligible'] ?? false) && count($this->eligibility['errors'] ?? []) > 0)
                            <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-xs font-semibold text-amber-800 dark:text-amber-200 mb-1">Warnings (non-blocking):</p>
                                <ul class="text-xs text-amber-700 dark:text-amber-300 space-y-1">
                                    @foreach($this->eligibility['errors'] as $error)
                                        <li>{{ is_array($error) ? ($error['message'] ?? '') : $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Create Button --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="p-6">
                    @if($this->selectedUser && !($this->eligibility['eligible'] ?? false))
                        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                This member does not meet all eligibility requirements. You can still create the endorsement — it will be flagged when you review it.
                            </p>
                        </div>
                    @endif

                    <button
                        wire:click="createEndorsement"
                        wire:loading.attr="disabled"
                        wire:confirm="{{ ($this->selectedUser && !($this->eligibility['eligible'] ?? true)) ? 'This member is NOT fully eligible. Are you sure you want to create this endorsement anyway?' : 'Create this endorsement request? You will be taken to the review page where you can approve and issue it.' }}"
                        class="w-full px-4 py-3 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="createEndorsement">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="createEndorsement">Create & Review</span>
                        <span wire:loading wire:target="createEndorsement">Creating...</span>
                    </button>

                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 text-center">
                        The endorsement will be created in "Submitted" status. You can then approve and issue it from the review page.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
