<?php

use App\Models\ActivityType;
use App\Models\ActivityTag;
use App\Models\FirearmCalibre;
use App\Models\Country;
use App\Models\FirearmType;
use App\Models\MemberDocument;
use App\Models\DocumentType;
use App\Models\Province;
use App\Models\ShootingActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ShootingActivity $activity;

    // Form fields
    public ?string $track = null;
    public ?int $activity_type_id = null;
    public array $activity_tag_ids = [];
    public ?string $activity_date = null;
    public ?int $firearm_type_id = null;
    public ?int $calibre_id = null;
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


    protected function rules(): array
    {
        return [
            'track' => ['required', 'in:hunting,sport'],
            'activity_type_id' => ['required', 'exists:activity_types,id'], // Auto-set based on track
            'activity_tag_id' => ['required', 'exists:activity_tags,id'],
            'activity_tag_ids' => ['nullable', 'array'],
            'activity_tag_ids.*' => ['exists:activity_tags,id'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'firearm_type_id' => ['required', 'exists:firearm_types,id'],
            'calibre_id' => ['required', 'exists:firearm_calibres,id'],
            'location' => ['required', 'string', 'max:255'],
            'country_selection' => ['required', 'in:south_africa,other'],
            'country_id' => ['required_if:country_selection,south_africa', 'nullable', 'exists:countries,id'],
            'country_name' => ['required_if:country_selection,other', 'nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'province_name' => ['nullable', 'string', 'max:255'],
            'closest_town_city' => ['required', 'string', 'max:255'],
            'rounds_fired' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'proof_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'additional_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function mount(ShootingActivity $activity): void
    {
        // Ensure user owns this activity and it's still pending
        if ($activity->user_id !== auth()->id()) {
            abort(403);
        }

        if ($activity->status !== 'pending') {
            session()->flash('error', 'You can only edit pending activities.');
            $this->redirect(route('activities.show', $activity));
            return;
        }

        $this->activity = $activity;

        // Populate form fields
        $this->track = $activity->track ?? $activity->activityType?->track;
        // Auto-set activity_type_id based on track (Dedicated Hunting or Dedicated Sport-Shooting)
        if ($this->track) {
            $activityType = ActivityType::where('track', $this->track)
                ->whereIn('slug', ['dedicated-hunting', 'dedicated-sport-shooting'])
                ->first();
            $this->activity_type_id = $activityType?->id ?? $activity->activity_type_id;
        } else {
            $this->activity_type_id = $activity->activity_type_id;
        }
        $this->activity_tag_id = $activity->tags->first()?->id;
        $this->activity_date = $activity->activity_date?->format('Y-m-d');
        $this->firearm_type_id = $activity->firearm_type_id;
        $this->calibre_id = $activity->calibre_id;
        $this->location = $activity->location;
        // Determine country selection based on whether country_id exists
        if ($activity->country_id) {
            $this->country_selection = 'south_africa';
            $this->country_id = $activity->country_id;
        } else {
            $this->country_selection = 'other';
            $this->country_name = $activity->country_name;
        }
        $this->province_id = $activity->province_id;
        $this->province_name = $activity->province_name;
        $this->closest_town_city = $activity->closest_town_city;
        $this->description = $activity->description;
        $this->rounds_fired = $activity->rounds_fired;
    }

    public function updatedTrack(): void
    {
        // Auto-set activity type based on track (only 2 types: Dedicated Hunting or Dedicated Sport-Shooting)
        if ($this->track) {
            $activityType = ActivityType::where('track', $this->track)
                ->whereIn('slug', ['dedicated-hunting', 'dedicated-sport-shooting'])
                ->first();
            $this->activity_type_id = $activityType?->id;
        } else {
            $this->activity_type_id = null;
        }
        $this->activity_tag_id = null;
    }


    public function update(): void
    {
        $this->validate();

        $this->activity->update([
            'track' => $this->track,
            'activity_type_id' => $this->activity_type_id, // Auto-set based on track
            'activity_date' => $this->activity_date,
            'firearm_type_id' => $this->firearm_type_id,
            'calibre_id' => $this->calibre_id,
            'location' => $this->location,
            'country_id' => $this->country_selection === 'south_africa' ? $this->country_id : null,
            'country_name' => $this->country_selection === 'other' ? $this->country_name : null,
            'province_id' => $this->country_selection === 'south_africa' ? $this->province_id : null,
            'province_name' => $this->country_selection === 'other' ? $this->province_name : null,
            'closest_town_city' => $this->closest_town_city,
            'description' => $this->description,
            'rounds_fired' => $this->rounds_fired,
        ]);

        // Handle file uploads if new files provided and create MemberDocument records
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
        
        $updateData = [];
        
        if ($this->proof_document) {
            // Delete old evidence document if it exists
            if ($this->activity->evidence_document_id) {
                $oldDocument = MemberDocument::find($this->activity->evidence_document_id);
                if ($oldDocument) {
                    // Delete file from storage
                    try {
                        Storage::disk($disk)->delete($oldDocument->file_path);
                    } catch (\Exception $e) {
                        // Log error but continue
                    }
                    // Delete document record
                    $oldDocument->delete();
                }
            }
            
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
                    'activity_id' => $this->activity->id,
                    'activity_date' => $this->activity->activity_date->format('Y-m-d'),
                    'activity_type' => $this->activity->activityType?->name,
                ],
            ]);
            
            $updateData['evidence_document_id'] = $evidenceDocument->id;
        }

        // Sync activity tags
        // Sync the single selected tag
        if ($this->activity_tag_id) {
            $this->activity->tags()->sync([$this->activity_tag_id]);
        } else {
            $this->activity->tags()->sync([]);
        }

        if ($this->additional_document) {
            // Delete old additional document if it exists
            if ($this->activity->additional_document_id) {
                $oldDocument = MemberDocument::find($this->activity->additional_document_id);
                if ($oldDocument) {
                    // Delete file from storage
                    try {
                        Storage::disk($disk)->delete($oldDocument->file_path);
                    } catch (\Exception $e) {
                        // Log error but continue
                    }
                    // Delete document record
                    $oldDocument->delete();
                }
            }
            
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
                    'activity_id' => $this->activity->id,
                    'activity_date' => $this->activity->activity_date->format('Y-m-d'),
                    'activity_type' => $this->activity->activityType?->name,
                    'is_additional' => true,
                ],
            ]);
            
            $updateData['additional_document_id'] = $additionalDocument->id;
        }
        
        // Update activity with document IDs if any were uploaded
        if (!empty($updateData)) {
            $this->activity->update($updateData);
        }

        session()->flash('success', 'Activity updated successfully.');
        $this->redirect(route('activities.show', $this->activity));
    }

    public function delete(): void
    {
        if ($this->activity->status !== 'pending') {
            session()->flash('error', 'You can only delete pending activities.');
            return;
        }

        $this->activity->delete();

        session()->flash('success', 'Activity deleted successfully.');
        $this->redirect(route('activities.index'));
    }

    #[Computed]
    public function activityTypes()
    {
        if (!$this->track) {
            return collect();
        }

        return ActivityType::active()
            ->forTrack($this->track)
            ->ordered()
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

    public function with(): array
    {
        return [
            'firearmTypes' => FirearmType::active()->usableForActivities()->ordered()->get(),
            'calibres' => FirearmCalibre::active()->ordered()->get(),
            'countries' => Country::active()->ordered()->get(),
            'provinces' => Province::active()->ordered()->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('activities.show', $activity) }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Edit Activity</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Update your activity details</p>
            </div>
        </div>
    </x-slot>

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="update" class="space-y-8">
        <!-- Activity Information -->
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-6">Activity Information</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Track -->
                <div>
                    <label for="track" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Track <span class="text-red-500">*</span></label>
                    <select id="track" wire:model.live="track" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                        <option value="">Select Track</option>
                        <option value="hunting">Hunting</option>
                        <option value="sport">Sport Shooting</option>
                    </select>
                    @error('track') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Activity Type (auto-set based on track, hidden from user) -->
                <input type="hidden" wire:model="activity_type_id">

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

                <!-- Activity Tag (replaces Activity Type - provides details) -->
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
                            @foreach($provinces as $province)
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

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Firearm Type -->
                <div>
                    <label for="firearm_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type of Firearm Used <span class="text-red-500">*</span></label>
                    <select id="firearm_type_id" wire:model="firearm_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                        <option value="">Select a Firearm Type</option>
                        @foreach($firearmTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('firearm_type_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Calibre -->
                <div>
                    <label for="calibre_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Calibre / Bore <span class="text-red-500">*</span></label>
                    <select id="calibre_id" wire:model="calibre_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-nrapa-blue focus:ring-nrapa-blue">
                        <option value="">Search/Select a Calibre</option>
                        <optgroup label="Rifle">
                            @foreach($calibres->where('category', 'rifle') as $calibre)
                                <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Handgun">
                            @foreach($calibres->where('category', 'handgun') as $calibre)
                                <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Shotgun">
                            @foreach($calibres->where('category', 'shotgun') as $calibre)
                                <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Other">
                            @foreach($calibres->where('category', 'other') as $calibre)
                                <option value="{{ $calibre->id }}">{{ $calibre->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('calibre_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
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
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">Upload new documents to replace the existing ones (optional)</p>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Proof of Activity -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Proof of Activity</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-nrapa-blue transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <label for="proof_document" class="relative cursor-pointer rounded-md font-medium text-nrapa-blue hover:text-nrapa-blue-dark focus-within:outline-none">
                                    <span>Click to Upload or Drag n Drop</span>
                                    <input id="proof_document" wire:model="proof_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                </label>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: png, jpg, jpeg and PDF</p>
                            @if($proof_document)
                                <p class="text-sm text-nrapa-blue">{{ $proof_document->getClientOriginalName() }}</p>
                            @endif
                        </div>
                    </div>
                    @error('proof_document') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Additional Document -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Additional Supporting Document</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-lg hover:border-nrapa-blue transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto size-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-zinc-600 dark:text-zinc-400 justify-center">
                                <label for="additional_document" class="relative cursor-pointer rounded-md font-medium text-nrapa-blue hover:text-nrapa-blue-dark focus-within:outline-none">
                                    <span>Click to Upload or Drag n Drop</span>
                                    <input id="additional_document" wire:model="additional_document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                </label>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: png, jpg, jpeg and PDF</p>
                            @if($additional_document)
                                <p class="text-sm text-nrapa-blue">{{ $additional_document->getClientOriginalName() }}</p>
                            @endif
                        </div>
                    </div>
                    @error('additional_document') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-between">
            <button type="button" wire:click="delete" wire:confirm="Are you sure you want to delete this activity?" class="rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                Delete Activity
            </button>

            <div class="flex items-center gap-4">
                <a href="{{ route('activities.show', $activity) }}" wire:navigate class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-6 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2.5 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                    <span wire:loading.remove>Update Activity</span>
                    <span wire:loading>Updating...</span>
                </button>
            </div>
        </div>
    </form>
</div>
