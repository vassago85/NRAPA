<?php

use App\Models\Calibre;
use App\Models\CalibreRequest;
use App\Models\EndorsementComponent;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\ShootingActivity;
use App\Models\UserFirearm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app.sidebar')] #[Title('Request Endorsement Letter')] class extends Component {

    // Wizard state
    public int $currentStep = 1;
    public int $totalSteps = 5;

    // Step 1: Request type
    public string $requestType = '';

    // Step 2: Firearm details (SAPS 271 Form Section E)
    public string $firearmCategory = '';       // 1. Type of Firearm
    public string $ignitionType = '';
    public string $actionType = '';            // 1.1 Action
    public string $actionOtherSpecify = '';    // 1.1 Other action (specify)
    public string $metalEngraving = '';        // 1.2 Names and addresses engraved
    public ?int $calibreId = null;             // 1.3 Calibre
    public string $calibreManual = '';         // 1.3 Calibre (manual)
    public string $calibreCode = '';           // 1.4 Calibre code
    public string $make = '';                  // 1.5 Make
    public string $model = '';                 // 1.6 Model
    public string $barrelSerialNumber = '';    // 1.7 Barrel serial number
    public string $barrelMake = '';            // 1.8 Barrel Make
    public string $frameSerialNumber = '';     // 1.9 Frame serial number
    public string $frameMake = '';             // 1.10 Frame Make
    public string $receiverSerialNumber = '';  // 1.11 Receiver serial number
    public string $receiverMake = '';          // 1.12 Receiver Make
    public string $serialNumber = '';          // Legacy serial (backward compat)
    public string $licenceSection = '';
    public string $sapsReference = '';
    public ?int $existingFirearmId = null;

    // Step 3: Components (renewal only)
    public bool $requestComponent = false;
    public array $components = [];

    // Step 4: Purpose & Notes
    public string $purpose = '';
    public string $purposeOtherText = '';
    public string $memberNotes = '';

    // Step 5: Declaration & Review
    public bool $declarationAccepted = false;

    // Request being edited
    public ?EndorsementRequest $editingRequest = null;

    // Eligibility info
    public array $eligibility = [];

    // Calibre request modal
    public bool $showCalibreRequestModal = false;
    public string $newCalibreName = '';
    public string $newCalibreCategory = '';
    public string $newCalibreIgnition = 'centerfire';
    public string $newCalibreSapsCode = '';
    public string $newCalibreReason = '';

    public function mount(?EndorsementRequest $request = null): void
    {
        $user = auth()->user();
        // Get eligibility summary for display (not for blocking)
        $this->eligibility = EndorsementRequest::getEligibilitySummary($user);

        if ($request && $request->exists && $request->user_id === $user->id) {
            $this->editingRequest = $request->load(['firearm', 'components']);
            $this->loadFromRequest($request);
        }
    }

    protected function loadFromRequest(EndorsementRequest $request): void
    {
        $this->requestType = $request->request_type;
        $this->purpose = $request->purpose ?? '';
        $this->purposeOtherText = $request->purpose_other_text ?? '';
        $this->memberNotes = $request->member_notes ?? '';
        $this->declarationAccepted = $request->declaration_accepted_at !== null;

        // Load firearm (SAPS 271 fields)
        if ($request->firearm) {
            $this->firearmCategory = $request->firearm->firearm_category;
            $this->ignitionType = $request->firearm->ignition_type ?? '';
            $this->actionType = $request->firearm->action_type ?? '';
            $this->actionOtherSpecify = $request->firearm->action_other_specify ?? '';
            $this->metalEngraving = $request->firearm->metal_engraving ?? '';
            $this->calibreId = $request->firearm->calibre_id;
            $this->calibreManual = $request->firearm->calibre_manual ?? '';
            $this->calibreCode = $request->firearm->calibre_code ?? '';
            $this->make = $request->firearm->make ?? '';
            $this->model = $request->firearm->model ?? '';
            $this->serialNumber = $request->firearm->serial_number ?? '';
            $this->barrelSerialNumber = $request->firearm->barrel_serial_number ?? '';
            $this->barrelMake = $request->firearm->barrel_make ?? '';
            $this->frameSerialNumber = $request->firearm->frame_serial_number ?? '';
            $this->frameMake = $request->firearm->frame_make ?? '';
            $this->receiverSerialNumber = $request->firearm->receiver_serial_number ?? '';
            $this->receiverMake = $request->firearm->receiver_make ?? '';
            $this->licenceSection = $request->firearm->licence_section ?? '';
            $this->sapsReference = $request->firearm->saps_reference ?? '';
            $this->existingFirearmId = $request->firearm->user_firearm_id;
        }

        // Load components
        if ($request->components->count() > 0) {
            $this->requestComponent = true;
            foreach ($request->components as $comp) {
                $this->components[] = [
                    'id' => $comp->id,
                    'component_type' => $comp->component_type,
                    'component_description' => $comp->component_description ?? '',
                    'component_serial' => $comp->component_serial ?? '',
                    'component_make' => $comp->component_make ?? '',
                    'component_model' => $comp->component_model ?? '',
                    'calibre_id' => $comp->calibre_id,
                    'calibre_manual' => $comp->calibre_manual ?? '',
                ];
            }
        }
    }

    #[Computed]
    public function userFirearms()
    {
        return UserFirearm::where('user_id', auth()->id())
            ->with('calibre')
            ->orderBy('make')
            ->orderBy('model')
            ->get();
    }

    #[Computed]
    public function userActivities()
    {
        $periodMonths = $this->eligibility['activity_details']['period_months'] ?? 12;
        return ShootingActivity::where('user_id', auth()->id())
            ->where('status', 'approved')
            ->where('activity_date', '>=', now()->subMonths($periodMonths))
            ->with(['activityType', 'eventCategory'])
            ->orderBy('activity_date', 'desc')
            ->get();
    }

    #[Computed]
    public function calibres()
    {
        $query = Calibre::active()->notObsolete()->ordered();

        if ($this->firearmCategory) {
            $categoryFilter = EndorsementFirearm::getCalibreCategoryFilter($this->firearmCategory);
            if ($categoryFilter) {
                $query->forCategory($categoryFilter);
            }
        }

        if ($this->ignitionType) {
            $query->forIgnitionType($this->ignitionType);
        }

        return $query->get();
    }

    #[Computed]
    public function actionTypeOptions()
    {
        return EndorsementFirearm::getActionTypeOptions($this->firearmCategory ?: null);
    }

    // Step navigation
    public function nextStep(): void
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            // Skip component step for new requests
            if ($this->currentStep === 2 && $this->requestType === 'new') {
                $this->currentStep = 4; // Skip to purpose
            } else {
                $this->currentStep++;
            }
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            // Skip component step for new requests
            if ($this->currentStep === 4 && $this->requestType === 'new') {
                $this->currentStep = 2; // Back to firearm
            } else {
                $this->currentStep--;
            }
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->getMaxAllowedStep()) {
            $this->currentStep = $step;
        }
    }

    protected function getMaxAllowedStep(): int
    {
        if (empty($this->requestType)) return 1;
        if (empty($this->firearmCategory)) return 2;
        if ($this->requestType === 'new') return 5;
        return 5;
    }

    protected function validateCurrentStep(): void
    {
        $rules = match($this->currentStep) {
            1 => ['requestType' => 'required|in:new,renewal'],
            2 => [
                'firearmCategory' => 'required|in:handgun,rifle_manual,rifle_self_loading,shotgun',
            ],
            3 => [], // Components optional
            4 => [
                'purpose' => 'required',
                'purposeOtherText' => 'required_if:purpose,other|max:500',
            ],
            5 => [], // Declaration validated on submit
            default => [],
        };

        $this->validate($rules);
    }

    // Request type changed
    public function updatedRequestType(): void
    {
        $this->requestComponent = false;
        $this->components = [];
    }

    // Firearm category changed - reset dependent fields
    public function updatedFirearmCategory(): void
    {
        $this->actionType = '';
        $this->calibreId = null;
        $this->ignitionType = '';
        $this->calibreCode = '';
    }

    // Calibre selected - auto-fill SAPS code if available
    public function updatedCalibreId(): void
    {
        if ($this->calibreId) {
            $calibre = Calibre::find($this->calibreId);
            if ($calibre && $calibre->saps_code) {
                $this->calibreCode = $calibre->saps_code;
            }
        } else {
            $this->calibreCode = '';
        }
    }

    // Open calibre request modal
    public function openCalibreRequestModal(): void
    {
        $this->showCalibreRequestModal = true;
        $this->newCalibreName = $this->calibreManual; // Pre-fill from manual entry
        $this->newCalibreCategory = match($this->firearmCategory) {
            'handgun' => 'handgun',
            'rifle_manual', 'rifle_self_loading' => 'rifle',
            'shotgun' => 'shotgun',
            default => '',
        };
        $this->newCalibreIgnition = $this->ignitionType ?: 'centerfire';
        $this->newCalibreSapsCode = '';
        $this->newCalibreReason = '';
    }

    // Close calibre request modal
    public function closeCalibreRequestModal(): void
    {
        $this->showCalibreRequestModal = false;
        $this->resetCalibreRequestForm();
    }

    // Reset calibre request form
    protected function resetCalibreRequestForm(): void
    {
        $this->newCalibreName = '';
        $this->newCalibreCategory = '';
        $this->newCalibreIgnition = 'centerfire';
        $this->newCalibreSapsCode = '';
        $this->newCalibreReason = '';
    }

    // Submit calibre request
    public function submitCalibreRequest(): void
    {
        $this->validate([
            'newCalibreName' => 'required|string|min:2|max:100',
            'newCalibreCategory' => 'required|in:handgun,rifle,shotgun,other',
            'newCalibreIgnition' => 'required|in:rimfire,centerfire',
        ], [
            'newCalibreName.required' => 'Please enter the calibre name.',
            'newCalibreCategory.required' => 'Please select a category.',
        ]);

        CalibreRequest::create([
            'user_id' => auth()->id(),
            'name' => $this->newCalibreName,
            'category' => $this->newCalibreCategory,
            'ignition_type' => $this->newCalibreIgnition,
            'saps_code' => $this->newCalibreSapsCode ?: null,
            'reason' => $this->newCalibreReason ?: null,
            'status' => CalibreRequest::STATUS_PENDING,
        ]);

        // Use the requested calibre name as manual entry
        $this->calibreManual = $this->newCalibreName;
        $this->calibreCode = $this->newCalibreSapsCode;

        $this->closeCalibreRequestModal();
        session()->flash('calibre_request_success', 'Calibre request submitted for admin approval. You can continue with your endorsement using the manual calibre entry.');
    }

    // Load from existing firearm
    public function loadExistingFirearm(): void
    {
        if (!$this->existingFirearmId) return;

        $firearm = UserFirearm::where('id', $this->existingFirearmId)
            ->where('user_id', auth()->id())
            ->first();

        if ($firearm) {
            $this->make = $firearm->make ?? '';
            $this->model = $firearm->model ?? '';
            $this->serialNumber = $firearm->serial_number ?? '';
            $this->calibreId = $firearm->calibre_id;
            if ($firearm->firearmType) {
                $cat = $firearm->firearmType->category;
                if ($cat === 'handgun') $this->firearmCategory = 'handgun';
                elseif ($cat === 'rifle') {
                    $this->firearmCategory = $firearm->firearmType->action_type === 'semi_auto' 
                        ? 'rifle_self_loading' 
                        : 'rifle_manual';
                }
                elseif ($cat === 'shotgun') $this->firearmCategory = 'shotgun';
            }
        }
    }

    // Component management
    public function addComponent(): void
    {
        if ($this->requestType !== 'renewal') return;

        $this->components[] = [
            'component_type' => '',
            'component_description' => '',
            'component_serial' => '',
            'component_make' => '',
            'component_model' => '',
            'calibre_id' => null,
            'calibre_manual' => '',
        ];
    }

    public function removeComponent(int $index): void
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
    }

    // Check if at least one serial number is provided
    #[Computed]
    public function hasAtLeastOneSerial(): bool
    {
        return !empty($this->barrelSerialNumber) 
            || !empty($this->frameSerialNumber) 
            || !empty($this->receiverSerialNumber)
            || !empty($this->serialNumber); // Legacy fallback
    }

    // Check if can submit
    #[Computed]
    public function canSubmit(): bool
    {
        if (!$this->declarationAccepted) return false;
        if (empty($this->requestType)) return false;
        if (empty($this->firearmCategory)) return false;
        if (empty($this->actionType)) return false;
        if (empty($this->make)) return false;
        if (empty($this->model)) return false;
        if (!$this->hasAtLeastOneSerial) return false;
        if (empty($this->purpose)) return false;
        if ($this->purpose === 'other' && empty($this->purposeOtherText)) return false;

        return true;
    }

    #[Computed]
    public function submissionErrors(): array
    {
        $errors = [];

        if (!$this->declarationAccepted) {
            $errors[] = 'You must accept the declaration.';
        }
        if (empty($this->requestType)) {
            $errors[] = 'Request type is required.';
        }
        if (empty($this->firearmCategory)) {
            $errors[] = 'Firearm type is required.';
        }
        if (empty($this->actionType)) {
            $errors[] = 'Action type is required.';
        }
        if (empty($this->make)) {
            $errors[] = 'Firearm make is required.';
        }
        if (empty($this->model)) {
            $errors[] = 'Firearm model is required.';
        }
        if (!$this->hasAtLeastOneSerial) {
            $errors[] = 'At least one serial number is required (barrel, frame, or receiver).';
        }
        if (empty($this->purpose)) {
            $errors[] = 'Purpose is required.';
        }

        return $errors;
    }

    // Save as draft
    public function saveDraft(): void
    {
        $this->saveRequest(false);
        session()->flash('success', 'Draft saved successfully.');
        $this->redirect(route('member.endorsements.index'), navigate: true);
    }

    // Submit request
    public function submitRequest(): void
    {
        if (!$this->canSubmit) {
            session()->flash('error', 'Please complete all required fields before submitting.');
            return;
        }

        $request = $this->saveRequest(true);
        
        if ($request->submit()) {
            session()->flash('success', 'Endorsement request submitted successfully!');
        } else {
            session()->flash('error', 'Failed to submit request. Please check all requirements.');
        }
        
        $this->redirect(route('member.endorsements.index'), navigate: true);
    }

    protected function saveRequest(bool $isSubmitting): EndorsementRequest
    {
        $user = auth()->user();

        // Create or update request
        $request = $this->editingRequest ?? new EndorsementRequest();
        $request->fill([
            'user_id' => $user->id,
            'request_type' => $this->requestType,
            'status' => EndorsementRequest::STATUS_DRAFT,
            'purpose' => $this->purpose ?: null,
            'purpose_other_text' => $this->purpose === 'other' ? $this->purposeOtherText : null,
            'member_notes' => $this->memberNotes ?: null,
            'declaration_accepted_at' => $this->declarationAccepted ? now() : null,
            'declaration_text' => $this->declarationAccepted ? $this->getDeclarationText() : null,
        ]);
        $request->save();

        // Save firearm (SAPS 271 Form Section E fields)
        $firearm = $request->firearm ?? new EndorsementFirearm();
        $firearm->fill([
            'endorsement_request_id' => $request->id,
            'firearm_category' => $this->firearmCategory,          // 1. Type
            'ignition_type' => $this->ignitionType ?: null,
            'action_type' => $this->actionType ?: null,            // 1.1 Action
            'action_other_specify' => $this->actionOtherSpecify ?: null,
            'metal_engraving' => $this->metalEngraving ?: null,    // 1.2 Engraving
            'calibre_id' => $this->calibreId,                      // 1.3 Calibre
            'calibre_manual' => $this->calibreManual ?: null,
            'calibre_code' => $this->calibreCode ?: null,          // 1.4 Calibre code
            'make' => $this->make ?: null,                         // 1.5 Make
            'model' => $this->model ?: null,                       // 1.6 Model
            'serial_number' => $this->serialNumber ?: null,        // Legacy
            'barrel_serial_number' => $this->barrelSerialNumber ?: null,    // 1.7
            'barrel_make' => $this->barrelMake ?: null,            // 1.8
            'frame_serial_number' => $this->frameSerialNumber ?: null,      // 1.9
            'frame_make' => $this->frameMake ?: null,              // 1.10
            'receiver_serial_number' => $this->receiverSerialNumber ?: null, // 1.11
            'receiver_make' => $this->receiverMake ?: null,        // 1.12
            'licence_section' => $this->licenceSection ?: null,
            'saps_reference' => $this->sapsReference ?: null,
            'user_firearm_id' => $this->existingFirearmId,
        ]);
        $firearm->save();

        // Save components (renewal only)
        if ($this->requestType === 'renewal' && $this->requestComponent) {
            $existingIds = collect($this->components)->pluck('id')->filter()->toArray();
            $request->components()->whereNotIn('id', $existingIds)->delete();

            foreach ($this->components as $compData) {
                if (empty($compData['component_type'])) continue;

                $component = isset($compData['id']) 
                    ? EndorsementComponent::find($compData['id']) 
                    : new EndorsementComponent();

                $component->fill([
                    'endorsement_request_id' => $request->id,
                    'component_type' => $compData['component_type'],
                    'component_description' => $compData['component_description'] ?: null,
                    'component_serial' => $compData['component_serial'] ?: null,
                    'component_make' => $compData['component_make'] ?: null,
                    'component_model' => $compData['component_model'] ?: null,
                    'calibre_id' => $compData['calibre_id'] ?? null,
                    'calibre_manual' => $compData['calibre_manual'] ?? null,
                ]);
                $component->save();
            }
        } else {
            $request->components()->delete();
        }

        return $request;
    }

    protected function getDeclarationText(): string
    {
        return "I, the undersigned, hereby declare that:
1. All information provided in this endorsement request is true, correct, and complete to the best of my knowledge.
2. I am an active member in good standing with NRAPA and maintain my dedicated status requirements.
3. I understand that providing false information may result in the revocation of my endorsement and membership.
4. I acknowledge that loss of compliance with dedicated status requirements may void this endorsement.
5. I consent to NRAPA verifying my information with relevant authorities if required.";
    }

    public function with(): array
    {
        return [
            'requestTypeOptions' => EndorsementRequest::getRequestTypeOptions(),
            'categoryOptions' => EndorsementFirearm::getCategoryOptions(),
            'ignitionOptions' => EndorsementFirearm::getIgnitionTypeOptions(),
            'licenceSectionOptions' => EndorsementFirearm::getLicenceSectionOptions(),
            'purposeOptions' => EndorsementRequest::getPurposeOptions(),
            'componentTypeOptions' => EndorsementComponent::getComponentTypeOptions(),
        ];
    }
}; ?>

<div>
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('member.endorsements.index') }}" wire:navigate class="inline-flex items-center gap-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back
            </a>
        </div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            {{ $editingRequest ? 'Edit Endorsement Request' : 'Request Endorsement Letter' }}
        </h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Complete the form below to request a dedicated status endorsement letter.</p>
    </div>

    {{-- Progress Steps --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'Type',
                    2 => 'Firearm',
                    3 => 'Components',
                    4 => 'Purpose',
                    5 => 'Review',
                ];
                $skipStep3 = $requestType === 'new';
            @endphp
            @foreach($steps as $num => $label)
                @if($num === 3 && $skipStep3)
                    @continue
                @endif
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <button 
                        wire:click="goToStep({{ $num }})"
                        @disabled($num > $this->getMaxAllowedStep())
                        class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold transition-colors
                            {{ $currentStep === $num 
                                ? 'bg-emerald-600 text-white' 
                                : ($currentStep > $num 
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' 
                                    : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400') }}
                            {{ $num <= $this->getMaxAllowedStep() ? 'cursor-pointer hover:ring-2 hover:ring-emerald-300' : 'cursor-not-allowed' }}"
                    >
                        @if($currentStep > $num)
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </button>
                    <span class="ml-2 text-sm font-medium {{ $currentStep === $num ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }} hidden sm:inline">
                        {{ $label }}
                    </span>
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $num ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
        {{-- Step 1: Request Type --}}
        @if($currentStep === 1)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Select Request Type</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    {{-- New Endorsement --}}
                    <label class="relative cursor-pointer">
                        <input type="radio" wire:model.live="requestType" value="new" class="peer sr-only">
                        <div class="p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">New Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        First-time endorsement letter for a Section 16 firearm application.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- Renewal Endorsement --}}
                    <label class="relative cursor-pointer">
                        <input type="radio" wire:model.live="requestType" value="renewal" class="peer sr-only">
                        <div class="p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Renewal Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        Endorsement renewal for existing firearms. Can include component requests (barrels, actions).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                @error('requestType')
                    <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Step 2: Firearm Details --}}
        @if($currentStep === 2)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Firearm Details</h2>

                @if($this->userFirearms->count() > 0)
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Load from your Virtual Safe</label>
                        <div class="flex gap-3">
                            <select wire:model="existingFirearmId" class="flex-1 px-4 py-2 border border-blue-300 dark:border-blue-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
                                <option value="">Select a firearm...</option>
                                @foreach($this->userFirearms as $uf)
                                    <option value="{{ $uf->id }}">
                                        {{ $uf->make }} {{ $uf->model }} 
                                        @if($uf->calibre) ({{ $uf->calibre->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <button wire:click="loadExistingFirearm" type="button"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                Load
                            </button>
                        </div>
                    </div>
                @endif

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Firearm Category <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach($categoryOptions as $value => $label)
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model.live="firearmCategory" value="{{ $value }}" class="peer sr-only">
                                    <div class="p-4 text-center border-2 rounded-lg transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('firearmCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($firearmCategory)
                        {{-- SAPS 271 Form Section E Fields --}}
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>Note:</strong> These fields align with SAPS Form 271 Section E - Description of Firearm. 
                                At least one serial number (barrel, frame, or receiver) is required.
                            </p>
                        </div>

                        {{-- 1.1 Action Type --}}
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Action Type <span class="text-red-500">*</span>
                                    <span class="text-xs text-zinc-500">(1.1)</span>
                                </label>
                                <select wire:model.live="actionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($this->actionTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($actionType === 'other')
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Other Action (specify)</label>
                                    <input type="text" wire:model="actionOtherSpecify" placeholder="Specify action type" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type</label>
                                    <select wire:model.live="ignitionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                        <option value="">Select...</option>
                                        @foreach($ignitionOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        {{-- 1.2 Metal Engraving --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Names/Addresses Engraved in Metal
                                <span class="text-xs text-zinc-500">(1.2)</span>
                            </label>
                            <input type="text" wire:model="metalEngraving" placeholder="If any text is engraved on the firearm" 
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        </div>

                        {{-- 1.3 & 1.4 Calibre --}}
                        @if(session('calibre_request_success'))
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-sm text-green-700 dark:text-green-300">
                                {{ session('calibre_request_success') }}
                            </div>
                        @endif
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Calibre / Gauge
                                    <span class="text-xs text-zinc-500">(1.3)</span>
                                </label>
                                <select wire:model.live="calibreId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select calibre...</option>
                                    @foreach($this->calibres as $cal)
                                        <option value="{{ $cal->id }}">{{ $cal->name }}@if($cal->saps_code) ({{ $cal->saps_code }})@endif</option>
                                    @endforeach
                                </select>
                                <div class="mt-2 flex gap-2">
                                    <input type="text" wire:model="calibreManual" placeholder="Or enter manually if not listed" 
                                        class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <button type="button" wire:click="openCalibreRequestModal"
                                        class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-lg transition-colors whitespace-nowrap">
                                        Request New
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">Can't find your calibre? Request it to be added.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Calibre Code
                                    <span class="text-xs text-zinc-500">(1.4 - Optional)</span>
                                </label>
                                <input type="text" wire:model="calibreCode" placeholder="e.g., 9PAR, 223REM" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono uppercase">
                                <p class="mt-1 text-xs text-zinc-500">Auto-fills when calibre selected</p>
                            </div>
                        </div>

                        {{-- 1.5 & 1.6 Make and Model --}}
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Make <span class="text-red-500">*</span>
                                    <span class="text-xs text-zinc-500">(1.5)</span>
                                </label>
                                <input type="text" wire:model="make" placeholder="e.g., Glock, CZ, Howa" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Model <span class="text-red-500">*</span>
                                    <span class="text-xs text-zinc-500">(1.6)</span>
                                </label>
                                <input type="text" wire:model="model" placeholder="e.g., 17, Shadow 2, 1500" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            </div>
                        </div>

                        {{-- Serial Numbers Section - At least one required --}}
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">
                                Serial Numbers <span class="text-red-500">*</span>
                                <span class="font-normal text-amber-700 dark:text-amber-300">(At least one required)</span>
                            </h4>
                            
                            {{-- 1.7 & 1.8 Barrel --}}
                            <div class="grid gap-4 md:grid-cols-2 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Barrel Serial Number
                                        <span class="text-xs text-zinc-500">(1.7)</span>
                                    </label>
                                    <input type="text" wire:model="barrelSerialNumber" placeholder="Barrel serial" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Barrel Make
                                        <span class="text-xs text-zinc-500">(1.8)</span>
                                    </label>
                                    <input type="text" wire:model="barrelMake" placeholder="Barrel manufacturer" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            </div>

                            {{-- 1.9 & 1.10 Frame --}}
                            <div class="grid gap-4 md:grid-cols-2 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Frame Serial Number
                                        <span class="text-xs text-zinc-500">(1.9)</span>
                                    </label>
                                    <input type="text" wire:model="frameSerialNumber" placeholder="Frame serial" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Frame Make
                                        <span class="text-xs text-zinc-500">(1.10)</span>
                                    </label>
                                    <input type="text" wire:model="frameMake" placeholder="Frame manufacturer" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            </div>

                            {{-- 1.11 & 1.12 Receiver --}}
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Receiver Serial Number
                                        <span class="text-xs text-zinc-500">(1.11)</span>
                                    </label>
                                    <input type="text" wire:model="receiverSerialNumber" placeholder="Receiver serial" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Receiver Make
                                        <span class="text-xs text-zinc-500">(1.12)</span>
                                    </label>
                                    <input type="text" wire:model="receiverMake" placeholder="Receiver manufacturer" 
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            </div>

                            @if(!$this->hasAtLeastOneSerial)
                                <p class="mt-3 text-sm text-red-600 dark:text-red-400">
                                    Please provide at least one serial number (barrel, frame, or receiver).
                                </p>
                            @endif
                        </div>

                        {{-- Licence Information (for renewals) --}}
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Licence Section</label>
                                <select wire:model="licenceSection" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($licenceSectionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SAPS/CFR Reference</label>
                                <input type="text" wire:model="sapsReference" placeholder="Existing CFR reference number" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Step 3: Components (Renewal Only) --}}
        @if($currentStep === 3 && $requestType === 'renewal')
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Component Endorsement</h2>

                <div class="mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="requestComponent" 
                            class="w-5 h-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-zinc-900 dark:text-white font-medium">Request component endorsement</span>
                    </label>
                    <p class="mt-1 ml-8 text-sm text-zinc-500 dark:text-zinc-400">
                        Enable to request endorsement for firearm components (barrels, actions, etc.)
                    </p>
                </div>

                @if($requestComponent)
                    <div class="space-y-4">
                        @foreach($components as $index => $component)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Component {{ $index + 1 }}</h4>
                                    <button wire:click="removeComponent({{ $index }})" type="button"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Component Type <span class="text-red-500">*</span></label>
                                        <select wire:model="components.{{ $index }}.component_type" 
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                            <option value="">Select...</option>
                                            @foreach($componentTypeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number</label>
                                        <input type="text" wire:model="components.{{ $index }}.component_serial" 
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                                        <input type="text" wire:model="components.{{ $index }}.component_make" 
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                                        <input type="text" wire:model="components.{{ $index }}.component_model" 
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    </div>
                                </div>

                                @if($component['component_type'] === 'barrel')
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre (for barrel)</label>
                                        <select wire:model="components.{{ $index }}.calibre_id" 
                                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                            <option value="">Select calibre...</option>
                                            @foreach($this->calibres as $cal)
                                                <option value="{{ $cal->id }}">{{ $cal->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                                    <input type="text" wire:model="components.{{ $index }}.component_description" 
                                        placeholder="Additional details about the component"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            </div>
                        @endforeach

                        <button wire:click="addComponent" type="button"
                            class="w-full p-4 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-500 dark:text-zinc-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Component
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Step 4: Purpose & Notes --}}
        @if($currentStep === 4)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Purpose & Notes</h2>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Purpose of Endorsement <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="purpose" 
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select purpose...</option>
                            @foreach($purposeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('purpose') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($purpose === 'other')
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Please specify <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="purposeOtherText" 
                                placeholder="Describe the purpose..."
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('purposeOtherText') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Additional Notes (Optional)
                        </label>
                        <textarea wire:model="memberNotes" rows="4"
                            placeholder="Any additional information you'd like to include..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 5: Review & Submit --}}
        @if($currentStep === 5)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Review & Submit</h2>

                <div class="space-y-6">
                    {{-- Verified Prerequisites --}}
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <h3 class="text-sm font-semibold text-green-800 dark:text-green-200 mb-3">Verified Prerequisites</h3>
                        <ul class="space-y-2 text-sm text-green-700 dark:text-green-300">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Knowledge Test Passed
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Required Documents Verified
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $eligibility['activity_details']['approved_count'] }} Approved Activities ({{ $eligibility['activity_details']['required'] }} required)
                            </li>
                        </ul>
                    </div>

                    {{-- Request Summary --}}
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Request Details</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Request Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $requestType === 'new' ? 'New Endorsement' : 'Renewal Endorsement' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Purpose</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">
                                    {{ $purposeOptions[$purpose] ?? $purpose }}
                                    @if($purpose === 'other') - {{ $purposeOtherText }} @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Firearm Summary --}}
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Firearm Details</h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Category</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $categoryOptions[$firearmCategory] ?? $firearmCategory }}</dd>
                            </div>
                            @if($calibreId || $calibreManual)
                                <div>
                                    <dt class="text-zinc-500">Calibre</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">
                                        {{ $calibreId ? $this->calibres->find($calibreId)?->name : $calibreManual }}
                                    </dd>
                                </div>
                            @endif
                            @if($make || $model)
                                <div>
                                    <dt class="text-zinc-500">Make/Model</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">{{ $make }} {{ $model }}</dd>
                                </div>
                            @endif
                            @if($serialNumber)
                                <div>
                                    <dt class="text-zinc-500">Serial Number</dt>
                                    <dd class="font-medium font-mono text-zinc-900 dark:text-white">{{ $serialNumber }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Components Summary --}}
                    @if($requestType === 'renewal' && $requestComponent && count($components) > 0)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Component Endorsements</h3>
                            <ul class="space-y-2 text-sm">
                                @foreach($components as $comp)
                                    @if($comp['component_type'])
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-zinc-900 dark:text-white">
                                                {{ $componentTypeOptions[$comp['component_type']] ?? $comp['component_type'] }}
                                                @if($comp['component_make']) - {{ $comp['component_make'] }} @endif
                                                @if($comp['component_model']) {{ $comp['component_model'] }} @endif
                                            </span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Submission Errors --}}
                    @if(count($this->submissionErrors) > 0)
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">Cannot Submit Yet</h4>
                            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                @foreach($this->submissionErrors as $error)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Declaration --}}
                    <div class="p-4 border-2 rounded-lg {{ $declarationAccepted ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="declarationAccepted" 
                                class="mt-1 w-5 h-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-white">I accept the declaration</span>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    I hereby declare that all information provided is true and correct. I am an active member in good standing with NRAPA and maintain my dedicated status requirements. I understand that providing false information may result in revocation of my endorsement and membership.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Footer --}}
        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-between">
            <div>
                @if($currentStep > 1)
                    <button wire:click="previousStep" type="button"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        Previous
                    </button>
                @endif
            </div>

            <div class="flex gap-3">
                <button wire:click="saveDraft" type="button"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    Save Draft
                </button>

                @if($currentStep < $totalSteps)
                    @if(!($currentStep === 3 && $requestType === 'new'))
                        <button wire:click="nextStep" type="button"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                            Next
                        </button>
                    @endif
                @else
                    <button wire:click="submitRequest" type="button"
                        @disabled(!$this->canSubmit)
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Submit Request
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Calibre Request Modal --}}
    @if($showCalibreRequestModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-zinc-500/75 dark:bg-zinc-900/75 transition-opacity" wire:click="closeCalibreRequestModal"></div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="modal-title">
                        Request New Calibre
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Submit a request to add a calibre that's not in our list. An admin will review and approve it.
                    </p>
                </div>

                <div class="px-6 py-4 space-y-4">
                    {{-- Calibre Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Calibre Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="newCalibreName" 
                            placeholder="e.g., 6.5 PRC, .300 PRC, 6mm ARC"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        @error('newCalibreName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="newCalibreCategory" 
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select category...</option>
                            <option value="handgun">Handgun</option>
                            <option value="rifle">Rifle</option>
                            <option value="shotgun">Shotgun</option>
                            <option value="other">Other</option>
                        </select>
                        @error('newCalibreCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Ignition Type --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Ignition Type <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="newCalibreIgnition" value="centerfire" 
                                    class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-zinc-700 dark:text-zinc-300">Centerfire</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="newCalibreIgnition" value="rimfire"
                                    class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-zinc-700 dark:text-zinc-300">Rimfire</span>
                            </label>
                        </div>
                    </div>

                    {{-- SAPS Code (optional) --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            SAPS Code <span class="text-xs text-zinc-500">(Optional - if known)</span>
                        </label>
                        <input type="text" wire:model="newCalibreSapsCode" 
                            placeholder="e.g., 65PRC"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono uppercase">
                    </div>

                    {{-- Reason/Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Additional Notes <span class="text-xs text-zinc-500">(Optional)</span>
                        </label>
                        <textarea wire:model="newCalibreReason" rows="2"
                            placeholder="Any additional information about this calibre..."
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                    <button type="button" wire:click="closeCalibreRequestModal"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitCalibreRequest"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
