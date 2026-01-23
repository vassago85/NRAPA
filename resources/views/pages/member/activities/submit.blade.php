<?php

use App\Models\ActivityType;
use App\Models\Calibre;
use App\Models\Country;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\FirearmType;
use App\Models\LoadData;
use App\Models\Province;
use App\Models\ShootingActivity;
use App\Models\UserFirearm;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // Form fields
    public ?int $activity_type_id = null;
    public ?int $event_category_id = null;
    public ?int $event_type_id = null;
    public ?string $activity_date = null;
    
    // Firearm selection - from armoury or manual
    public string $firearm_source = 'armoury'; // 'armoury' or 'manual'
    public ?int $user_firearm_id = null;
    public ?int $load_data_id = null;
    public ?int $firearm_type_id = null;
    public ?int $calibre_id = null;
    
    // Location
    public ?string $location = null;
    public ?int $country_id = null;
    public ?int $province_id = null;
    public ?string $closest_town_city = null;
    public ?string $description = null;
    public $proof_document = null;
    public $additional_document = null;

    // Dynamic options
    public array $eventCategories = [];
    public array $eventTypes = [];
    public array $loadDataOptions = [];

    protected function rules(): array
    {
        $rules = [
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'event_type_id' => ['nullable', 'exists:event_types,id'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'firearm_source' => ['required', 'in:armoury,manual'],
            'location' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'closest_town_city' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'proof_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'additional_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];

        if ($this->firearm_source === 'armoury') {
            $rules['user_firearm_id'] = ['required', 'exists:user_firearms,id'];
            $rules['load_data_id'] = ['nullable', 'exists:load_data,id'];
        } else {
            $rules['firearm_type_id'] = ['required', 'exists:firearm_types,id'];
            $rules['calibre_id'] = ['required', 'exists:calibres,id'];
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

        // Set default country to South Africa
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

    public function updatedActivityTypeId($value): void
    {
        $this->event_category_id = null;
        $this->event_type_id = null;
        $this->eventTypes = [];

        if ($value) {
            // Get categories for this specific activity type OR categories that apply to both types
            $this->eventCategories = EventCategory::active()
                ->where(function ($query) use ($value) {
                    $query->where('activity_type_id', $value)
                          ->orWhere(function ($q) {
                              $q->whereNull('activity_type_id')
                                ->where('dedicated_type', 'both');
                          });
                })
                ->ordered()
                ->get()
                ->toArray();
        } else {
            $this->eventCategories = [];
        }
    }

    public function updatedEventCategoryId($value): void
    {
        $this->event_type_id = null;

        if ($value) {
            $this->eventTypes = EventType::active()
                ->where('event_category_id', $value)
                ->ordered()
                ->get()
                ->toArray();
        } else {
            $this->eventTypes = [];
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
            $calibreId = $userFirearm->calibre_id;
            $userFirearmId = $userFirearm->id;
            $loadDataId = $this->load_data_id;
        } else {
            $firearmTypeId = $this->firearm_type_id;
            $calibreId = $this->calibre_id;
        }

        // Create the activity
        $activity = ShootingActivity::create([
            'user_id' => auth()->id(),
            'activity_type_id' => $this->activity_type_id,
            'event_category_id' => $this->event_category_id,
            'event_type_id' => $this->event_type_id,
            'activity_date' => $this->activity_date,
            'firearm_type_id' => $firearmTypeId,
            'calibre_id' => $calibreId,
            'user_firearm_id' => $userFirearmId,
            'load_data_id' => $loadDataId,
            'location' => $this->location,
            'country_id' => $this->country_id,
            'province_id' => $this->province_id,
            'closest_town_city' => $this->closest_town_city,
            'description' => $this->description,
            'activity_year_month_start' => 1, // Fixed: January (activity period is 1 Jan - 30 Sep)
            'status' => 'pending',
        ]);

        // Handle file uploads (store paths temporarily - document management to be implemented)
        if ($this->proof_document) {
            $proofPath = $this->proof_document->store('activities/' . auth()->id() . '/proof', 'private');
            // For now, store directly in the description or add to notes
            // Full document management will be implemented later
        }

        if ($this->additional_document) {
            $additionalPath = $this->additional_document->store('activities/' . auth()->id() . '/additional', 'private');
        }

        session()->flash('success', 'Activity submitted successfully and is pending review.');
        $this->redirect(route('activities.index'));
    }

    protected function getDedicatedType(): ?string
    {
        $user = auth()->user();
        $membershipType = $user->activeMembership?->type;
        
        // Get dedicated_type from membership TYPE
        // If membership allows_dedicated_status but has no specific dedicated_type,
        // treat as 'both' so they can see all options
        $dedicatedType = $membershipType?->dedicated_type;
        if (!$dedicatedType && $membershipType?->allows_dedicated_status) {
            $dedicatedType = 'both';
        }
        
        return $dedicatedType;
    }

    #[Computed]
    public function activityTypes()
    {
        return ActivityType::active()
            ->forDedicatedType($this->getDedicatedType())
            ->ordered()
            ->get();
    }

    #[Computed]
    public function firearmTypes()
    {
        return FirearmType::active()
            ->forDedicatedType($this->getDedicatedType())
            ->ordered()
            ->get();
    }

    #[Computed]
    public function calibres()
    {
        return Calibre::active()->ordered()->get();
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
            ->with(['firearmType', 'calibre'])
            ->get();
    }
}; ?>

<div>
    <div class="mb-8">
        <a href="{{ route('activities.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400 hover:text-emerald-600">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Activities
        </a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">Submit Activity</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Add a hunting or sport-shooting related activity for your membership qualifications</p>
    </div>

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
                <!-- Related Activity -->
                <div>
                    <label for="activity_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Related Activity <span class="text-red-500">*</span></label>
                    <select id="activity_type_id" wire:model.live="activity_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select Related Activity</option>
                        @foreach($this->activityTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('activity_type_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Event Category -->
                <div>
                    <label for="event_category_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type of Activity <span class="text-red-500">*</span></label>
                    <select id="event_category_id" wire:model.live="event_category_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" @disabled(empty($eventCategories))>
                        <option value="">Select Event Category</option>
                        @foreach($eventCategories as $category)
                            <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    @error('event_category_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Activity Date -->
                <div>
                    <label for="activity_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date of Activity <span class="text-red-500">*</span></label>
                    <input type="date" id="activity_date" wire:model="activity_date" max="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                    @error('activity_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
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
                    <input type="text" id="location" wire:model="location" placeholder="e.g., Magaliesberg Shooting Range" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('location') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Country -->
                <div>
                    <label for="country_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country <span class="text-red-500">*</span></label>
                    <select id="country_id" wire:model="country_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select Country</option>
                        @foreach($this->countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Province -->
                <div>
                    <label for="province_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Province</label>
                    <select id="province_id" wire:model="province_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select Province</option>
                        @foreach($this->provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('province_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Closest Town/City -->
                <div>
                    <label for="closest_town_city" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Closest Town/City <span class="text-red-500">*</span></label>
                    <input type="text" id="closest_town_city" wire:model="closest_town_city" placeholder="e.g., Pretoria" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('closest_town_city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Firearm/Calibre -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Firearm / Calibre</h2>

            @if($this->userFirearms->count() > 0)
                <!-- Source Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Select Firearm From</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="firearm_source" value="armoury" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">My Armoury</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="firearm_source" value="manual" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Enter Manually</span>
                        </label>
                    </div>
                </div>
            @endif

            @if($firearm_source === 'armoury' && $this->userFirearms->count() > 0)
                <!-- Select from Armoury -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="user_firearm_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Firearm <span class="text-red-500">*</span></label>
                        <select id="user_firearm_id" wire:model.live="user_firearm_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Select a Firearm</option>
                            @foreach($this->userFirearms as $firearm)
                                <option value="{{ $firearm->id }}">
                                    {{ $firearm->display_name }} 
                                    @if($firearm->calibre) ({{ $firearm->calibre->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('user_firearm_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        <a href="{{ route('armoury.create') }}" wire:navigate class="mt-1 inline-block text-sm text-emerald-600 hover:text-emerald-700">
                            + Add new firearm to armoury
                        </a>
                    </div>

                    @if(count($loadDataOptions) > 0)
                        <div>
                            <label for="load_data_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Load Data (Optional)</label>
                            <select id="load_data_id" wire:model="load_data_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
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
                        <div class="mt-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4">
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">Selected Firearm Details</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-emerald-700 dark:text-emerald-300">
                                <div><span class="font-medium">Make/Model:</span> {{ $selectedFirearm->make }} {{ $selectedFirearm->model }}</div>
                                <div><span class="font-medium">Type:</span> {{ $selectedFirearm->firearmType?->name ?? 'N/A' }}</div>
                                <div><span class="font-medium">Calibre:</span> {{ $selectedFirearm->calibre?->name ?? 'N/A' }}</div>
                                @if($selectedFirearm->serial_number)
                                    <div><span class="font-medium">S/N:</span> {{ $selectedFirearm->serial_number }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            @else
                <!-- Manual Entry -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Firearm Type -->
                    <div>
                        <label for="firearm_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type of Firearm Used <span class="text-red-500">*</span></label>
                        <select id="firearm_type_id" wire:model="firearm_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Select a Firearm Type</option>
                            @foreach($this->firearmTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('firearm_type_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Calibre -->
                    <div>
                        <label for="calibre_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre / Bore <span class="text-red-500">*</span></label>
                        <select id="calibre_id" wire:model="calibre_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Search/Select a Calibre</option>
                            <optgroup label="Rifle">
                                @foreach($this->calibres->where('category', 'rifle') as $calibre)
                                    <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Handgun">
                                @foreach($this->calibres->where('category', 'handgun') as $calibre)
                                    <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Shotgun">
                                @foreach($this->calibres->where('category', 'shotgun') as $calibre)
                                    <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Other">
                                @foreach($this->calibres->where('category', 'other') as $calibre)
                                    <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
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
                <textarea id="description" wire:model="description" rows="4" placeholder="Add any additional details about your activity..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
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
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-emerald-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <label for="proof_document" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500 focus-within:outline-none">
                                    <span>Click to Upload or Drag n Drop</span>
                                    <input id="proof_document" wire:model="proof_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                </label>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: png, jpg, jpeg and PDF</p>
                            @if($proof_document)
                                <p class="text-sm text-emerald-600">{{ $proof_document->getClientOriginalName() }}</p>
                            @endif
                        </div>
                    </div>
                    @error('proof_document') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Additional Document -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Additional Supporting Document (Optional)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-emerald-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <label for="additional_document" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500 focus-within:outline-none">
                                    <span>Click to Upload or Drag n Drop</span>
                                    <input id="additional_document" wire:model="additional_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                </label>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: png, jpg, jpeg and PDF</p>
                            @if($additional_document)
                                <p class="text-sm text-emerald-600">{{ $additional_document->getClientOriginalName() }}</p>
                            @endif
                        </div>
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
            <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                <span wire:loading.remove>Submit Activity</span>
                <span wire:loading>Submitting...</span>
            </button>
        </div>
    </form>
</div>
