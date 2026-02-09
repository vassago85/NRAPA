<?php

use App\Models\ActivityType;
use App\Models\ActivityTag;
use App\Models\FirearmCalibre;
use App\Models\Country;
use App\Models\FirearmType;
use App\Models\LoadData;
use App\Models\MemberDocument;
use App\Models\DocumentType;
use App\Models\Province;
use App\Models\ShootingActivity;
use App\Models\UserFirearm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // Form fields
    public ?string $track = null; // 'hunting' or 'sport'
    public ?int $activity_tag_id = null; // Single tag selection (replaces activity_type_id) - this is the activity type now
    public ?string $activity_date = null;
    
    // Firearm selection - from armoury or manual
    public string $firearm_source = 'armoury'; // 'armoury' or 'manual'
    public ?int $user_firearm_id = null;
    public ?int $load_data_id = null;
    public ?int $firearm_type_id = null;
    public ?int $calibre_id = null;
    
    // Location
    public ?string $location = null;
    public string $country_selection = 'south_africa'; // 'south_africa' or 'other'
    public ?int $country_id = null;
    public ?string $country_name = null; // Manual entry when "other" is selected
    public ?int $province_id = null;
    public ?string $province_name = null; // Manual entry when "other" is selected
    public ?string $closest_town_city = null;
    public ?string $description = null;
    public ?int $rounds_fired = null;
    public $proof_document = null;
    public $additional_document = null;

    // Dynamic options
    public array $loadDataOptions = [];
    
    // Calibre search
    public string $calibreSearch = '';
    public bool $showCalibreDropdown = false;

    protected function rules(): array
    {
        $rules = [
            'track' => ['required', 'in:hunting,sport'],
            'activity_tag_id' => ['required', 'exists:activity_tags,id'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'firearm_source' => ['required', 'in:armoury,manual'],
            'location' => ['required', 'string', 'max:255'],
            'country_selection' => ['required', 'in:south_africa,other'],
            'country_id' => ['required_if:country_selection,south_africa', 'nullable', 'exists:countries,id'],
            'country_name' => ['required_if:country_selection,other', 'nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'province_name' => ['nullable', 'string', 'max:255'],
            'closest_town_city' => ['required', 'string', 'max:255'],
            'rounds_fired' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'proof_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'additional_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
        
        // Only require province if country is South Africa
        $country = Country::find($this->country_id);
        if ($country && $country->code === 'ZA') {
            // Province is optional even for South Africa, but we can make it required if needed
            // For now, keeping it optional
        }

        if ($this->firearm_source === 'armoury') {
            $rules['user_firearm_id'] = ['required', 'exists:user_firearms,id'];
            $rules['load_data_id'] = ['nullable', 'exists:load_data,id'];
        } else {
            $rules['firearm_type_id'] = ['required', 'exists:firearm_types,id'];
            $rules['calibre_id'] = ['required', 'exists:firearm_calibres,id'];
        }

        return $rules;
    }

    public function mount(): void
    {
        // Check if user has dedicated status membership
        if (!auth()->user()->activeMembership?->type?->allows_dedicated_status) {
            session()->flash('error', 'Your membership type does not allow dedicated status activities.');
            $this->redirect(route('activities.index'));
            return;
        }

        $user = auth()->user();

        // Set default country selection to South Africa
        $this->country_selection = 'south_africa';
        $this->country_id = Country::where('code', 'ZA')->first()?->id;
        
        // Set default closest city from user's address
        if ($user->physical_address) {
            // Try to extract city from address (usually last part before postal code or country)
            $addressParts = array_map('trim', explode(',', $user->physical_address));
            if (count($addressParts) >= 2) {
                // Usually format is: Street, Suburb, City, Province, Code
                // Try to get the 3rd part (city) or 2nd-to-last
                $this->closest_town_city = $addressParts[min(2, count($addressParts) - 2)] ?? $addressParts[1] ?? null;
            }
        }
        
        // Check for preselected firearm from query string
        $preselectedFirearm = request()->query('firearm');
        if ($preselectedFirearm) {
            $firearm = UserFirearm::where('uuid', $preselectedFirearm)
                ->where('user_id', auth()->id())
                ->first();
            
            if ($firearm) {
                $this->firearm_source = 'armoury';
                $this->user_firearm_id = $firearm->id;
                $this->loadLoadDataOptions();
            }
        }
        
        // If user has firearms in armoury, default to armoury; otherwise manual
        $hasFirearms = UserFirearm::where('user_id', auth()->id())->active()->exists();
        if ($hasFirearms) {
            $this->firearm_source = 'armoury';
        } else {
            $this->firearm_source = 'manual';
        }
    }


    public function updatedUserFirearmId($value): void
    {
        $this->load_data_id = null;
        $this->loadLoadDataOptions();
    }

    public function updatedFirearmSource($value): void
    {
        // Reset fields when switching
        if ($value === 'armoury') {
            $this->firearm_type_id = null;
            $this->calibre_id = null;
            $this->calibreSearch = '';
            $this->showCalibreDropdown = false;
        } else {
            $this->user_firearm_id = null;
            $this->load_data_id = null;
            $this->loadDataOptions = [];
        }
    }

    protected function loadLoadDataOptions(): void
    {
        if ($this->user_firearm_id) {
            $this->loadDataOptions = LoadData::where('user_id', auth()->id())
                ->where('user_firearm_id', $this->user_firearm_id)
                ->whereIn('status', ['tested', 'approved'])
                ->get()
                ->map(fn($load) => [
                    'id' => $load->id,
                    'name' => $load->name,
                    'description' => $load->bullet_description,
                ])
                ->toArray();
        } else {
            $this->loadDataOptions = [];
        }
    }

    public function submit(): void
    {
        $this->validate();

        // Get firearm details based on source
        $firearmTypeId = null;
        $calibreId = null;
        $userFirearmId = null;
        $loadDataId = null;

        if ($this->firearm_source === 'armoury' && $this->user_firearm_id) {
            $userFirearm = UserFirearm::find($this->user_firearm_id);
            $firearmTypeId = $userFirearm->firearm_type_id;
            $calibreId = $userFirearm->firearm_calibre_id ?? $userFirearm->calibre_id;
            if ($calibreId) {
                $calibre = FirearmCalibre::find($calibreId);
                if ($calibre) {
                    $this->calibreSearch = $calibre->name;
                }
            }
            $userFirearmId = $userFirearm->id;
            $loadDataId = $this->load_data_id;
        } else {
            $firearmTypeId = $this->firearm_type_id;
            $calibreId = $this->calibre_id;
        }

        // Get the activity type based on track (Dedicated Hunting or Dedicated Sport-Shooting)
        $activityType = ActivityType::where('track', $this->track)
            ->whereIn('slug', ['dedicated-hunting', 'dedicated-sport-shooting'])
            ->first();

        if (!$activityType) {
            throw new \Exception('Activity type not found for track: ' . $this->track);
        }

        // Create the activity
        $activity = ShootingActivity::create([
            'user_id' => auth()->id(),
            'track' => $this->track,
            'activity_type_id' => $activityType->id, // Set to Dedicated Hunting or Dedicated Sport-Shooting
            'activity_date' => $this->activity_date,
            'firearm_type_id' => $firearmTypeId,
            'calibre_id' => $calibreId,
            'user_firearm_id' => $userFirearmId,
            'load_data_id' => $loadDataId,
            'location' => $this->location,
            'country_id' => $this->country_selection === 'south_africa' ? $this->country_id : null,
            'country_name' => $this->country_selection === 'other' ? $this->country_name : null,
            'province_id' => $this->country_selection === 'south_africa' ? $this->province_id : null,
            'province_name' => $this->country_selection === 'other' ? $this->province_name : null,
            'closest_town_city' => $this->closest_town_city,
            'rounds_fired' => $this->rounds_fired,
            'description' => $this->description,
            'activity_year_month_start' => 1, // Fixed: January (activity period is 1 Jan - 30 Sep)
            'status' => 'pending',
        ]);

        // Attach the selected tag (single tag)
        if ($this->activity_tag_id) {
            $activity->tags()->attach($this->activity_tag_id);
        }

        // Handle file uploads and create MemberDocument records
        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
        
        // Get or create document type for activity evidence
        $evidenceDocumentType = DocumentType::firstOrCreate(
            ['slug' => 'activity-evidence'],
            [
                'name' => 'Activity Evidence',
                'description' => 'Proof of activity participation (photos, certificates, etc.)',
                'is_active' => true,
                'expiry_months' => null,
                'archive_months' => 12,
                'sort_order' => 100,
            ]
        );
        
        $evidenceDocumentId = null;
        if ($this->proof_document) {
            // Generate unique filename
            $filename = Str::random(40) . '.' . $this->proof_document->getClientOriginalExtension();
            $directory = "documents/" . auth()->user()->uuid . "/activity-evidence";
            
            // Store file
            $path = $this->proof_document->storeAs(
                $directory,
                $filename,
                [
                    'disk' => $disk,
                    'visibility' => 'private',
                    'options' => [
                        'ContentDisposition' => 'inline',
                        'ContentType' => $this->proof_document->getMimeType(),
                    ],
                ]
            );
            
            // Create MemberDocument record
            $evidenceDocument = MemberDocument::create([
                'user_id' => auth()->id(),
                'document_type_id' => $evidenceDocumentType->id,
                'file_path' => $path,
                'original_filename' => $this->proof_document->getClientOriginalName(),
                'mime_type' => $this->proof_document->getMimeType(),
                'file_size' => $this->proof_document->getSize(),
                'status' => 'pending',
                'uploaded_at' => now(),
                'metadata' => [
                    'activity_id' => $activity->id,
                    'activity_date' => $activity->activity_date->format('Y-m-d'),
                    'activity_type' => $activity->activityType?->name,
                ],
            ]);
            
            $evidenceDocumentId = $evidenceDocument->id;
        }

        $additionalDocumentId = null;
        if ($this->additional_document) {
            // Generate unique filename
            $filename = Str::random(40) . '.' . $this->additional_document->getClientOriginalExtension();
            $directory = "documents/" . auth()->user()->uuid . "/activity-evidence";
            
            // Store file
            $path = $this->additional_document->storeAs(
                $directory,
                $filename,
                [
                    'disk' => $disk,
                    'visibility' => 'private',
                    'options' => [
                        'ContentDisposition' => 'inline',
                        'ContentType' => $this->additional_document->getMimeType(),
                    ],
                ]
            );
            
            // Create MemberDocument record
            $additionalDocument = MemberDocument::create([
                'user_id' => auth()->id(),
                'document_type_id' => $evidenceDocumentType->id,
                'file_path' => $path,
                'original_filename' => $this->additional_document->getClientOriginalName(),
                'mime_type' => $this->additional_document->getMimeType(),
                'file_size' => $this->additional_document->getSize(),
                'status' => 'pending',
                'uploaded_at' => now(),
                'metadata' => [
                    'activity_id' => $activity->id,
                    'activity_date' => $activity->activity_date->format('Y-m-d'),
                    'activity_type' => $activity->activityType?->name,
                    'is_additional' => true,
                ],
            ]);
            
            $additionalDocumentId = $additionalDocument->id;
        }
        
        // Update activity with document IDs
        if ($evidenceDocumentId || $additionalDocumentId) {
            $activity->update([
                'evidence_document_id' => $evidenceDocumentId,
                'additional_document_id' => $additionalDocumentId,
            ]);
        }

        session()->flash('success', 'Activity submitted successfully and is pending review.');
        $this->redirect(route('activities.index'));
    }

    public function updatedTrack(): void
    {
        // Reset activity tag when track changes
        $this->activity_tag_id = null;
    }
    
    public function updatedCountrySelection($value): void
    {
        // Reset fields when switching between South Africa and Other
        if ($value === 'south_africa') {
            $this->country_name = null;
            $this->province_name = null;
            $this->country_id = Country::where('code', 'ZA')->first()?->id;
        } else {
            $this->country_id = null;
            $this->province_id = null;
        }
    }

    public function updatedCountryId($value): void
    {
        // Reset province if country is not South Africa
        $country = Country::find($value);
        if (!$country || $country->code !== 'ZA') {
            $this->province_id = null;
        }
    }
    
    #[Computed]
    public function isSouthAfrica()
    {
        return $this->country_selection === 'south_africa';
    }

    #[Computed]
    public function activityTypes()
    {
        if (!$this->track) {
            return collect();
        }

        return ActivityType::active()
            ->where('track', $this->track)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function activityTags()
    {
        if (!$this->track) {
            return collect();
        }

        return ActivityTag::active()
            ->forTrack($this->track)
            ->ordered()
            ->get();
    }

    #[Computed]
    public function firearmTypes()
    {
        return FirearmType::active()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function calibres()
    {
        return FirearmCalibre::active()->ordered()->get();
    }

    #[Computed]
    public function filteredCalibres()
    {
        $calibres = $this->calibres;
        
        if (empty($this->calibreSearch)) {
            return $calibres;
        }
        
        $searchTerm = strtolower($this->calibreSearch);
        return $calibres->filter(function ($calibre) use ($searchTerm) {
            return str_contains(strtolower($calibre->name), $searchTerm);
        });
    }

    public function selectCalibre(int $calibreId): void
    {
        $calibre = FirearmCalibre::find($calibreId);
        if ($calibre) {
            $this->calibre_id = $calibreId;
            $this->calibreSearch = $calibre->name;
            $this->showCalibreDropdown = false;
        }
    }

    public function updatedCalibreSearch(): void
    {
        $this->showCalibreDropdown = strlen($this->calibreSearch) >= 1;
        
        // If calibre_id is set but search doesn't match, clear it
        if ($this->calibre_id) {
            $selected = FirearmCalibre::find($this->calibre_id);
            if ($selected && !str_contains(strtolower($selected->name), strtolower($this->calibreSearch))) {
                $this->calibre_id = null;
            } elseif ($selected && strtolower($selected->name) === strtolower($this->calibreSearch)) {
                // Search matches selected, keep it
                return;
            }
        }
    }

    public function updatedCalibreId($value): void
    {
        // Update search text when calibre_id changes (e.g., from armoury selection)
        if ($value) {
            $calibre = FirearmCalibre::find($value);
            if ($calibre && empty($this->calibreSearch)) {
                $this->calibreSearch = $calibre->name;
            }
            $this->showCalibreDropdown = false;
        } else {
            if (empty($this->calibreSearch)) {
                $this->showCalibreDropdown = false;
            }
        }
    }

    #[Computed]
    public function countries()
    {
        return Country::active()->ordered()->get();
    }

    #[Computed]
    public function provinces()
    {
        return Province::active()->ordered()->get();
    }

    #[Computed]
    public function userFirearms()
    {
        return UserFirearm::where('user_id', auth()->id())
            ->active()
            ->with(['firearmType', 'firearmCalibre', 'firearmMake', 'firearmModel'])
            ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('activities.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Submit Activity</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Add a hunting or sport-shooting related activity for your membership qualifications</p>
            </div>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-8">
        <!-- Activity Information -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Activity Information</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Track -->
                <div>
                    <label for="track" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Track <span class="text-red-500">*</span></label>
                    <select id="track" wire:model.live="track" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                        <option value="">Select Track</option>
                        <option value="hunting">Dedicated Hunting</option>
                        <option value="sport">Dedicated Sport Shooting</option>
                    </select>
                    @error('track') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Activity Tag (replaces Activity Type) -->
                <div wire:key="activity-tag-{{ $track }}">
                    <label for="activity_tag_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activity Type <span class="text-red-500">*</span></label>
                    <select id="activity_tag_id" wire:model="activity_tag_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue" @disabled(!$track)>
                        <option value="">Select Activity Type</option>
                        @if($track)
                            @foreach($this->activityTags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->label }}</option>
                            @endforeach
                            @if($this->activityTags->isEmpty())
                                <option value="" disabled>No activity types available for {{ $track === 'hunting' ? 'Dedicated Hunting' : 'Dedicated Sport Shooting' }} track</option>
                            @endif
                        @endif
                    </select>
                    @error('activity_tag_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Activity Date -->
                <div>
                    <label for="activity_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date of Activity <span class="text-red-500">*</span></label>
                    <input type="date" id="activity_date" wire:model="activity_date" max="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                    @error('activity_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Rounds Fired -->
                <div>
                    <label for="rounds_fired" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rounds Fired</label>
                    <input type="number" id="rounds_fired" wire:model="rounds_fired" min="1" max="99999" placeholder="e.g., 50" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Number of rounds fired during this activity (used for barrel life tracking)</p>
                    @error('rounds_fired') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Activity Location</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Location Name -->
                <div>
                    <label for="location" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Location / Venue <span class="text-red-500">*</span></label>
                    <input type="text" id="location" wire:model="location" placeholder="e.g., Magaliesberg Shooting Range" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue">
                    @error('location') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Country Selection -->
                <div>
                    <label for="country_selection" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country <span class="text-red-500">*</span></label>
                    <select id="country_selection" wire:model.live="country_selection" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                        <option value="south_africa">South Africa</option>
                        <option value="other">Other</option>
                    </select>
                    @error('country_selection') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                @if($this->country_selection === 'south_africa')
                    <!-- Province (only show for South Africa) -->
                    <div>
                        <label for="province_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Province</label>
                        <select id="province_id" wire:model="province_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                            <option value="">Select Province</option>
                            @foreach($this->provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @else
                    <!-- Manual Country Entry -->
                    <div>
                        <label for="country_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country Name <span class="text-red-500">*</span></label>
                        <input type="text" id="country_name" wire:model="country_name" placeholder="e.g., Mozambique, Namibia, Botswana" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue">
                        @error('country_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Manual Province/State Entry -->
                    <div>
                        <label for="province_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Province/State/Region</label>
                        <input type="text" id="province_name" wire:model="province_name" placeholder="e.g., Maputo Province, Khomas Region" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue">
                        @error('province_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif

                <!-- Closest Town/City -->
                <div>
                    <label for="closest_town_city" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Closest Town/City <span class="text-red-500">*</span></label>
                    <input type="text" id="closest_town_city" wire:model="closest_town_city" placeholder="e.g., Pretoria" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue">
                    @error('closest_town_city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Firearm/Calibre -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Firearm / Calibre</h2>

            <!-- Source Selection - Always show -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Select Firearm From</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer p-3 rounded-lg border-2 transition-colors {{ $firearm_source === 'armoury' ? 'border-nrapa-blue bg-nrapa-blue-light dark:bg-nrapa-blue/20' : 'border-zinc-300 dark:border-zinc-600 hover:border-nrapa-blue' }}">
                        <input type="radio" wire:model.live="firearm_source" value="armoury" class="text-nrapa-blue focus:ring-nrapa-blue">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-nrapa-blue dark:text-nrapa-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <div>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Virtual Safe</span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Select from your registered firearms</p>
                            </div>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-3 rounded-lg border-2 transition-colors {{ $firearm_source === 'manual' ? 'border-nrapa-blue bg-nrapa-blue-light dark:bg-nrapa-blue/20' : 'border-zinc-300 dark:border-zinc-600 hover:border-nrapa-blue' }}">
                        <input type="radio" wire:model.live="firearm_source" value="manual" class="text-nrapa-blue focus:ring-nrapa-blue">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <div>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Enter Manually</span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Type firearm details manually</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            @if($firearm_source === 'armoury')
                @if($this->userFirearms->count() > 0)
                    <!-- Select from Virtual Safe -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="user_firearm_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Firearm from Virtual Safe <span class="text-red-500">*</span></label>
                            <select id="user_firearm_id" wire:model.live="user_firearm_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                                <option value="">Select a Firearm from Your Virtual Safe</option>
                                @foreach($this->userFirearms as $firearm)
                                    <option value="{{ $firearm->id }}">
                                        {{ $firearm->display_name }} 
                                        @if($firearm->calibre_display) ({{ $firearm->calibre_display }}) @endif
                                        @if($firearm->nickname) - {{ $firearm->nickname }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_firearm_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <div class="mt-2 flex items-center gap-2">
                                <a href="{{ route('armoury.index') }}" wire:navigate class="text-sm text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue dark:hover:text-nrapa-blue">
                                    View Virtual Safe
                                </a>
                                <span class="text-zinc-400">•</span>
                                <a href="{{ route('armoury.create') }}" wire:navigate class="text-sm text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue dark:hover:text-nrapa-blue">
                                    + Add New Firearm
                                </a>
                            </div>
                        </div>

                        @if(count($loadDataOptions) > 0)
                            <div>
                                <label for="load_data_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Load Data (Optional)</label>
                                <select id="load_data_id" wire:model="load_data_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                                    <option value="">No specific load</option>
                                    @foreach($loadDataOptions as $load)
                                        <option value="{{ $load['id'] }}">{{ $load['name'] }} - {{ $load['description'] }}</option>
                                    @endforeach
                                </select>
                                @error('load_data_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                @if($user_firearm_id)
                    @php $selectedFirearm = $this->userFirearms->firstWhere('id', $user_firearm_id); @endphp
                    @if($selectedFirearm)
                        <div class="mt-4 rounded-lg bg-nrapa-blue-light dark:bg-nrapa-blue/20 border border-nrapa-blue/20 dark:border-nrapa-blue/30 p-4">
                            <p class="text-sm font-medium text-nrapa-blue dark:text-nrapa-blue">Selected Firearm Details</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-nrapa-blue/80 dark:text-nrapa-blue/80">
                                <div><span class="font-medium">Make/Model:</span> {{ $selectedFirearm->make }} {{ $selectedFirearm->model }}</div>
                                <div><span class="font-medium">Type:</span> {{ $selectedFirearm->firearmType?->name ?? 'N/A' }}</div>
                                <div><span class="font-medium">Calibre:</span> {{ $selectedFirearm->calibre_display ?? 'N/A' }}</div>
                                @if($selectedFirearm->serial_number)
                                    <div><span class="font-medium">S/N:</span> {{ $selectedFirearm->serial_number }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
                @else
                    <!-- No Firearms in Virtual Safe -->
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">No Firearms in Your Virtual Safe</h3>
                                <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">
                                    Add firearms to your Virtual Safe to quickly select them when submitting activities. This helps track which firearms you use for each activity.
                                </p>
                                <div class="flex gap-3">
                                    <a href="{{ route('armoury.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-nrapa-blue hover:bg-nrapa-blue-dark text-white text-sm font-medium rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Firearm to Virtual Safe
                                    </a>
                                    <button type="button" wire:click="$set('firearm_source', 'manual')" class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                        Enter Manually Instead
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Manual Entry -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Firearm Type -->
                    <div>
                        <label for="firearm_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type of Firearm Used <span class="text-red-500">*</span></label>
                        <select id="firearm_type_id" wire:model="firearm_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                            <option value="">Select a Firearm Type</option>
                            @foreach($this->firearmTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('firearm_type_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Calibre -->
                    <div class="relative" x-data="{ open: @entangle('showCalibreDropdown') }" @click.away="open = false">
                        <label for="calibre_search" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre / Bore <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="text"
                                id="calibre_search"
                                wire:model.live.debounce.250ms="calibreSearch"
                                x-on:focus="open = true"
                                placeholder="Type to search calibre (e.g., 6.5 Creedmoor, .308 Win, 9mm)..."
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue"
                                autocomplete="off"
                            />
                            @if($calibre_id)
                                <button 
                                    type="button"
                                    wire:click="$set('calibre_id', null); $set('calibreSearch', '')"
                                    class="absolute right-2 top-2 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                            
                            @if($showCalibreDropdown && $this->filteredCalibres->count() > 0)
                                <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($this->filteredCalibres as $calibre)
                                        <button
                                            type="button"
                                            wire:click="selectCalibre({{ $calibre->id }})"
                                            x-on:click="open = false"
                                            class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white flex items-center justify-between"
                                        >
                                            <span>{{ $calibre->name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">{{ $calibre->category }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif($showCalibreDropdown && strlen($calibreSearch) >= 1 && $this->filteredCalibres->count() === 0)
                                <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg shadow-lg p-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    No calibres found matching "{{ $calibreSearch }}"
                                </div>
                            @endif
                        </div>
                        @if($calibre_id)
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Selected: {{ FirearmCalibre::find($calibre_id)?->name ?? '' }}
                            </p>
                        @endif
                        @error('calibre_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif
        </div>

        <!-- Description -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Additional Information</h2>

            <div>
                <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description (Optional)</label>
                <textarea id="description" wire:model="description" rows="4" placeholder="Add any additional details about your activity..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue"></textarea>
                @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Supporting Documents -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Supporting Documents</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Proof of Activity -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Proof of Activity <span class="text-red-500">*</span></label>
                    <div 
                        x-data="{ 
                            dragging: false,
                            handleDrop(e) {
                                this.dragging = false;
                                const files = e.dataTransfer.files;
                                if (files.length > 0) {
                                    const input = this.$refs.proofDocumentInput;
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(files[0]);
                                    input.files = dataTransfer.files;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                        }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="handleDrop($event)"
                        :class="{ 'border-nrapa-blue bg-nrapa-blue-light dark:bg-nrapa-blue/20': dragging }"
                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-nrapa-blue transition-colors cursor-pointer"
                        x-on:click="$refs.proofDocumentInput.click()"
                    >
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <span class="font-medium text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue">Click to Upload</span>
                                <span class="pl-1">or drag and drop</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: PNG, JPG, JPEG and PDF (max 10MB)</p>
                            @if($proof_document)
                                <p class="text-sm text-nrapa-blue dark:text-nrapa-blue mt-2">{{ $proof_document->getClientOriginalName() }}</p>
                            @endif
                            <div wire:loading wire:target="proof_document" class="mt-2">
                                <div class="flex items-center justify-center gap-2 text-nrapa-blue dark:text-nrapa-blue">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm">Uploading...</span>
                                </div>
                            </div>
                        </div>
                        <input x-ref="proofDocumentInput" id="proof_document" wire:model="proof_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    @error('proof_document') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Additional Document -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Additional Supporting Document (Optional)</label>
                    <div 
                        x-data="{ 
                            dragging: false,
                            handleDrop(e) {
                                this.dragging = false;
                                const files = e.dataTransfer.files;
                                if (files.length > 0) {
                                    const input = this.$refs.additionalDocumentInput;
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(files[0]);
                                    input.files = dataTransfer.files;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                        }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="handleDrop($event)"
                        :class="{ 'border-nrapa-blue bg-nrapa-blue-light dark:bg-nrapa-blue/20': dragging }"
                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-nrapa-blue transition-colors cursor-pointer"
                        x-on:click="$refs.additionalDocumentInput.click()"
                    >
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <span class="font-medium text-nrapa-blue hover:text-nrapa-blue-dark dark:text-nrapa-blue">Click to Upload</span>
                                <span class="pl-1">or drag and drop</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: PNG, JPG, JPEG and PDF (max 10MB)</p>
                            @if($additional_document)
                                <p class="text-sm text-nrapa-blue dark:text-nrapa-blue mt-2">{{ $additional_document->getClientOriginalName() }}</p>
                            @endif
                            <div wire:loading wire:target="additional_document" class="mt-2">
                                <div class="flex items-center justify-center gap-2 text-nrapa-blue dark:text-nrapa-blue">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm">Uploading...</span>
                                </div>
                            </div>
                        </div>
                        <input x-ref="additionalDocumentInput" id="additional_document" wire:model="additional_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    @error('additional_document') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('activities.index') }}" wire:navigate class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-6 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                <span wire:loading.remove>Submit Activity</span>
                <span wire:loading>Submitting...</span>
            </button>
        </div>
    </form>
</div>
