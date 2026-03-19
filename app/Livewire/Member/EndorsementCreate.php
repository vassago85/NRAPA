<?php

namespace App\Livewire\Member;

use App\Models\CalibreRequest;
use App\Models\EndorsementComponent;
use App\Models\EndorsementDocument;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\FirearmCalibre;
use App\Models\ShootingActivity;
use App\Models\User;
use App\Models\UserFirearm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Request Endorsement Letter')]
class EndorsementCreate extends Component
{
    // Wizard state (3 steps: Type, Firearm/Component, Review)
    public int $currentStep = 1;

    public int $totalSteps = 3;

    // Step 1: Request type
    public string $requestType = '';

    // Step 2: Entry type = rifle | shotgun | handgun | barrel | action (type of firearm or component)
    public string $endorsementEntryType = '';

    // Component-only data (when entry type is barrel or action)
    public string $componentBarrelDiameter = '';

    public string $componentBarrelMake = '';

    public string $componentBarrelSerial = '';

    public string $componentActionBoltFace = '';

    public string $componentActionType = '';

    public string $componentActionSerial = '';

    // Step 2: Firearm details (SAPS 271 Form Section E)
    // New reference system fields
    public ?int $firearmCalibreId = null;

    public ?string $calibreTextOverride = null;

    public ?int $firearmMakeId = null;

    public ?string $makeTextOverride = null;

    public ?int $firearmModelId = null;

    public ?string $modelTextOverride = null;

    // Legacy fields (kept for backward compatibility during migration)
    public string $firearmCategory = '';       // 1. Type of Firearm (SAPS 271 compliant)

    public string $firearmTypeOther = '';      // Specification when firearmCategory = 'other'

    public string $ignitionType = '';

    public string $actionType = '';            // 1.1 Action

    public string $actionOtherSpecify = '';    // 1.1 Other action (specify)

    public string $metalEngraving = '';        // 1.2 Names and addresses engraved

    public string $calibreCode = '';           // 1.4 Calibre code

    public string $make = '';                  // 1.5 Make (legacy)

    public string $model = '';                 // 1.6 Model (legacy)

    public string $barrelSerialNumber = '';    // 1.7 Barrel serial number

    public string $barrelMake = '';            // 1.8 Barrel Make

    public string $frameSerialNumber = '';     // 1.9 Frame serial number

    public string $frameMake = '';             // 1.10 Frame Make

    public string $receiverSerialNumber = '';  // 1.11 Receiver serial number

    public string $receiverMake = '';          // 1.12 Receiver Make

    public string $serialNumber = '';          // Legacy serial (backward compat)

    public string $licenceSection = '16'; // Always Section 16 for dedicated endorsements

    public string $sapsReference = '';

    public ?int $existingFirearmId = null;

    public ?int $calibreId = null;

    public string $calibreManual = '';

    // FirearmSearchPanel data
    public ?array $firearmPanelData = null;

    // Legacy: components array kept for backward compat when loading old requests with multiple components
    public bool $requestComponent = false;

    public array $components = [];

    // Purpose defaulted to Section 16; optional notes
    public string $purpose = '';

    public string $purposeOtherText = '';

    public string $memberNotes = '';

    // Step 3: Declaration & Review
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

    public string $calibreSearchQuery = '';

    public function mount(?EndorsementRequest $request = null): void
    {
        // Prevent editing if request is not in draft status
        if ($request && ! $request->canEdit()) {
            session()->flash('error', 'This endorsement request cannot be edited as it has already been submitted or approved.');
            $this->redirect(route('member.endorsements.show', $request), navigate: true);

            return;
        }
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'), navigate: true);

            return;
        }
        // Get eligibility summary for display (not for blocking)
        try {
            $this->eligibility = EndorsementRequest::getEligibilitySummary($user);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Endorsement create: getEligibilitySummary failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->eligibility = [
                'eligible' => false,
                'knowledge_test_passed' => false,
                'documents_complete' => false,
                'activities_met' => false,
                'activity_details' => ['met' => false, 'approved_count' => 0, 'required' => 2, 'period_months' => 12, 'message' => 'Unable to load eligibility.'],
                'missing_documents' => [],
                'errors' => [['type' => 'system', 'message' => 'Eligibility could not be loaded. Please try again or contact support.']],
            ];
        }

        if ($request && $request->exists && $request->user_id === $user->id) {
            $this->editingRequest = $request->load(['firearm', 'components']);
            $this->loadFromRequest($request);
        } else {
            // Initialize empty firearm panel data for new requests
            $this->firearmPanelData = [];
        }
    }

    protected function loadFromRequest(EndorsementRequest $request): void
    {
        $this->requestType = $request->request_type;
        $this->purpose = $request->purpose ?? '';
        $this->purposeOtherText = $request->purpose_other_text ?? '';
        $this->memberNotes = $request->member_notes ?? '';
        $this->declarationAccepted = $request->declaration_accepted_at !== null;

        // Set entry type: from firearm category or from first component (component-only request)
        if ($request->firearm) {
            $this->endorsementEntryType = $request->firearm->firearm_category; // rifle, shotgun, handgun, etc.
        } elseif ($request->components->count() > 0) {
            $first = $request->components->first();
            $this->endorsementEntryType = $first->component_type === EndorsementComponent::TYPE_BARREL ? 'barrel' : ($first->component_type === EndorsementComponent::TYPE_ACTION ? 'action' : '');
            if ($this->endorsementEntryType === 'barrel') {
                $this->componentBarrelDiameter = $first->diameter ?? '';
                $this->componentBarrelMake = $first->component_make ?? '';
                $this->componentBarrelSerial = $first->component_serial ?? '';
            } elseif ($this->endorsementEntryType === 'action') {
                $this->componentActionBoltFace = $first->bolt_face ?? '';
                $this->componentActionType = $first->action_type ?? '';
                $this->componentActionSerial = $first->component_serial ?? '';
            }
        }

        // Load firearm (SAPS 271 fields)
        if ($request->firearm) {
            $firearm = $request->firearm;

            // New reference system fields
            $this->firearmCalibreId = $firearm->firearm_calibre_id;
            $this->calibreTextOverride = $firearm->calibre_text_override;
            $this->firearmMakeId = $firearm->firearm_make_id;
            $this->makeTextOverride = $firearm->make_text_override;
            $this->firearmModelId = $firearm->firearm_model_id;
            $this->modelTextOverride = $firearm->model_text_override;

            // Legacy fields (for backward compatibility)
            $this->firearmCategory = $firearm->firearm_category;
            $this->firearmTypeOther = $firearm->firearm_type_other ?? '';
            $this->ignitionType = $firearm->ignition_type ?? '';
            $this->actionType = $firearm->action_type ?? '';
            $this->actionOtherSpecify = $firearm->action_other_specify ?? '';
            $this->metalEngraving = $firearm->metal_engraving ?? '';
            $this->calibreCode = $firearm->calibre_code ?? '';
            $this->make = $firearm->make ?? '';
            $this->model = $firearm->model ?? '';
            $this->serialNumber = $firearm->serial_number ?? '';
            $this->barrelSerialNumber = $firearm->barrel_serial_number ?? '';
            $this->barrelMake = $firearm->barrel_make ?? '';
            $this->frameSerialNumber = $firearm->frame_serial_number ?? '';
            $this->frameMake = $firearm->frame_make ?? '';
            $this->receiverSerialNumber = $firearm->receiver_serial_number ?? '';
            $this->receiverMake = $firearm->receiver_make ?? '';
            $this->licenceSection = $firearm->licence_section ?? '16';
            $this->sapsReference = $firearm->saps_reference ?? '';
            $this->existingFirearmId = $firearm->user_firearm_id;

            // Prepare FirearmSearchPanel data
            $this->firearmPanelData = $this->getFirearmPanelInitialData();
            $this->calibreId = $firearm->firearm_calibre_id ?? $firearm->calibre_id ?? null;
            $this->calibreManual = $firearm->calibre_text_override ?? $firearm->calibre_manual ?? '';
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
                    'calibre_id' => $comp->calibre_id ?? null,
                    'calibre_manual' => $comp->calibre_manual ?? '',
                    'diameter' => $comp->diameter ?? '',
                ];
            }
        }
    }

    /**
     * Get initial data for FirearmSearchPanel component.
     */
    protected function getFirearmPanelInitialData(): array
    {
        // Map firearm category to firearm type
        // Map SAPS 271 compliant categories to FirearmSearchPanel firearm_type
        $firearmType = match ($this->firearmCategory) {
            'rifle' => 'rifle',
            'shotgun' => 'shotgun',
            'handgun' => 'handgun',
            'combination' => 'combination',
            'other' => 'other',
            default => '',
        };

        // Map action type
        $actionType = match ($this->actionType) {
            'semi_auto' => 'semi_automatic',
            'bolt_action', 'lever_action', 'pump_action', 'break_action', 'single_shot', 'revolver' => 'manual',
            default => $this->actionType,
        };

        return [
            'firearm_calibre_id' => $this->firearmCalibreId,
            'calibre_text_override' => $this->calibreTextOverride,
            'firearm_make_id' => $this->firearmMakeId,
            'make_text_override' => $this->makeTextOverride ?: ($this->make ?: null),
            'firearm_model_id' => $this->firearmModelId,
            'model_text_override' => $this->modelTextOverride ?: ($this->model ?: null),
            'firearm_type' => $firearmType,
            'firearm_type_other' => ($this->firearmCategory === 'other' && ! empty($this->firearmTypeOther)) ? $this->firearmTypeOther : '',
            'action_type' => $actionType,
            'action_type_other' => $this->actionOtherSpecify,
            'engraved_text' => $this->metalEngraving,
            'calibre_code' => $this->calibreCode,
            'barrel_serial_number' => $this->barrelSerialNumber,
            'barrel_make_text' => $this->barrelMake,
            'frame_serial_number' => $this->frameSerialNumber,
            'frame_make_text' => $this->frameMake,
            'receiver_serial_number' => $this->receiverSerialNumber,
            'receiver_make_text' => $this->receiverMake,
        ];
    }

    /**
     * Sync FirearmSearchPanel data back to component properties.
     */
    public function syncFirearmPanelData(array $data): void
    {
        $this->firearmCalibreId = $data['firearm_calibre_id'] ?? null;
        $this->calibreTextOverride = $data['calibre_text_override'] ?? null;
        $this->firearmMakeId = $data['firearm_make_id'] ?? null;
        $this->makeTextOverride = $data['make_text_override'] ?? null;
        $this->firearmModelId = $data['firearm_model_id'] ?? null;
        $this->modelTextOverride = $data['model_text_override'] ?? null;

        // Map FirearmSearchPanel firearm_type to SAPS 271 compliant category
        $this->firearmCategory = match ($data['firearm_type'] ?? '') {
            'rifle' => 'rifle',
            'shotgun' => 'shotgun',
            'handgun' => 'handgun',
            'combination' => 'combination',
            'other' => 'other',
            default => '',
        };

        // Set firearm_type_other if provided
        if (isset($data['firearm_type_other']) && $data['firearm_type'] === 'other') {
            $this->firearmTypeOther = $data['firearm_type_other'];
        }

        // Map action type back
        $this->actionType = match ($data['action_type'] ?? '') {
            'semi_automatic' => 'semi_auto',
            'automatic' => 'automatic',
            'manual' => 'bolt_action', // Default
            'other' => 'other',
            default => '',
        };

        $this->actionOtherSpecify = $data['action_type_other'] ?? '';
        $this->metalEngraving = $data['engraved_text'] ?? '';
        $this->calibreCode = $data['calibre_code'] ?? '';
        $this->barrelSerialNumber = $data['barrel_serial_number'] ?? '';
        $this->barrelMake = $data['barrel_make_text'] ?? '';
        $this->frameSerialNumber = $data['frame_serial_number'] ?? '';
        $this->frameMake = $data['frame_make_text'] ?? '';
        $this->receiverSerialNumber = $data['receiver_serial_number'] ?? '';
        $this->receiverMake = $data['receiver_make_text'] ?? '';

        // Set legacy fields for backward compatibility
        if ($this->makeTextOverride) {
            $this->make = $this->makeTextOverride;
        }
        if ($this->modelTextOverride) {
            $this->model = $this->modelTextOverride;
        }
        $this->calibreId = $this->firearmCalibreId;
        $this->calibreManual = $this->calibreTextOverride ?? '';
    }

    #[Computed]
    public function userFirearms()
    {
        return UserFirearm::where('user_id', auth()->id())
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
    public function actionTypeOptions()
    {
        return EndorsementFirearm::getActionTypeOptions($this->firearmCategory ?: null);
    }

    #[Computed]
    public function existingCalibres()
    {
        $query = FirearmCalibre::active()
            ->notObsolete()
            ->orderBy('name');

        // Filter by category if selected
        if ($this->newCalibreCategory && $this->newCalibreCategory !== 'other') {
            // Map calibre request category to FirearmCalibre category
            $category = match ($this->newCalibreCategory) {
                'handgun' => 'handgun',
                'rifle' => 'rifle',
                'shotgun' => 'shotgun',
                default => null,
            };
            if ($category) {
                $query->where('category', $category);
            }
        }
        // If 'other' is selected, show all categories (no filter)

        // Filter by ignition type if selected
        if ($this->newCalibreIgnition) {
            $query->where('ignition', $this->newCalibreIgnition);
        }

        // Search filter if query provided
        if ($this->calibreSearchQuery) {
            $query->search($this->calibreSearchQuery);
        }

        return $query->limit(50)->get();
    }

    #[Computed]
    public function calibres()
    {
        return FirearmCalibre::active()->notObsolete()->orderBy('name')->get();
    }

    // Step navigation
    public function nextStep(): void
    {
        try {
            $this->validateCurrentStep();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return;
        }
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
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
        if (empty($this->requestType)) {
            return 1;
        }
        if (empty($this->endorsementEntryType)) {
            return 2;
        }

        return 3;
    }

    protected function validateCurrentStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate(['requestType' => 'required|in:new,renewal']);

            return;
        }
        if ($this->currentStep === 2) {
            $this->validate(['endorsementEntryType' => 'required|in:rifle,shotgun,handgun,barrel,action']);
            $entry = $this->endorsementEntryType;
            if (in_array($entry, ['rifle', 'shotgun', 'handgun'], true)) {
                $this->validate([
                    'firearmCategory' => 'required|in:rifle,shotgun,handgun,combination,other',
                    'actionType' => 'required',
                    'make' => 'required|string|max:255',
                    'model' => 'required|string|max:255',
                ]);
                if (empty($this->calibreId) && empty($this->calibreManual)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'calibre' => 'Calibre/Gauge is required (select from list or enter manually).',
                    ]);
                }
                if (! $this->hasAtLeastOneSerial) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'serial' => 'At least one serial number is required (barrel, frame, or receiver).',
                    ]);
                }
            } elseif ($entry === 'barrel') {
                $this->validate([
                    'componentBarrelDiameter' => 'required|string|max:100',
                    'componentBarrelMake' => 'required|string|max:255',
                    'componentBarrelSerial' => 'required|string|max:255',
                ]);
            } elseif ($entry === 'action') {
                $this->validate([
                    'componentActionBoltFace' => 'required|string|max:100',
                    'componentActionType' => 'required|string|in:bolt_action,semi_auto,single_shot,other',
                    'componentActionSerial' => 'required|string|max:255',
                ]);
            }

            return;
        }
        // Step 3: no validation for proceeding (declaration validated on submit)
    }

    #[Computed]
    public function canProceedToNextStep(): bool
    {
        if ($this->currentStep === 1) {
            return ! empty($this->requestType) && in_array($this->requestType, ['new', 'renewal']);
        }
        if ($this->currentStep === 2) {
            if (empty($this->endorsementEntryType)) {
                return false;
            }
            $entry = $this->endorsementEntryType;
            if (in_array($entry, ['rifle', 'shotgun', 'handgun'], true)) {
                return $this->firearmCategory === $entry
                    && ! empty($this->actionType)
                    && (! empty($this->calibreId) || ! empty($this->calibreManual))
                    && ! empty($this->make)
                    && ! empty($this->model)
                    && $this->hasAtLeastOneSerial;
            }
            if ($entry === 'barrel') {
                return ! empty($this->componentBarrelDiameter) && ! empty($this->componentBarrelMake) && ! empty($this->componentBarrelSerial);
            }
            if ($entry === 'action') {
                return ! empty($this->componentActionBoltFace) && ! empty($this->componentActionType) && ! empty($this->componentActionSerial);
            }

            return false;
        }

        return $this->currentStep === 3 && $this->declarationAccepted;
    }

    // Request type changed
    public function updatedRequestType(): void
    {
        $this->requestComponent = false;
        $this->components = [];
    }

    // When entry type changes, set firearm category for firearm types so existing form works
    public function updatedEndorsementEntryType(): void
    {
        if (in_array($this->endorsementEntryType, ['rifle', 'shotgun', 'handgun'], true)) {
            $this->firearmCategory = $this->endorsementEntryType;
        } else {
            $this->firearmCategory = '';
        }
        $this->actionType = '';
        $this->componentBarrelDiameter = '';
        $this->componentBarrelMake = '';
        $this->componentBarrelSerial = '';
        $this->componentActionBoltFace = '';
        $this->componentActionType = '';
        $this->componentActionSerial = '';
    }

    // Firearm category changed - reset dependent fields
    public function updatedFirearmCategory(): void
    {
        $this->actionType = '';
        $this->ignitionType = '';
        $this->calibreCode = '';

        // Clear firearm_type_other if not "other"
        if ($this->firearmCategory !== 'other') {
            $this->firearmTypeOther = '';
        }
    }

    // Calibre selected - auto-fill SAPS code if available
    public function updatedCalibreId(): void
    {
        // Always clear the calibre code first to avoid stale data
        $this->calibreCode = '';

        // Calibre code is now handled by FirearmSearchPanel component
    }

    // Open calibre request modal
    public function openCalibreRequestModal(): void
    {
        $this->showCalibreRequestModal = true;
        $this->newCalibreName = $this->calibreManual; // Pre-fill from manual entry
        // Map SAPS 271 category to FirearmCalibre category for CalibreRequest
        $this->newCalibreCategory = match ($this->firearmCategory) {
            'rifle', 'combination' => 'rifle', // Combination can use rifle calibres
            'shotgun' => 'shotgun',
            'handgun' => 'handgun',
            'other' => 'rifle', // Default fallback to rifle
            default => 'rifle',
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
        $this->calibreSearchQuery = '';
    }

    // Submit calibre request
    public function submitCalibreRequest(): void
    {
        $this->validate([
            'newCalibreName' => 'required|string|min:2|max:100',
            'newCalibreCategory' => 'required|in:handgun,rifle,shotgun,muzzleloader,historic',
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

    // Load from existing firearm (canonical SAPS 271 identity)
    public function loadExistingFirearm(): void
    {
        if (! $this->existingFirearmId) {
            return;
        }

        $firearm = UserFirearm::where('id', $this->existingFirearmId)
            ->where('user_id', auth()->id())
            ->with(['components', 'firearmCalibre', 'firearmMake', 'firearmModel'])
            ->first();

        if ($firearm) {
            // SAPS 271 canonical fields
            $this->make = $firearm->make ?? '';
            $this->model = $firearm->model ?? '';
            $this->calibreId = $firearm->calibre_id;
            $this->calibreCode = $firearm->calibre_code ?? '';

            // Map SAPS 271 firearm_type to endorsement firearm_category (SAPS 271 compliant)
            if ($firearm->firearm_type) {
                $categoryMap = [
                    'rifle' => 'rifle',
                    'shotgun' => 'shotgun',
                    'handgun' => 'handgun',
                    'combination' => 'combination',
                    'other' => 'other',
                ];
                $this->firearmCategory = $categoryMap[$firearm->firearm_type] ?? '';

                // Set firearm_type_other if firearm is "other" type
                if ($firearm->firearm_type === 'other' && $firearm->firearm_type_other) {
                    $this->firearmTypeOther = $firearm->firearm_type_other;
                }
            }

            // Map SAPS 271 action to endorsement action_type
            if ($firearm->action) {
                $actionMap = [
                    'semi_automatic' => 'semi_auto',
                    'automatic' => 'semi_auto', // Endorsement form doesn't have separate automatic
                    'manual' => 'bolt_action', // Default manual action
                    'other' => 'other',
                ];
                $this->actionType = $actionMap[$firearm->action] ?? '';
                if ($firearm->action === 'other') {
                    $this->actionOtherSpecify = $firearm->other_action_text ?? '';
                }
            }

            // Load component serials from canonical firearm_components
            $barrel = $firearm->barrelComponent();
            $this->barrelSerialNumber = $barrel?->serial ?? '';
            $this->barrelMake = $barrel?->make ?? '';

            $frame = $firearm->frameComponent();
            $this->frameSerialNumber = $frame?->serial ?? '';
            $this->frameMake = $frame?->make ?? '';

            $receiver = $firearm->receiverComponent();
            $this->receiverSerialNumber = $receiver?->serial ?? '';
            $this->receiverMake = $receiver?->make ?? '';

            // Legacy fallback
            if (empty($this->receiverSerialNumber) && empty($this->frameSerialNumber) && empty($this->barrelSerialNumber)) {
                $this->serialNumber = $firearm->serial_number ?? '';
            }
        }
    }

    // Component management
    public function addComponent(): void
    {
        // Allow components for both new and renewal requests
        $this->components[] = [
            'component_type' => '',
            'component_description' => '',
            'component_serial' => '',
            'component_make' => '',
            'component_model' => '',
            'calibre_id' => null,
            'calibre_manual' => '',
            'diameter' => '', // Barrel diameter (required before chambering)
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
        return ! empty($this->barrelSerialNumber)
            || ! empty($this->frameSerialNumber)
            || ! empty($this->receiverSerialNumber)
            || ! empty($this->serialNumber); // Legacy fallback
    }

    // Check if can submit (purpose is defaulted on save)
    #[Computed]
    public function canSubmit(): bool
    {
        if (! $this->declarationAccepted) {
            return false;
        }
        if (empty($this->requestType)) {
            return false;
        }
        if (empty($this->endorsementEntryType)) {
            return false;
        }
        $entry = $this->endorsementEntryType;
        if (in_array($entry, ['rifle', 'shotgun', 'handgun'], true)) {
            if (empty($this->actionType)) {
                return false;
            }
            if (empty($this->calibreId) && empty($this->calibreManual)) {
                return false;
            }
            if (empty($this->make)) {
                return false;
            }
            if (empty($this->model)) {
                return false;
            }
            if (! $this->hasAtLeastOneSerial) {
                return false;
            }
        } elseif ($entry === 'barrel') {
            if (empty($this->componentBarrelDiameter) || empty($this->componentBarrelMake) || empty($this->componentBarrelSerial)) {
                return false;
            }
        } elseif ($entry === 'action') {
            if (empty($this->componentActionBoltFace) || empty($this->componentActionType) || empty($this->componentActionSerial)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    #[Computed]
    public function submissionErrors(): array
    {
        $errors = [];
        if (! $this->declarationAccepted) {
            $errors[] = 'You must accept the declaration.';
        }
        if (empty($this->requestType)) {
            $errors[] = 'Request type is required.';
        }
        if (empty($this->endorsementEntryType)) {
            $errors[] = 'Please select what you are requesting endorsement for.';
        }
        $entry = $this->endorsementEntryType;
        if (in_array($entry, ['rifle', 'shotgun', 'handgun'], true)) {
            if (empty($this->actionType)) {
                $errors[] = 'Action type is required.';
            }
            if (empty($this->calibreId) && empty($this->calibreManual)) {
                $errors[] = 'Calibre/Gauge is required.';
            }
            if (empty($this->make)) {
                $errors[] = 'Make is required.';
            }
            if (empty($this->model)) {
                $errors[] = 'Model is required.';
            }
            if (! $this->hasAtLeastOneSerial) {
                $errors[] = 'At least one serial number is required.';
            }
        } elseif ($entry === 'barrel') {
            if (empty($this->componentBarrelDiameter)) {
                $errors[] = 'Barrel diameter is required.';
            }
            if (empty($this->componentBarrelMake)) {
                $errors[] = 'Barrel make is required.';
            }
            if (empty($this->componentBarrelSerial)) {
                $errors[] = 'Barrel serial number is required.';
            }
        } elseif ($entry === 'action') {
            if (empty($this->componentActionBoltFace)) {
                $errors[] = 'Bolt face is required.';
            }
            if (empty($this->componentActionType)) {
                $errors[] = 'Action type is required.';
            }
            if (empty($this->componentActionSerial)) {
                $errors[] = 'Action serial number is required.';
            }
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
        if (! $this->canSubmit) {
            session()->flash('error', 'Please complete all required fields before submitting.');

            return;
        }

        // Check terms acceptance
        $user = auth()->user();
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && ! $user->hasAcceptedActiveTerms()) {
            session()->flash('error', 'You must accept the Terms & Conditions before submitting endorsement requests.');
            $this->redirect(route('terms.accept'), navigate: true);

            return;
        }

        $request = $this->saveRequest(true);

        // Refresh to ensure all relationships and documents are loaded
        $request->refresh();
        $request->load(['documents', 'firearm']);

        // Double-check documents were created
        $request->refresh();
        $request->load(['documents']);

        // Check if submission is possible before attempting
        if (! $request->canSubmit()) {
            $errors = $request->getSubmissionErrors();
            $missingDocs = $request->getMissingRequestDocuments();

            if (count($missingDocs) > 0) {
                $docLabels = array_map(fn ($doc) => EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                $errors[] = 'Missing required documents: '.implode(', ', $docLabels);
            }

            if (empty($errors)) {
                $errors[] = 'Unable to submit request. Please ensure all required fields are completed and required documents are uploaded.';
            }

            $errorMessage = 'Failed to submit request: '.implode('. ', $errors);
            session()->flash('error', $errorMessage);

            // Don't redirect - stay on the page so user can see the error and fix issues
            return;
        }

        // Attempt to submit
        $submitResult = $request->submit();

        if ($submitResult) {
            // Refresh again after submission to get updated status
            $request->refresh();
            session()->flash('success', 'Endorsement request submitted successfully!');
            $this->redirect(route('member.endorsements.index'), navigate: true);
        } else {
            // This shouldn't happen if canSubmit() passed, but handle it anyway
            $errors = $request->getSubmissionErrors();
            $missingDocs = $request->getMissingRequestDocuments();

            if (count($missingDocs) > 0) {
                $docLabels = array_map(fn ($doc) => EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                $errors[] = 'Missing required documents: '.implode(', ', $docLabels);
            }

            $errorMessage = 'Failed to submit request: '.implode('. ', $errors);
            session()->flash('error', $errorMessage);
        }
    }

    protected function saveRequest(bool $isSubmitting): EndorsementRequest
    {
        $user = auth()->user();

        // Create or update request
        $request = $this->editingRequest ?? new EndorsementRequest;

        // Prevent saving if request is not in draft status
        if ($request->exists && ! $request->canEdit()) {
            session()->flash('error', 'This endorsement request cannot be edited as it has already been submitted or approved.');
            $this->redirect(route('member.endorsements.show', $request), navigate: true);

            return $request;
        }
        $purpose = $this->purpose ?: EndorsementRequest::PURPOSE_SECTION_16;
        $request->fill([
            'user_id' => $user->id,
            'request_type' => $this->requestType,
            'status' => EndorsementRequest::STATUS_DRAFT,
            'purpose' => $purpose,
            'purpose_other_text' => $purpose === 'other' ? $this->purposeOtherText : null,
            'member_notes' => $this->memberNotes ?: null,
            'declaration_accepted_at' => $this->declarationAccepted ? now() : null,
            'declaration_text' => $this->declarationAccepted ? $this->getDeclarationText() : null,
        ]);
        $request->save();

        $entry = $this->endorsementEntryType;

        if (in_array($entry, ['rifle', 'shotgun', 'handgun'], true)) {
            // Save firearm (SAPS 271 Form Section E fields)
            $firearm = $request->firearm ?? new EndorsementFirearm;
            $firearmData = $this->firearmPanelData ?? $this->getFirearmPanelInitialData();
            $firearmCategory = match ($firearmData['firearm_type'] ?? '') {
                'rifle' => 'rifle',
                'shotgun' => 'shotgun',
                'handgun' => 'handgun',
                'combination' => 'combination',
                'other' => 'other',
                default => $this->firearmCategory,
            };
            $actionType = match ($firearmData['action_type'] ?? '') {
                'semi_automatic' => 'semi_auto',
                'automatic' => 'automatic',
                'manual' => 'bolt_action',
                'other' => 'other',
                default => $this->actionType,
            };
            $firearm->fill([
                'endorsement_request_id' => $request->id,
                'firearm_category' => $firearmCategory,
                'firearm_type_other' => ($firearmCategory === 'other' && ! empty($firearmData['firearm_type_other'])) ? $firearmData['firearm_type_other'] : null,
                'ignition_type' => $this->ignitionType ?: null,
                'action_type' => $actionType,
                'action_other_specify' => $firearmData['action_type_other'] ?? $this->actionOtherSpecify ?: null,
                'metal_engraving' => $firearmData['engraved_text'] ?? $this->metalEngraving ?: null,
                'firearm_calibre_id' => $firearmData['firearm_calibre_id'] ?? $this->firearmCalibreId,
                'calibre_text_override' => $firearmData['calibre_text_override'] ?? $this->calibreTextOverride,
                'firearm_make_id' => $firearmData['firearm_make_id'] ?? $this->firearmMakeId,
                'make_text_override' => $firearmData['make_text_override'] ?? $this->makeTextOverride,
                'firearm_model_id' => $firearmData['firearm_model_id'] ?? $this->firearmModelId,
                'model_text_override' => $firearmData['model_text_override'] ?? $this->modelTextOverride,
                'calibre_id' => $this->calibreId,
                'calibre_manual' => $firearmData['calibre_text_override'] ?? $this->calibreManual ?: null,
                'calibre_code' => $firearmData['calibre_code'] ?? $this->calibreCode ?: null,
                'make' => $firearmData['make_text_override'] ?? $this->make ?: null,
                'model' => $firearmData['model_text_override'] ?? $this->model ?: null,
                'serial_number' => $this->serialNumber ?: null,
                'barrel_serial_number' => $firearmData['barrel_serial_number'] ?? $this->barrelSerialNumber ?: null,
                'barrel_make' => $firearmData['barrel_make_text'] ?? $this->barrelMake ?: null,
                'frame_serial_number' => $firearmData['frame_serial_number'] ?? $this->frameSerialNumber ?: null,
                'frame_make' => $firearmData['frame_make_text'] ?? $this->frameMake ?: null,
                'receiver_serial_number' => $firearmData['receiver_serial_number'] ?? $this->receiverSerialNumber ?: null,
                'receiver_make' => $firearmData['receiver_make_text'] ?? $this->receiverMake ?: null,
                'licence_section' => $this->licenceSection ?: '16',
                'saps_reference' => $this->sapsReference ?: null,
                'user_firearm_id' => $this->existingFirearmId,
            ]);
            $firearm->save();
            $request->components()->delete();
        } else {
            // Component-only: no firearm
            $request->firearm?->delete();
            $request->components()->delete();
            if ($entry === 'barrel') {
                $comp = new EndorsementComponent;
                $comp->fill([
                    'endorsement_request_id' => $request->id,
                    'component_type' => EndorsementComponent::TYPE_BARREL,
                    'diameter' => $this->componentBarrelDiameter,
                    'component_make' => $this->componentBarrelMake,
                    'component_serial' => $this->componentBarrelSerial,
                ]);
                $comp->save();
            } elseif ($entry === 'action') {
                $comp = new EndorsementComponent;
                $comp->fill([
                    'endorsement_request_id' => $request->id,
                    'component_type' => EndorsementComponent::TYPE_ACTION,
                    'bolt_face' => $this->componentActionBoltFace,
                    'action_type' => $this->componentActionType,
                    'component_serial' => $this->componentActionSerial,
                ]);
                $comp->save();
            }
        }

        // Auto-create system-verified documents from existing member documents
        $this->autoVerifyDocumentsFromMemberDocs($request, $user);

        // Refresh to ensure documents are loaded
        $request->refresh();
        $request->load(['firearm', 'documents']);

        return $request;
    }

    protected function autoVerifyDocumentsFromMemberDocs(EndorsementRequest $request, User $user): void
    {
        $requiredDocTypes = EndorsementRequest::getRequiredDocumentTypes($request->request_type);

        foreach ($requiredDocTypes as $docType) {
            // Check if document already exists for this request
            $existing = $request->documents()->where('document_type', $docType)->first();
            if ($existing) {
                continue;
            }

            // Map endorsement document types to member document type slugs
            $memberDocSlug = match ($docType) {
                'sa_id' => 'id-document',
                'proof_of_address' => 'proof-of-address',
                'dedicated_status_certificate' => null, // Check certificates instead
                'membership_proof' => null, // System verified from membership
                'activity_proof' => null, // System verified from activities
                'competency_certificate' => 'competency-certificate',
                default => null,
            };

            $memberDoc = null;
            if ($memberDocSlug) {
                $docTypeModel = \App\Models\DocumentType::where('slug', $memberDocSlug)->first();
                if ($docTypeModel) {
                    $memberDoc = \App\Models\MemberDocument::where('user_id', $user->id)
                        ->where('document_type_id', $docTypeModel->id)
                        ->where('status', 'verified')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        })
                        ->latest('verified_at')
                        ->first();
                }
            }

            // For dedicated status certificate, check certificates
            if ($docType === 'dedicated_status_certificate') {
                $certType = \App\Models\CertificateType::where('slug', 'dedicated-status-certificate')->first();
                if ($certType) {
                    $cert = \App\Models\Certificate::where('user_id', $user->id)
                        ->where('certificate_type_id', $certType->id)
                        ->whereNull('revoked_at')
                        ->where(function ($q) {
                            $q->whereNull('valid_until')
                                ->orWhere('valid_until', '>', now());
                        })
                        ->latest('issued_at')
                        ->first();

                    if ($cert) {
                        // Create system-verified document
                        EndorsementDocument::create([
                            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                            'endorsement_request_id' => $request->id,
                            'document_type' => $docType,
                            'status' => 'system_verified',
                            'metadata' => ['certificate_id' => $cert->id],
                        ]);

                        continue;
                    }
                }
            }

            // For membership proof, auto-verify if active membership exists
            if ($docType === 'membership_proof') {
                if ($user->activeMembership) {
                    EndorsementDocument::create([
                        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                        'endorsement_request_id' => $request->id,
                        'document_type' => $docType,
                        'status' => 'system_verified',
                        'metadata' => ['membership_id' => $user->activeMembership->id],
                    ]);

                    continue;
                }
            }

            // For activity proof, auto-verify if user has approved activities
            if ($docType === 'activity_proof') {
                $activityCheck = EndorsementRequest::checkActivityRequirements($user);
                if ($activityCheck['met']) {
                    EndorsementDocument::create([
                        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                        'endorsement_request_id' => $request->id,
                        'document_type' => $docType,
                        'status' => 'system_verified',
                        'metadata' => [
                            'approved_count' => $activityCheck['approved_count'],
                            'required' => $activityCheck['required'],
                        ],
                    ]);

                    continue;
                }
            }

            // If member document exists, create system-verified endorsement document
            if ($memberDoc) {
                EndorsementDocument::create([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'endorsement_request_id' => $request->id,
                    'document_type' => $docType,
                    'status' => 'system_verified',
                    'member_document_id' => $memberDoc->id,
                    'metadata' => ['auto_verified' => true],
                ]);
            }
        }
    }

    protected function getDeclarationText(): string
    {
        return 'I, the undersigned, hereby declare that:
1. All information provided in this endorsement request is true, correct, and complete to the best of my knowledge.
2. I am an active member in good standing with NRAPA and maintain my dedicated status requirements.
3. I understand that providing false information may result in the revocation of my endorsement and membership.
4. I acknowledge that loss of compliance with dedicated status requirements may void this endorsement.
5. I consent to NRAPA verifying my information with relevant authorities if required.';
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
            'entryTypeOptions' => [
                'rifle' => 'Rifle',
                'shotgun' => 'Shotgun',
                'handgun' => 'Handgun',
                'barrel' => 'Main Firearm Component',
                'action' => 'Action (component)',
            ],
            'componentActionTypeOptions' => EndorsementComponent::getActionTypeOptions(),
            'boltFaceOptions' => EndorsementComponent::getBoltFaceOptions(),
        ];
    }
}
