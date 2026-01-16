<?php

use App\Models\ActivityType;
use App\Models\Calibre;
use App\Models\Country;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\FirearmType;
use App\Models\Province;
use App\Models\ShootingActivity;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ShootingActivity $activity;

    // Form fields
    public ?int $activity_type_id = null;
    public ?int $event_category_id = null;
    public ?int $event_type_id = null;
    public ?string $activity_date = null;
    public ?int $firearm_type_id = null;
    public ?int $calibre_id = null;
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

    protected function rules(): array
    {
        return [
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'event_type_id' => ['nullable', 'exists:event_types,id'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'firearm_type_id' => ['required', 'exists:firearm_types,id'],
            'calibre_id' => ['required', 'exists:calibres,id'],
            'location' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'closest_town_city' => ['required', 'string', 'max:255'],
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
        $this->activity_type_id = $activity->activity_type_id;
        $this->event_category_id = $activity->event_category_id;
        $this->event_type_id = $activity->event_type_id;
        $this->activity_date = $activity->activity_date?->format('Y-m-d');
        $this->firearm_type_id = $activity->firearm_type_id;
        $this->calibre_id = $activity->calibre_id;
        $this->location = $activity->location;
        $this->country_id = $activity->country_id;
        $this->province_id = $activity->province_id;
        $this->closest_town_city = $activity->closest_town_city;
        $this->description = $activity->description;

        // Load dependent dropdowns
        if ($this->activity_type_id) {
            $this->eventCategories = EventCategory::active()
                ->where('activity_type_id', $this->activity_type_id)
                ->ordered()
                ->get()
                ->toArray();
        }

        if ($this->event_category_id) {
            $this->eventTypes = EventType::active()
                ->where('event_category_id', $this->event_category_id)
                ->ordered()
                ->get()
                ->toArray();
        }
    }

    public function updatedActivityTypeId($value): void
    {
        $this->event_category_id = null;
        $this->event_type_id = null;
        $this->eventTypes = [];

        if ($value) {
            $this->eventCategories = EventCategory::active()
                ->where('activity_type_id', $value)
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

    public function update(): void
    {
        $this->validate();

        $this->activity->update([
            'activity_type_id' => $this->activity_type_id,
            'event_category_id' => $this->event_category_id,
            'event_type_id' => $this->event_type_id,
            'activity_date' => $this->activity_date,
            'firearm_type_id' => $this->firearm_type_id,
            'calibre_id' => $this->calibre_id,
            'location' => $this->location,
            'country_id' => $this->country_id,
            'province_id' => $this->province_id,
            'closest_town_city' => $this->closest_town_city,
            'description' => $this->description,
        ]);

        // Handle file uploads if new files provided
        if ($this->proof_document) {
            $proofPath = $this->proof_document->store('activities/' . auth()->id() . '/proof', 'private');
        }

        if ($this->additional_document) {
            $additionalPath = $this->additional_document->store('activities/' . auth()->id() . '/additional', 'private');
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

    public function with(): array
    {
        $user = auth()->user();
        $dedicatedType = $user->activeMembership?->dedicated_type ?? null;

        return [
            'activityTypes' => ActivityType::active()
                ->when($dedicatedType, fn($q) => $q->forDedicatedType($dedicatedType))
                ->ordered()
                ->get(),
            'firearmTypes' => FirearmType::active()
                ->when($dedicatedType, fn($q) => $q->forDedicatedType($dedicatedType))
                ->ordered()
                ->get(),
            'calibres' => Calibre::active()->ordered()->get(),
            'countries' => Country::active()->ordered()->get(),
            'provinces' => Province::active()->ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-8">
        <a href="{{ route('activities.show', $activity) }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400 hover:text-emerald-600">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Activity
        </a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">Edit Activity</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Update your activity details</p>
    </div>

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
                <!-- Related Activity -->
                <div>
                    <label for="activity_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Related Activity <span class="text-red-500">*</span></label>
                    <select id="activity_type_id" wire:model.live="activity_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select Related Activity</option>
                        @foreach($activityTypes as $type)
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

                <!-- Event Type -->
                <div>
                    <label for="event_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Event Type</label>
                    <select id="event_type_id" wire:model="event_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" @disabled(empty($eventTypes))>
                        <option value="">Select Event Type</option>
                        @foreach($eventTypes as $eventType)
                            <option value="{{ $eventType['id'] }}">{{ $eventType['name'] }}</option>
                        @endforeach
                    </select>
                    @error('event_type_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
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
                        @foreach($countries as $country)
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
                        @foreach($provinces as $province)
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

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Firearm Type -->
                <div>
                    <label for="firearm_type_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type of Firearm Used <span class="text-red-500">*</span></label>
                    <select id="firearm_type_id" wire:model="firearm_type_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
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
                    <select id="calibre_id" wire:model="calibre_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
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
                <textarea id="description" wire:model="description" rows="4" placeholder="Add any additional details about your activity..." class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Upload Additional Supporting Document</label>
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

        <!-- Buttons -->
        <div class="flex items-center justify-between">
            <button type="button" wire:click="delete" wire:confirm="Are you sure you want to delete this activity?" class="rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                Delete Activity
            </button>

            <div class="flex items-center gap-4">
                <a href="{{ route('activities.show', $activity) }}" wire:navigate class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-6 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                    <span wire:loading.remove>Update Activity</span>
                    <span wire:loading>Updating...</span>
                </button>
            </div>
        </div>
    </form>
</div>
