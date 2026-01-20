<?php

use App\Models\Calibre;
use App\Models\EndorsementComponent;
use App\Models\EndorsementDocument;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\UserFirearm;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app.sidebar')] #[Title('Request Endorsement Letter')] class extends Component {
    use WithFileUploads;

    // Wizard state
    public int $currentStep = 1;
    public int $totalSteps = 6;

    // Step 1: Request type
    public string $requestType = '';

    // Step 2: Firearm details
    public string $firearmCategory = '';
    public string $ignitionType = '';
    public string $actionType = '';
    public ?int $calibreId = null;
    public string $calibreManual = '';
    public string $make = '';
    public string $model = '';
    public string $serialNumber = '';
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

    // Step 5: Documents
    public array $documentStatuses = [];
    public array $activityProofs = [];
    public $currentUpload = null;
    public string $uploadingDocType = '';

    // Activity proof fields
    public string $activityType = '';
    public string $activityDiscipline = '';
    public ?string $activityDate = null;
    public string $activityVenue = '';
    public string $activityOrganiser = '';

    // Step 6: Declaration & Review
    public bool $declarationAccepted = false;

    // Request being edited
    public ?EndorsementRequest $editingRequest = null;

    public function mount(?EndorsementRequest $request = null): void
    {
        if ($request && $request->exists && $request->user_id === auth()->id()) {
            $this->editingRequest = $request->load(['firearm', 'components', 'documents']);
            $this->loadFromRequest($request);
        } else {
            // Initialize document statuses
            $this->initializeDocumentStatuses('new');
        }
    }

    protected function loadFromRequest(EndorsementRequest $request): void
    {
        $this->requestType = $request->request_type;
        $this->purpose = $request->purpose ?? '';
        $this->purposeOtherText = $request->purpose_other_text ?? '';
        $this->memberNotes = $request->member_notes ?? '';
        $this->declarationAccepted = $request->declaration_accepted_at !== null;

        // Load firearm
        if ($request->firearm) {
            $this->firearmCategory = $request->firearm->firearm_category;
            $this->ignitionType = $request->firearm->ignition_type ?? '';
            $this->actionType = $request->firearm->action_type ?? '';
            $this->calibreId = $request->firearm->calibre_id;
            $this->calibreManual = $request->firearm->calibre_manual ?? '';
            $this->make = $request->firearm->make ?? '';
            $this->model = $request->firearm->model ?? '';
            $this->serialNumber = $request->firearm->serial_number ?? '';
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

        // Load document statuses
        $this->initializeDocumentStatuses($request->request_type);
        foreach ($request->documents as $doc) {
            if ($doc->document_type === EndorsementDocument::TYPE_ACTIVITY_PROOF) {
                $this->activityProofs[] = [
                    'id' => $doc->id,
                    'status' => $doc->status,
                    'activity_type' => $doc->activity_type,
                    'activity_discipline' => $doc->activity_discipline,
                    'activity_date' => $doc->activity_date?->format('Y-m-d'),
                    'activity_venue' => $doc->activity_venue,
                    'activity_organiser' => $doc->activity_organiser,
                    'original_filename' => $doc->original_filename,
                ];
            } else {
                $this->documentStatuses[$doc->document_type] = [
                    'id' => $doc->id,
                    'status' => $doc->status,
                    'original_filename' => $doc->original_filename,
                    'is_required' => $doc->is_required,
                ];
            }
        }
    }

    protected function initializeDocumentStatuses(string $requestType): void
    {
        $required = EndorsementRequest::getRequiredDocumentTypes($requestType);
        $optional = EndorsementRequest::getOptionalDocumentTypes($requestType);

        $this->documentStatuses = [];

        foreach ($required as $docType) {
            if ($docType !== 'activity_proof') {
                $this->documentStatuses[$docType] = [
                    'status' => 'required',
                    'is_required' => true,
                    'original_filename' => null,
                ];
            }
        }

        foreach ($optional as $docType) {
            if ($docType !== 'activity_proof') {
                $this->documentStatuses[$docType] = [
                    'status' => 'required',
                    'is_required' => false,
                    'original_filename' => null,
                ];
            }
        }

        // Activity proofs handled separately
        $this->activityProofs = [];
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
        // Basic validation to determine how far user can go
        if (empty($this->requestType)) return 1;
        if (empty($this->firearmCategory)) return 2;
        if ($this->requestType === 'new') return 6;
        return 6;
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
            5 => [], // Documents validated on submit
            6 => [], // Declaration validated on submit
            default => [],
        };

        $this->validate($rules);
    }

    // Request type changed
    public function updatedRequestType(): void
    {
        $this->initializeDocumentStatuses($this->requestType);
        $this->requestComponent = false;
        $this->components = [];
    }

    // Firearm category changed - reset dependent fields
    public function updatedFirearmCategory(): void
    {
        $this->actionType = '';
        $this->calibreId = null;
        $this->ignitionType = '';
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
            // Try to determine category from firearm type
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

    // Document handling
    public function markSubmitLater(string $docType): void
    {
        if (isset($this->documentStatuses[$docType])) {
            $this->documentStatuses[$docType]['status'] = 'pending_upload';
        }
    }

    public function setUploadingDoc(string $docType): void
    {
        $this->uploadingDocType = $docType;
        $this->currentUpload = null;
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'currentUpload' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp',
        ]);

        if ($this->uploadingDocType && $this->currentUpload) {
            $this->documentStatuses[$this->uploadingDocType] = [
                'status' => 'uploaded',
                'is_required' => $this->documentStatuses[$this->uploadingDocType]['is_required'] ?? true,
                'original_filename' => $this->currentUpload->getClientOriginalName(),
                'temp_path' => $this->currentUpload->getRealPath(),
                'mime_type' => $this->currentUpload->getMimeType(),
                'file_size' => $this->currentUpload->getSize(),
            ];
        }

        $this->uploadingDocType = '';
        $this->currentUpload = null;
    }

    public function addActivityProof(): void
    {
        $this->validate([
            'currentUpload' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp',
            'activityType' => 'required',
            'activityDate' => 'required|date',
        ]);

        $this->activityProofs[] = [
            'status' => 'uploaded',
            'activity_type' => $this->activityType,
            'activity_discipline' => $this->activityDiscipline,
            'activity_date' => $this->activityDate,
            'activity_venue' => $this->activityVenue,
            'activity_organiser' => $this->activityOrganiser,
            'original_filename' => $this->currentUpload->getClientOriginalName(),
            'temp_path' => $this->currentUpload->getRealPath(),
            'mime_type' => $this->currentUpload->getMimeType(),
            'file_size' => $this->currentUpload->getSize(),
        ];

        // Reset fields
        $this->currentUpload = null;
        $this->activityType = '';
        $this->activityDiscipline = '';
        $this->activityDate = null;
        $this->activityVenue = '';
        $this->activityOrganiser = '';
    }

    public function removeActivityProof(int $index): void
    {
        unset($this->activityProofs[$index]);
        $this->activityProofs = array_values($this->activityProofs);
    }

    // Check if can submit
    #[Computed]
    public function canSubmit(): bool
    {
        if (!$this->declarationAccepted) return false;
        if (empty($this->requestType)) return false;
        if (empty($this->firearmCategory)) return false;
        if (empty($this->purpose)) return false;
        if ($this->purpose === 'other' && empty($this->purposeOtherText)) return false;

        // Check required documents
        $requiredTypes = EndorsementRequest::getRequiredDocumentTypes($this->requestType);
        foreach ($requiredTypes as $docType) {
            if ($docType === 'activity_proof') {
                if (count($this->activityProofs) === 0) return false;
            } else {
                $status = $this->documentStatuses[$docType]['status'] ?? 'required';
                if (!in_array($status, ['uploaded', 'verified', 'system_verified'])) {
                    return false;
                }
            }
        }

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
            $errors[] = 'Firearm category is required.';
        }
        if (empty($this->purpose)) {
            $errors[] = 'Purpose is required.';
        }

        $requiredTypes = EndorsementRequest::getRequiredDocumentTypes($this->requestType ?: 'new');
        foreach ($requiredTypes as $docType) {
            if ($docType === 'activity_proof') {
                if (count($this->activityProofs) === 0) {
                    $errors[] = 'At least one activity proof is required.';
                }
            } else {
                $status = $this->documentStatuses[$docType]['status'] ?? 'required';
                if (!in_array($status, ['uploaded', 'verified', 'system_verified'])) {
                    $errors[] = 'Missing document: ' . EndorsementRequest::getDocumentTypeLabel($docType);
                }
            }
        }

        return $errors;
    }

    // Save as draft
    public function saveDraft(): void
    {
        $request = $this->saveRequest(false);
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
        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');

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

        // Save firearm
        $firearm = $request->firearm ?? new EndorsementFirearm();
        $firearm->fill([
            'endorsement_request_id' => $request->id,
            'firearm_category' => $this->firearmCategory,
            'ignition_type' => $this->ignitionType ?: null,
            'action_type' => $this->actionType ?: null,
            'calibre_id' => $this->calibreId,
            'calibre_manual' => $this->calibreManual ?: null,
            'make' => $this->make ?: null,
            'model' => $this->model ?: null,
            'serial_number' => $this->serialNumber ?: null,
            'licence_section' => $this->licenceSection ?: null,
            'saps_reference' => $this->sapsReference ?: null,
            'user_firearm_id' => $this->existingFirearmId,
        ]);
        $firearm->save();

        // Save components (renewal only)
        if ($this->requestType === 'renewal' && $this->requestComponent) {
            // Delete removed components
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

        // Save documents
        foreach ($this->documentStatuses as $docType => $docData) {
            $doc = $request->documents()->where('document_type', $docType)->first() 
                ?? new EndorsementDocument(['endorsement_request_id' => $request->id]);

            $updateData = [
                'document_type' => $docType,
                'status' => $docData['status'],
                'is_required' => $docData['is_required'] ?? true,
            ];

            // Handle file upload
            if (isset($docData['temp_path']) && file_exists($docData['temp_path'])) {
                $filename = \Illuminate\Support\Str::random(40) . '.' . pathinfo($docData['original_filename'], PATHINFO_EXTENSION);
                $directory = "endorsements/{$user->uuid}/{$request->uuid}";
                
                $path = Storage::disk($disk)->putFileAs($directory, $docData['temp_path'], $filename, [
                    'visibility' => 'private',
                ]);

                $updateData['file_path'] = $path;
                $updateData['original_filename'] = $docData['original_filename'];
                $updateData['mime_type'] = $docData['mime_type'];
                $updateData['file_size'] = $docData['file_size'];
                $updateData['uploaded_by'] = $user->id;
                $updateData['uploaded_at'] = now();
                $updateData['status'] = EndorsementDocument::STATUS_UPLOADED;
            }

            $doc->fill($updateData);
            $doc->save();
        }

        // Save activity proofs
        $existingActivityIds = collect($this->activityProofs)->pluck('id')->filter()->toArray();
        $request->documents()->where('document_type', 'activity_proof')
            ->whereNotIn('id', $existingActivityIds)->delete();

        foreach ($this->activityProofs as $activityData) {
            $doc = isset($activityData['id'])
                ? EndorsementDocument::find($activityData['id'])
                : new EndorsementDocument(['endorsement_request_id' => $request->id]);

            $updateData = [
                'document_type' => 'activity_proof',
                'status' => $activityData['status'],
                'is_required' => true,
                'activity_type' => $activityData['activity_type'],
                'activity_discipline' => $activityData['activity_discipline'] ?? null,
                'activity_date' => $activityData['activity_date'],
                'activity_venue' => $activityData['activity_venue'] ?? null,
                'activity_organiser' => $activityData['activity_organiser'] ?? null,
            ];

            // Handle file upload for new activity proofs
            if (isset($activityData['temp_path']) && file_exists($activityData['temp_path'])) {
                $filename = \Illuminate\Support\Str::random(40) . '.' . pathinfo($activityData['original_filename'], PATHINFO_EXTENSION);
                $directory = "endorsements/{$user->uuid}/{$request->uuid}/activities";
                
                $path = Storage::disk($disk)->putFileAs($directory, $activityData['temp_path'], $filename, [
                    'visibility' => 'private',
                ]);

                $updateData['file_path'] = $path;
                $updateData['original_filename'] = $activityData['original_filename'];
                $updateData['mime_type'] = $activityData['mime_type'];
                $updateData['file_size'] = $activityData['file_size'];
                $updateData['uploaded_by'] = $user->id;
                $updateData['uploaded_at'] = now();
                $updateData['status'] = EndorsementDocument::STATUS_UPLOADED;
            }

            $doc->fill($updateData);
            $doc->save();
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
            'documentTypeLabels' => EndorsementDocument::getDocumentTypeOptions(),
            'activityTypeOptions' => EndorsementDocument::getActivityTypeOptions(),
            'disciplineOptions' => EndorsementDocument::getDisciplineOptions(),
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
                    5 => 'Documents',
                    6 => 'Review',
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
                                    <ul class="mt-3 text-sm text-zinc-500 dark:text-zinc-400 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Full document verification
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Confirms dedicated status
                                        </li>
                                    </ul>
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
                                        Endorsement renewal for existing firearms, with optional component requests.
                                    </p>
                                    <ul class="mt-3 text-sm text-zinc-500 dark:text-zinc-400 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Streamlined document requirements
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Can include component endorsement
                                        </li>
                                    </ul>
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

                {{-- Load from existing firearm --}}
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
                    {{-- Firearm Category --}}
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
                        <div class="grid gap-6 md:grid-cols-2">
                            {{-- Ignition Type --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ignition Type</label>
                                <select wire:model.live="ignitionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($ignitionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Action Type --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Type</label>
                                <select wire:model="actionType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">Select...</option>
                                    @foreach($this->actionTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Calibre --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre / Gauge</label>
                            <select wire:model="calibreId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                <option value="">Select calibre...</option>
                                @foreach($this->calibres as $cal)
                                    <option value="{{ $cal->id }}">{{ $cal->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-zinc-500">Or enter manually below if not listed</p>
                            <input type="text" wire:model="calibreManual" placeholder="e.g., .338 Lapua Magnum" 
                                class="mt-2 w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        </div>

                        {{-- Make, Model, Serial --}}
                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Make</label>
                                <input type="text" wire:model="make" placeholder="e.g., Glock, Ruger" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Model</label>
                                <input type="text" wire:model="model" placeholder="e.g., 17, 10/22" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Serial Number</label>
                                <input type="text" wire:model="serialNumber" placeholder="If known" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                            </div>
                        </div>

                        {{-- Licence Section & SAPS Reference --}}
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
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SAPS Reference (if applicable)</label>
                                <input type="text" wire:model="sapsReference" placeholder="CFR reference number" 
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
                        Enable this to request endorsement for firearm components (barrels, actions, etc.)
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
                        <p class="mt-1 text-xs text-zinc-500">This will be included in your endorsement request for admin review.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 5: Documents --}}
        @if($currentStep === 5)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">Required Documents</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">Upload your supporting documents or mark as "Submit Later" to save as draft.</p>

                <div class="space-y-4">
                    {{-- Regular Documents --}}
                    @foreach($documentStatuses as $docType => $docData)
                        <div class="p-4 border rounded-lg {{ $docData['is_required'] ? 'border-zinc-300 dark:border-zinc-600' : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/30' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    {{-- Status Icon --}}
                                    @if($docData['status'] === 'uploaded' || $docData['status'] === 'verified')
                                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @elseif($docData['status'] === 'pending_upload')
                                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                        </div>
                                    @endif

                                    <div>
                                        <h4 class="font-medium text-zinc-900 dark:text-white">
                                            {{ $documentTypeLabels[$docType] ?? $docType }}
                                            @if($docData['is_required'])
                                                <span class="text-red-500">*</span>
                                            @else
                                                <span class="text-xs text-zinc-500">(Optional)</span>
                                            @endif
                                        </h4>
                                        @if($docData['original_filename'])
                                            <p class="text-sm text-zinc-500 truncate max-w-xs">{{ $docData['original_filename'] }}</p>
                                        @elseif($docData['status'] === 'pending_upload')
                                            <p class="text-sm text-amber-600 dark:text-amber-400">Will submit later</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($docData['status'] !== 'uploaded' && $docData['status'] !== 'verified')
                                        <button wire:click="setUploadingDoc('{{ $docType }}')" type="button"
                                            class="px-3 py-1.5 text-sm font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 rounded-lg transition-colors">
                                            Upload
                                        </button>
                                        @if($docData['is_required'])
                                            <button wire:click="markSubmitLater('{{ $docType }}')" type="button"
                                                class="px-3 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                                                Later
                                            </button>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Uploaded
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Activity Proofs Section --}}
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                            Activity Proof / Participation Records <span class="text-red-500">*</span>
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                            Upload proof of your shooting activities (match results, attendance registers, training confirmations, etc.)
                        </p>

                        {{-- Existing activity proofs --}}
                        @if(count($activityProofs) > 0)
                            <div class="space-y-3 mb-4">
                                @foreach($activityProofs as $index => $activity)
                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                    {{ $activityTypeOptions[$activity['activity_type']] ?? $activity['activity_type'] }}
                                                    @if($activity['activity_discipline'])
                                                        - {{ $disciplineOptions[$activity['activity_discipline']] ?? $activity['activity_discipline'] }}
                                                    @endif
                                                </p>
                                                <p class="text-xs text-zinc-500">
                                                    {{ $activity['activity_date'] }}
                                                    @if($activity['activity_venue']) · {{ $activity['activity_venue'] }} @endif
                                                </p>
                                            </div>
                                        </div>
                                        <button wire:click="removeActivityProof({{ $index }})" type="button"
                                            class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Add activity proof form --}}
                        <div class="p-4 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg">
                            <div class="grid gap-4 md:grid-cols-2 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activity Type <span class="text-red-500">*</span></label>
                                    <select wire:model="activityType" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                        <option value="">Select...</option>
                                        @foreach($activityTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Discipline</label>
                                    <select wire:model="activityDiscipline" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                        <option value="">Select...</option>
                                        @foreach($disciplineOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model="activityDate" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Venue</label>
                                    <input type="text" wire:model="activityVenue" placeholder="e.g., False Bay Gun Club" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Organising Body</label>
                                <input type="text" wire:model="activityOrganiser" placeholder="e.g., SAPSA, SAHGCA" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Proof Document <span class="text-red-500">*</span></label>
                                <input type="file" wire:model="currentUpload" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                @error('currentUpload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button wire:click="addActivityProof" type="button"
                                class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Activity Proof
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload Modal --}}
            @if($uploadingDocType)
                <div class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div wire:click="$set('uploadingDocType', '')" class="fixed inset-0 bg-black/50"></div>
                        <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                                Upload {{ $documentTypeLabels[$uploadingDocType] ?? $uploadingDocType }}
                            </h3>
                            <div class="mb-4">
                                <input type="file" wire:model="currentUpload" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                @error('currentUpload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex justify-end gap-3">
                                <button wire:click="$set('uploadingDocType', '')" type="button"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    Cancel
                                </button>
                                <button wire:click="uploadDocument" type="button"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Step 6: Review & Submit --}}
        @if($currentStep === 6)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Review & Submit</h2>

                {{-- Summary --}}
                <div class="space-y-6">
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

                    {{-- Documents Summary --}}
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Documents</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach($documentStatuses as $docType => $docData)
                                <li class="flex items-center gap-2">
                                    @if(in_array($docData['status'], ['uploaded', 'verified']))
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @elseif($docData['status'] === 'pending_upload')
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    @endif
                                    <span class="text-zinc-900 dark:text-white">{{ $documentTypeLabels[$docType] ?? $docType }}</span>
                                    <span class="text-zinc-500 text-xs">
                                        ({{ $docData['status'] === 'uploaded' ? 'Uploaded' : ($docData['status'] === 'pending_upload' ? 'Submit later' : 'Required') }})
                                    </span>
                                </li>
                            @endforeach
                            <li class="flex items-center gap-2">
                                @if(count($activityProofs) > 0)
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @endif
                                <span class="text-zinc-900 dark:text-white">Activity Proofs</span>
                                <span class="text-zinc-500 text-xs">({{ count($activityProofs) }} uploaded)</span>
                            </li>
                        </ul>
                    </div>

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
                                    I, the undersigned, hereby declare that all information provided in this endorsement request is true, correct, and complete to the best of my knowledge. I am an active member in good standing with NRAPA and maintain my dedicated status requirements. I understand that providing false information may result in the revocation of my endorsement and membership. I acknowledge that loss of compliance with dedicated status requirements may void this endorsement.
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
</div>
