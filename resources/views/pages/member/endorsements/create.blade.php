<?php

use App\Models\EndorsementComponent;
use App\Models\EndorsementDocument;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\FirearmCalibre;
use App\Models\MemberDocument;
use App\Models\ShootingActivity;
use App\Models\User;
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
    public string $componentDiameter = '';     // Barrel diameter for component endorsements
    public string $licenceSection = '16'; // Always Section 16 for dedicated endorsements
    public string $sapsReference = '';
    public ?int $existingFirearmId = null;
    
    // FirearmSearchPanel data
    public ?array $firearmPanelData = null;

    // Step 3: Components (barrels, actions, etc. - available for both new and renewal)
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
    public string $calibreSearchQuery = '';

    // Legacy calibre fields used by canSubmit / canProceedToNextStep checks
    public ?int $calibreId = null;
    public ?string $calibreManual = null;

    public function mount(?EndorsementRequest $request = null): void
    {
        // Prevent editing if request is not in draft status
        if ($request && !$request->canEdit()) {
            session()->flash('error', 'This endorsement request cannot be edited as it has already been submitted or approved.');
            $this->redirect(route('member.endorsements.show', $request), navigate: true);
            return;
        }
        $user = auth()->user();
        if (!$user) {
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
            $this->calibreId = $firearm->calibre_id;
            $this->calibreManual = $firearm->calibre_manual ?? null;
            $this->make = $firearm->make ?? '';
            $this->model = $firearm->model ?? '';
            $this->serialNumber = $firearm->serial_number ?? '';
            $this->componentDiameter = $firearm->component_diameter ?? '';
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
        }

        // Load components (must stay inside loadFromRequest for Volt class structure)
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
        $firearmType = match($this->firearmCategory) {
            'rifle' => 'rifle',
            'shotgun' => 'shotgun',
            'handgun' => 'handgun',
            'combination' => 'combination',
            'other' => 'other',
            default => '',
        };
        
        // Map action type
        $actionType = match($this->actionType) {
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
            'firearm_type_other' => ($this->firearmCategory === 'other' && !empty($this->firearmTypeOther)) ? $this->firearmTypeOther : '',
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
        $this->firearmCategory = match($data['firearm_type'] ?? '') {
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
        $this->actionType = match($data['action_type'] ?? '') {
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
        $this->calibreId = $this->firearmCalibreId;
        $this->calibreManual = $this->calibreTextOverride;
        $this->make = $this->makeTextOverride
            ?: ($this->firearmMakeId ? \App\Models\FirearmMake::find($this->firearmMakeId)?->name : '') 
            ?: '';
        $this->model = $this->modelTextOverride
            ?: ($this->firearmModelId ? \App\Models\FirearmModel::find($this->firearmModelId)?->name : '')
            ?: '';
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
    public function isComponentCategory(): bool
    {
        return EndorsementFirearm::isComponentCategory($this->firearmCategory);
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
            $category = match($this->newCalibreCategory) {
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

    // Step navigation
    public function nextStep(): void
    {
        // Validate before proceeding
        try {
            $this->validateCurrentStep();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors will be displayed automatically by Livewire
            return;
        }
        
        if ($this->currentStep < $this->totalSteps) {
            $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
            // Skip component step for new requests and component categories
            if ($this->currentStep === 2 && ($this->requestType === 'new' || $isComponent)) {
                $this->currentStep = 4; // Skip to purpose
            } else {
                $this->currentStep++;
            }
            
            // Scroll to top of form after step change
            $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
            // Skip component step for new requests and component categories
            if ($this->currentStep === 4 && ($this->requestType === 'new' || $isComponent)) {
                $this->currentStep = 2; // Back to firearm/component
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
        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);

        $rules = match($this->currentStep) {
            1 => ['requestType' => 'required|in:new,renewal'],
            2 => $isComponent 
                ? [
                    'firearmCategory' => 'required|in:rifle,shotgun,handgun,combination,other,barrel,action',
                    'make' => 'required|string|max:255',
                    'serialNumber' => 'required|string|max:255',
                ]
                : [
                    'firearmCategory' => 'required|in:rifle,shotgun,handgun,combination,other,barrel,action',
                    'actionType' => 'required',
                    'make' => 'required|string|max:255',
                    'model' => 'required|string|max:255',
                ],
            3 => [], // Components optional
            4 => [
                'purpose' => 'required',
                'purposeOtherText' => 'required_if:purpose,other|max:500',
            ],
            5 => [], // Declaration validated on submit
            default => [],
        };

        // Additional validation for step 2
        if ($this->currentStep === 2) {
            // First validate the basic rules
            $this->validate($rules);
            
            if ($isComponent) {
                // For barrel components, diameter is required
                if ($this->firearmCategory === 'barrel' && empty($this->componentDiameter)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'componentDiameter' => 'Barrel diameter is required.',
                    ]);
                }
            } else {
                // For full firearms, calibre and serial are required
                if (empty($this->calibreId) && empty($this->calibreManual)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'calibre' => 'Calibre/Gauge is required (select from list or enter manually).',
                    ]);
                }
                
                if (!$this->hasAtLeastOneSerial) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'serial' => 'At least one serial number is required (barrel, frame, or receiver).',
                    ]);
                }
            }
        } else {
            $this->validate($rules);
        }
    }

    #[Computed]
    public function canProceedToNextStep(): bool
    {
        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);

        return match($this->currentStep) {
            1 => !empty($this->requestType) && in_array($this->requestType, ['new', 'renewal']),
            2 => !empty($this->firearmCategory) && (
                $isComponent
                    ? (!empty($this->make) 
                        && !empty($this->serialNumber)
                        && ($this->firearmCategory !== 'barrel' || !empty($this->componentDiameter)))
                    : (!empty($this->actionType)
                        && (!empty($this->calibreId) || !empty($this->calibreManual))
                        && !empty($this->make)
                        && !empty($this->model)
                        && $this->hasAtLeastOneSerial)
            ),
            3 => true, // Components are optional
            4 => !empty($this->purpose) 
                && ($this->purpose !== 'other' || !empty($this->purposeOtherText)),
            5 => $this->declarationAccepted, // For submit button
            default => false,
        };
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
        $this->ignitionType = '';
        $this->calibreCode = '';
        
        // Clear firearm_type_other if not "other"
        if ($this->firearmCategory !== 'other') {
            $this->firearmTypeOther = '';
        }
        
        // Clear component-specific fields when switching to firearm
        if (!EndorsementFirearm::isComponentCategory($this->firearmCategory)) {
            $this->componentDiameter = '';
        }
        
        // Update FirearmSearchPanel data so it remounts with correct firearm_type/calibre filter
        if (!EndorsementFirearm::isComponentCategory($this->firearmCategory)) {
            $this->firearmPanelData = $this->getFirearmPanelInitialData();
        }
        
        // Clear computed property cache
        unset($this->isComponentCategory);
        unset($this->canProceedToNextStep);
        unset($this->canSubmit);
        unset($this->submissionErrors);
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
        $this->newCalibreCategory = match($this->firearmCategory) {
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
        if (!$this->existingFirearmId) return;

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
        
        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
        
        if ($isComponent) {
            // Component endorsements: just need make and serial
            if (empty($this->make)) return false;
            if (empty($this->serialNumber)) return false;
            if ($this->firearmCategory === 'barrel' && empty($this->componentDiameter)) return false;
        } else {
            // Full firearm endorsements
            if (empty($this->actionType)) return false;
            if (empty($this->calibreId) && empty($this->calibreManual)) return false;
            if (empty($this->make)) return false;
            if (empty($this->model)) return false;
            if (!$this->hasAtLeastOneSerial) return false;
        }
        
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
            $errors[] = 'Firearm/Component type is required.';
        }
        
        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
        
        if ($isComponent) {
            if (empty($this->make)) {
                $errors[] = ucfirst($this->firearmCategory) . ' make is required.';
            }
            if (empty($this->serialNumber)) {
                $errors[] = 'Serial number is required.';
            }
            if ($this->firearmCategory === 'barrel' && empty($this->componentDiameter)) {
                $errors[] = 'Barrel diameter is required.';
            }
        } else {
            if (empty($this->actionType)) {
                $errors[] = 'Action type is required.';
            }
            if (empty($this->calibreId) && empty($this->calibreManual)) {
                $errors[] = 'Calibre/Gauge is required (select from list or enter manually).';
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

        // Check terms acceptance
        $user = auth()->user();
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$user->hasAcceptedActiveTerms()) {
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
        if (!$request->canSubmit()) {
            $errors = $request->getSubmissionErrors();
            $missingDocs = $request->getMissingRequestDocuments();
            
            if (count($missingDocs) > 0) {
                $docLabels = array_map(fn($doc) => EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                $errors[] = 'Missing required documents: ' . implode(', ', $docLabels);
            }
            
            if (empty($errors)) {
                $errors[] = 'Unable to submit request. Please ensure all required fields are completed and required documents are uploaded.';
            }
            
            $errorMessage = 'Failed to submit request: ' . implode('. ', $errors);
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
                $docLabels = array_map(fn($doc) => EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                $errors[] = 'Missing required documents: ' . implode(', ', $docLabels);
            }
            
            $errorMessage = 'Failed to submit request: ' . implode('. ', $errors);
            session()->flash('error', $errorMessage);
        }
    }

    protected function saveRequest(bool $isSubmitting): EndorsementRequest
    {
        $user = auth()->user();

        // Create or update request
        $request = $this->editingRequest ?? new EndorsementRequest();
        
        // Prevent saving if request is not in draft status
        if ($request->exists && !$request->canEdit()) {
            session()->flash('error', 'This endorsement request cannot be edited as it has already been submitted or approved.');
            $this->redirect(route('member.endorsements.show', $request), navigate: true);
            return $request;
        }
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
        $isComponent = EndorsementFirearm::isComponentCategory($this->firearmCategory);
        
        if ($isComponent) {
            // Component endorsement (barrel or action) - simplified data
            $firearm->fill([
                'endorsement_request_id' => $request->id,
                'firearm_category' => $this->firearmCategory,
                'component_diameter' => $this->firearmCategory === 'barrel' ? ($this->componentDiameter ?: null) : null,
                'make' => $this->make ?: null,
                'serial_number' => $this->serialNumber ?: null,
                'barrel_serial_number' => $this->firearmCategory === 'barrel' ? ($this->serialNumber ?: null) : null,
                'licence_section' => $this->licenceSection ?: null,
                'saps_reference' => $this->sapsReference ?: null,
                // Clear firearm-specific fields
                'firearm_type_other' => null,
                'ignition_type' => null,
                'action_type' => null,
                'action_other_specify' => null,
                'metal_engraving' => null,
                'model' => null,
            ]);
        } else {
            // Full firearm endorsement
            // Get data from FirearmSearchPanel if available, otherwise use legacy fields
            $firearmData = $this->firearmPanelData ?? $this->getFirearmPanelInitialData();
            
            // Map FirearmSearchPanel firearm_type to SAPS 271 compliant category
            $firearmCategory = match($firearmData['firearm_type'] ?? '') {
                'rifle' => 'rifle',
                'shotgun' => 'shotgun',
                'handgun' => 'handgun',
                'combination' => 'combination',
                'other' => 'other',
                default => $this->firearmCategory,
            };
            
            // Map action type
            $actionType = match($firearmData['action_type'] ?? '') {
                'semi_automatic' => 'semi_auto',
                'automatic' => 'automatic',
                'manual' => 'bolt_action',
                'other' => 'other',
                default => $this->actionType,
            };
            
            $firearm->fill([
                'endorsement_request_id' => $request->id,
                'firearm_category' => $firearmCategory,          // 1. Type (SAPS 271 compliant)
                'firearm_type_other' => ($firearmCategory === 'other' && !empty($firearmData['firearm_type_other'])) 
                    ? $firearmData['firearm_type_other'] 
                    : null,
                'ignition_type' => $this->ignitionType ?: null,
                'action_type' => $actionType,                     // 1.1 Action
                'action_other_specify' => $firearmData['action_type_other'] ?? $this->actionOtherSpecify ?: null,
                'metal_engraving' => $firearmData['engraved_text'] ?? $this->metalEngraving ?: null,    // 1.2 Engraving
                // New reference system
                'firearm_calibre_id' => $firearmData['firearm_calibre_id'] ?? $this->firearmCalibreId,
                'calibre_text_override' => $firearmData['calibre_text_override'] ?? $this->calibreTextOverride,
                'firearm_make_id' => $firearmData['firearm_make_id'] ?? $this->firearmMakeId,
                'make_text_override' => $firearmData['make_text_override'] ?? $this->makeTextOverride,
                'firearm_model_id' => $firearmData['firearm_model_id'] ?? $this->firearmModelId,
                'model_text_override' => $firearmData['model_text_override'] ?? $this->modelTextOverride,
                // Legacy fields (for backward compatibility)
                'calibre_id' => $this->calibreId,                      // 1.3 Calibre
                'calibre_manual' => $firearmData['calibre_text_override'] ?? $this->calibreManual ?: null,
                'calibre_code' => $firearmData['calibre_code'] ?? $this->calibreCode ?: null,          // 1.4 Calibre code
                'make' => $firearmData['make_text_override'] ?? $this->make ?: null,                         // 1.5 Make
                'model' => $firearmData['model_text_override'] ?? $this->model ?: null,                       // 1.6 Model
                'serial_number' => $this->serialNumber ?: null,        // Legacy
                'barrel_serial_number' => $firearmData['barrel_serial_number'] ?? $this->barrelSerialNumber ?: null,    // 1.7
                'barrel_make' => $firearmData['barrel_make_text'] ?? $this->barrelMake ?: null,            // 1.8
                'frame_serial_number' => $firearmData['frame_serial_number'] ?? $this->frameSerialNumber ?: null,      // 1.9
                'frame_make' => $firearmData['frame_make_text'] ?? $this->frameMake ?: null,              // 1.10
                'receiver_serial_number' => $firearmData['receiver_serial_number'] ?? $this->receiverSerialNumber ?: null, // 1.11
                'receiver_make' => $firearmData['receiver_make_text'] ?? $this->receiverMake ?: null,        // 1.12
                'licence_section' => $this->licenceSection ?: null,
                'saps_reference' => $this->sapsReference ?: null,
                'user_firearm_id' => $this->existingFirearmId,
                'component_diameter' => null,
            ]);
        }
        $firearm->save();
        
        // Refresh the request to ensure relationships are loaded
        $request->refresh();

        // Save components for both new and renewal requests
        if ($this->requestComponent && !empty($this->components)) {
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
                    'diameter' => $compData['diameter'] ?? null,
                ]);
                $component->save();
            }
        } else {
            $request->components()->delete();
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
            $memberDocSlug = match($docType) {
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
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('member.endorsements.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $editingRequest ? 'Edit Endorsement Request' : 'Request Endorsement Letter' }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Complete the form below to request a dedicated status endorsement letter.</p>
            </div>
        </div>
        @include('partials.member-nav-tabs')
    </x-slot>

    {{-- Progress Steps --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'Type',
                    2 => 'Firearm / Component',
                    3 => 'Components',
                    4 => 'Purpose',
                    5 => 'Review',
                ];
                $skipStep3 = $requestType === 'new' || \App\Models\EndorsementFirearm::isComponentCategory($firearmCategory);
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
                                ? 'bg-nrapa-blue text-white' 
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
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
        {{-- Step 1: Request Type --}}
        @if($currentStep === 1)
            <div class="p-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">Select Request Type</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    {{-- New Endorsement --}}
                    <label class="relative cursor-pointer h-full">
                        <input type="radio" wire:model.live="requestType" value="new" class="peer sr-only">
                        <div class="h-full p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-100 peer-checked:shadow-lg peer-checked:shadow-emerald-200/50 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:shadow-emerald-900/50 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4 h-full">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex-shrink-0">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">New Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        First-time endorsement letter for a Section 16 firearm application. Can include component requests (barrels, actions).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- Renewal Endorsement --}}
                    <label class="relative cursor-pointer h-full">
                        <input type="radio" wire:model.live="requestType" value="renewal" class="peer sr-only">
                        <div class="h-full p-6 border-2 rounded-xl transition-all peer-checked:border-emerald-600 peer-checked:bg-emerald-100 peer-checked:shadow-lg peer-checked:shadow-emerald-200/50 dark:peer-checked:bg-emerald-900/30 dark:peer-checked:shadow-emerald-900/50 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                            <div class="flex items-start gap-4 h-full">
                                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex-shrink-0">
                                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Renewal Endorsement</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        Endorsement renewal for existing firearms.
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

        {{-- Step 2: Firearm / Component Details --}}
        @if($currentStep === 2)
            <div class="p-6" 
                 x-data="{ panelData: @entangle('firearmPanelData') }"
                 @firearm-data-updated.window="panelData = $event.detail.data; $wire.syncFirearmPanelData(panelData)">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">
                    {{ $this->isComponentCategory ? 'What are you requesting endorsement for?' : 'Firearm Details (SAPS 271 Form Section E)' }}
                </h2>

                <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">All endorsements are Section 16 for dedicated sport or hunting.</p>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            @foreach($categoryOptions as $value => $label)
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model.live="firearmCategory" value="{{ $value }}" class="peer sr-only">
                                    <div class="p-4 text-center border-2 rounded-lg transition-all peer-checked:border-nrapa-blue peer-checked:bg-nrapa-blue peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-nrapa-blue/20 dark:peer-checked:bg-nrapa-blue dark:peer-checked:text-white dark:peer-checked:shadow-nrapa-blue/30 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white dark:bg-zinc-800">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white peer-checked:text-white">{{ $label }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('firearmCategory') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($firearmCategory && $this->isComponentCategory)
                        {{-- Component-specific fields (barrel or action) --}}
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg mb-4">
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                <strong>{{ ucfirst($firearmCategory) }} endorsement.</strong> 
                                Please provide the {{ $firearmCategory }} details below.
                            </p>
                        </div>

                        @if($firearmCategory === 'barrel')
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Barrel diameter <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model.live="componentDiameter" placeholder="e.g., 6.5mm, .308, 7mm" 
                                    class="w-full max-w-md px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('componentDiameter') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Make <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model.live="make" placeholder="e.g., Bartlein, Krieger, Bighorn" 
                                class="w-full max-w-md px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            @error('make') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Serial number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model.live="serialNumber" placeholder="Serial number" 
                                class="w-full max-w-md px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                            @error('serialNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                    @elseif($firearmCategory)
                        {{-- Full firearm: show Virtual Safe loader, FirearmSearchPanel, and SAPS 271 fields --}}
                        @if($this->userFirearms->count() > 0)
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Load from your Virtual Safe</label>
                                <div class="flex gap-3">
                                    <select wire:model="existingFirearmId" class="flex-1 px-4 py-2 border border-blue-300 dark:border-blue-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
                                        <option value="">Select a firearm...</option>
                                        @foreach($this->userFirearms as $uf)
                                            <option value="{{ $uf->id }}">
                                                {{ $uf->make }} {{ $uf->model }} 
                                                @if($uf->calibre_display) ({{ $uf->calibre_display }}) @endif
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

                        {{-- FirearmSearchPanel component (re-mounts when category changes to set calibre filter) --}}
                        @php
                            $firearmPanelData = $this->firearmPanelData ?? [];
                        @endphp
                        <livewire:firearm-search-panel 
                            wire:key="endorsement-firearm-panel-{{ $firearmCategory }}-{{ $editingRequest?->id ?? 'new' }}"
                            :initial-data="$firearmPanelData"
                        />

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
                                @error('actionType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

                        {{-- Note: Calibre, Make, and Model are handled by FirearmSearchPanel component above --}}

                        {{-- 1.5 & 1.6 Make and Model (Legacy - handled by FirearmSearchPanel) --}}
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Make <span class="text-red-500">*</span>
                                    <span class="text-xs text-zinc-500">(1.5)</span>
                                </label>
                                <input type="text" wire:model.live="make" placeholder="e.g., Glock, CZ, Howa" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('make') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Model <span class="text-red-500">*</span>
                                    <span class="text-xs text-zinc-500">(1.6)</span>
                                </label>
                                <input type="text" wire:model.live="model" placeholder="e.g., 17, Shadow 2, 1500" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                @error('model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                    <input type="text" wire:model.live="barrelSerialNumber" placeholder="Barrel serial" 
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
                                    <input type="text" wire:model.live="frameSerialNumber" placeholder="Frame serial" 
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
                                    <input type="text" wire:model.live="receiverSerialNumber" placeholder="Receiver serial" 
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
                            @error('serial') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Licence Information --}}
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Licence Section</label>
                                <input type="text" value="Section 16 - Dedicated Hunter/Sport Shooter" readonly disabled
                                    class="w-full px-4 py-2 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-not-allowed">
                                <p class="mt-1 text-xs text-zinc-500">Endorsement letters are for Section 16 licences only</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">SAPS/CFR Reference <span class="text-xs text-zinc-500">(Optional)</span></label>
                                <input type="text" wire:model="sapsReference" placeholder="Existing CFR reference number (if renewal)" 
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white font-mono">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Step 3: Components (Barrels, Actions, etc.) --}}
        @if($currentStep === 3)
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
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                                            Specify either <strong>calibre</strong> or <strong>diameter</strong> for the barrel (diameter is used when barrel is licensed before chambering).
                                        </p>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                                    Diameter
                                                    <span class="text-xs text-zinc-500">(For barrels before chambering)</span>
                                                </label>
                                                <input type="text" wire:model="components.{{ $index }}.diameter" 
                                                    placeholder="e.g., 6.5mm, .308, 7.62mm"
                                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre</label>
                                                <select wire:model="components.{{ $index }}.calibre_id" 
                                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                                    <option value="">Select calibre...</option>
                                                    @foreach($this->calibres as $cal)
                                                        <option value="{{ $cal->id }}">{{ $cal->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if(empty($components[$index]['calibre_id']))
                                                    <input type="text" wire:model="components.{{ $index }}.calibre_manual" 
                                                        placeholder="Or enter calibre manually"
                                                        class="w-full mt-2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                                @endif
                                            </div>
                                        </div>
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
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Prerequisites Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Knowledge Test --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['knowledge_test_passed'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['knowledge_test_passed'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['knowledge_test_passed'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Knowledge Test
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['knowledge_test_passed'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $eligibility['knowledge_test_passed'] ? 'Passed' : 'Not Completed' }}
                                </p>
                            </div>

                            {{-- Required Documents --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['documents_complete'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['documents_complete'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['documents_complete'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Documents
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['documents_complete'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    @if($eligibility['documents_complete'])
                                        All Required
                                    @else
                                        Missing {{ count($eligibility['missing_documents'] ?? []) }} doc(s)
                                    @endif
                                </p>
                            </div>

                            {{-- Activities --}}
                            <div class="p-4 rounded-lg border-2 transition-all {{ $eligibility['activities_met'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($eligibility['activities_met'])
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    <span class="font-semibold text-sm {{ $eligibility['activities_met'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200' }}">
                                        Activities
                                    </span>
                                </div>
                                <p class="text-xs {{ $eligibility['activities_met'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                    {{ $eligibility['activity_details']['approved_count'] ?? 0 }} / {{ $eligibility['activity_details']['required'] ?? 2 }} required
                                </p>
                            </div>
                        </div>
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

                    {{-- Firearm/Component Summary --}}
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">
                            {{ $this->isComponentCategory ? 'Component Details' : 'Firearm Details' }}
                        </h3>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">{{ $categoryOptions[$firearmCategory] ?? $firearmCategory }}</dd>
                            </div>
                            @if($this->isComponentCategory)
                                @if($firearmCategory === 'barrel' && $componentDiameter)
                                    <div>
                                        <dt class="text-zinc-500">Barrel Diameter</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $componentDiameter }}</dd>
                                    </div>
                                @endif
                                @if($make)
                                    <div>
                                        <dt class="text-zinc-500">Make</dt>
                                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $make }}</dd>
                                    </div>
                                @endif
                                @if($serialNumber)
                                    <div>
                                        <dt class="text-zinc-500">Serial Number</dt>
                                        <dd class="font-medium font-mono text-zinc-900 dark:text-white">{{ $serialNumber }}</dd>
                                    </div>
                                @endif
                            @else
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
                            @endif
                        </dl>
                    </div>

                    {{-- Components Summary --}}
                    @if($requestComponent && count($components) > 0)
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
                                                @if($comp['component_type'] === 'barrel')
                                                    @if($comp['diameter'])
                                                        <span class="text-zinc-500">(Diameter: {{ $comp['diameter'] }})</span>
                                                    @elseif(!empty($comp['calibre_id']) || !empty($comp['calibre_manual']))
                                                        <span class="text-zinc-500">(Calibre: {{ $comp['calibre_manual'] ?? 'Selected' }})</span>
                                                    @endif
                                                @endif
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
                                        {{ is_string($error) ? $error : ($error['message'] ?? json_encode($error)) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Debug: Show document status if trying to submit --}}
                    @php
                        // Check document status for debugging
                        if ($this->canSubmit && $this->declarationAccepted) {
                            try {
                                $testRequest = $this->editingRequest ?? new \App\Models\EndorsementRequest();
                                if ($testRequest->exists || $this->editingRequest) {
                                    $testRequest->request_type = $this->requestType;
                                    $missingDocs = $testRequest->getMissingRequestDocuments();
                                    if (count($missingDocs) > 0) {
                                        $docLabels = array_map(fn($doc) => \App\Models\EndorsementRequest::getDocumentTypeLabel($doc), $missingDocs);
                                    }
                                }
                            } catch (\Exception $e) {
                                // Ignore errors in debug display
                            }
                        }
                    @endphp

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
                            @disabled(!$this->canProceedToNextStep)
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-nrapa-blue">
                            <span wire:loading.remove wire:target="nextStep">Next</span>
                            <span wire:loading wire:target="nextStep">Processing...</span>
                        </button>
                    @endif
                @else
                    <button wire:click="submitRequest" type="button"
                        @disabled(!$this->canSubmit)
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-nrapa-blue">
                        <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                        <span wire:loading wire:target="submitRequest">Submitting...</span>
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
                    {{-- Existing Calibres List --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Existing Calibres
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                (Check if your calibre already exists)
                            </span>
                        </label>
                        
                        {{-- Search existing calibres --}}
                        <div class="mb-2">
                            <input type="text" 
                                wire:model.live.debounce.300ms="calibreSearchQuery"
                                placeholder="Search existing calibres..."
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        </div>

                        {{-- List of existing calibres --}}
                        <div class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
                            @if($this->existingCalibres->count() > 0)
                                <div class="p-2 space-y-1">
                                    @foreach($this->existingCalibres as $calibre)
                                        <div class="px-3 py-2 text-sm rounded hover:bg-zinc-200 dark:hover:bg-zinc-800 cursor-pointer transition-colors"
                                            wire:click="$set('newCalibreName', '{{ $calibre->name }}')"
                                            title="Click to use this calibre name">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-zinc-900 dark:text-white">{{ $calibre->name }}</span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $calibre->category_label }} • {{ $calibre->ignition_label }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($calibreSearchQuery)
                                        No calibres found matching "{{ $calibreSearchQuery }}"
                                    @elseif($newCalibreCategory || $newCalibreIgnition)
                                        No calibres found for selected filters. Try adjusting category or ignition type.
                                    @else
                                        Select a category and ignition type to see existing calibres.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Calibre Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Calibre Name <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                (Enter the calibre you want to request)
                            </span>
                        </label>
                        <input type="text" wire:model="newCalibreName" 
                            placeholder="e.g., 6.5 PRC, .300 PRC, 6mm ARC"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        @error('newCalibreName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if($newCalibreName && $this->existingCalibres->contains('name', $newCalibreName))
                            <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                                ⚠️ This calibre already exists in our database. Please check the list above.
                            </p>
                        @endif
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="newCalibreCategory" 
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                            <option value="">Select category...</option>
                            <option value="handgun">Handgun</option>
                            <option value="rifle">Rifle</option>
                            <option value="shotgun">Shotgun</option>
                            <option value="muzzleloader">Muzzleloader</option>
                            <option value="historic">Historic</option>
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
                                <input type="radio" wire:model.live="newCalibreIgnition" value="centerfire" 
                                    class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-zinc-700 dark:text-zinc-300">Centerfire</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="newCalibreIgnition" value="rimfire"
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
