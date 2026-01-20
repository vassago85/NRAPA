<?php

use App\Models\ActivityType;
use App\Models\Calibre;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\FirearmType;
use Livewire\Component;

new class extends Component {
    public string $activeTab = 'activity-types';

    // Activity Type form
    public ?int $editingActivityTypeId = null;
    public string $activityTypeName = '';
    public string $activityTypeDedicatedType = 'both';

    // Event Category form
    public ?int $editingEventCategoryId = null;
    public string $eventCategoryName = '';
    public ?int $eventCategoryActivityTypeId = null;
    public string $eventCategoryDedicatedType = 'both';

    // Event Type form
    public ?int $editingEventTypeId = null;
    public string $eventTypeName = '';
    public ?int $eventTypeEventCategoryId = null;

    // Firearm Type form
    public ?int $editingFirearmTypeId = null;
    public string $firearmTypeName = '';
    public string $firearmTypeDedicatedType = 'both';

    // Calibre form
    public ?int $editingCalibreId = null;
    public string $calibreName = '';
    public string $calibreCategory = 'rifle';

    // === Activity Types ===
    public function saveActivityType(): void
    {
        $this->validate([
            'activityTypeName' => ['required', 'string', 'max:255'],
            'activityTypeDedicatedType' => ['required', 'in:hunter,sport_shooter,both'],
        ]);

        ActivityType::updateOrCreate(
            ['id' => $this->editingActivityTypeId],
            [
                'name' => $this->activityTypeName,
                'slug' => \Illuminate\Support\Str::slug($this->activityTypeName),
                'dedicated_type' => $this->activityTypeDedicatedType,
            ]
        );

        $this->resetActivityTypeForm();
        session()->flash('success', 'Activity type saved successfully.');
    }

    public function editActivityType(ActivityType $activityType): void
    {
        $this->editingActivityTypeId = $activityType->id;
        $this->activityTypeName = $activityType->name;
        $this->activityTypeDedicatedType = $activityType->dedicated_type;
    }

    public function deleteActivityType(ActivityType $activityType): void
    {
        $activityType->delete();
        session()->flash('success', 'Activity type deleted.');
    }

    public function resetActivityTypeForm(): void
    {
        $this->editingActivityTypeId = null;
        $this->activityTypeName = '';
        $this->activityTypeDedicatedType = 'both';
    }

    // === Event Categories ===
    public function saveEventCategory(): void
    {
        $this->validate([
            'eventCategoryName' => ['required', 'string', 'max:255'],
            'eventCategoryActivityTypeId' => ['nullable', 'exists:activity_types,id'],
            'eventCategoryDedicatedType' => ['required', 'in:hunter,sport_shooter,both'],
        ]);

        EventCategory::updateOrCreate(
            ['id' => $this->editingEventCategoryId],
            [
                'name' => strtoupper($this->eventCategoryName),
                'slug' => \Illuminate\Support\Str::slug($this->eventCategoryName),
                'activity_type_id' => $this->eventCategoryActivityTypeId,
                'dedicated_type' => $this->eventCategoryDedicatedType,
            ]
        );

        $this->resetEventCategoryForm();
        session()->flash('success', 'Event category saved successfully.');
    }

    public function editEventCategory(EventCategory $eventCategory): void
    {
        $this->editingEventCategoryId = $eventCategory->id;
        $this->eventCategoryName = $eventCategory->name;
        $this->eventCategoryActivityTypeId = $eventCategory->activity_type_id;
        $this->eventCategoryDedicatedType = $eventCategory->dedicated_type ?? 'both';
    }

    public function deleteEventCategory(EventCategory $eventCategory): void
    {
        $eventCategory->delete();
        session()->flash('success', 'Event category deleted.');
    }

    public function resetEventCategoryForm(): void
    {
        $this->editingEventCategoryId = null;
        $this->eventCategoryName = '';
        $this->eventCategoryActivityTypeId = null;
        $this->eventCategoryDedicatedType = 'both';
    }

    // === Event Types ===
    public function saveEventType(): void
    {
        $this->validate([
            'eventTypeName' => ['required', 'string', 'max:255'],
            'eventTypeEventCategoryId' => ['nullable', 'exists:event_categories,id'],
        ]);

        EventType::updateOrCreate(
            ['id' => $this->editingEventTypeId],
            [
                'name' => strtoupper($this->eventTypeName),
                'slug' => \Illuminate\Support\Str::slug($this->eventTypeName),
                'event_category_id' => $this->eventTypeEventCategoryId,
            ]
        );

        $this->resetEventTypeForm();
        session()->flash('success', 'Event type saved successfully.');
    }

    public function editEventType(EventType $eventType): void
    {
        $this->editingEventTypeId = $eventType->id;
        $this->eventTypeName = $eventType->name;
        $this->eventTypeEventCategoryId = $eventType->event_category_id;
    }

    public function deleteEventType(EventType $eventType): void
    {
        $eventType->delete();
        session()->flash('success', 'Event type deleted.');
    }

    public function resetEventTypeForm(): void
    {
        $this->editingEventTypeId = null;
        $this->eventTypeName = '';
        $this->eventTypeEventCategoryId = null;
    }

    // === Firearm Types ===
    public function saveFirearmType(): void
    {
        $this->validate([
            'firearmTypeName' => ['required', 'string', 'max:255'],
            'firearmTypeDedicatedType' => ['required', 'in:hunter,sport_shooter,both'],
        ]);

        FirearmType::updateOrCreate(
            ['id' => $this->editingFirearmTypeId],
            [
                'name' => strtoupper($this->firearmTypeName),
                'slug' => \Illuminate\Support\Str::slug($this->firearmTypeName),
                'dedicated_type' => $this->firearmTypeDedicatedType,
            ]
        );

        $this->resetFirearmTypeForm();
        session()->flash('success', 'Firearm type saved successfully.');
    }

    public function editFirearmType(FirearmType $firearmType): void
    {
        $this->editingFirearmTypeId = $firearmType->id;
        $this->firearmTypeName = $firearmType->name;
        $this->firearmTypeDedicatedType = $firearmType->dedicated_type;
    }

    public function deleteFirearmType(FirearmType $firearmType): void
    {
        $firearmType->delete();
        session()->flash('success', 'Firearm type deleted.');
    }

    public function resetFirearmTypeForm(): void
    {
        $this->editingFirearmTypeId = null;
        $this->firearmTypeName = '';
        $this->firearmTypeDedicatedType = 'both';
    }

    // === Calibres ===
    public function saveCalibre(): void
    {
        $this->validate([
            'calibreName' => ['required', 'string', 'max:255'],
            'calibreCategory' => ['required', 'in:rifle,handgun,shotgun,other'],
        ]);

        Calibre::updateOrCreate(
            ['id' => $this->editingCalibreId],
            [
                'name' => $this->calibreName,
                'slug' => \Illuminate\Support\Str::slug($this->calibreName),
                'category' => $this->calibreCategory,
            ]
        );

        $this->resetCalibreForm();
        session()->flash('success', 'Calibre saved successfully.');
    }

    public function editCalibre(Calibre $calibre): void
    {
        $this->editingCalibreId = $calibre->id;
        $this->calibreName = $calibre->name;
        $this->calibreCategory = $calibre->category;
    }

    public function deleteCalibre(Calibre $calibre): void
    {
        $calibre->delete();
        session()->flash('success', 'Calibre deleted.');
    }

    public function resetCalibreForm(): void
    {
        $this->editingCalibreId = null;
        $this->calibreName = '';
        $this->calibreCategory = 'rifle';
    }

    public function with(): array
    {
        return [
            'activityTypes' => ActivityType::ordered()->get(),
            'eventCategories' => EventCategory::with('activityType')->ordered()->get(),
            'eventTypes' => EventType::with('eventCategory')->ordered()->get(),
            'firearmTypes' => FirearmType::ordered()->get(),
            'calibres' => Calibre::ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activity Configuration</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Configure activity types, activity categories, firearm types, and calibres</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4">
            <button wire:click="$set('activeTab', 'activity-types')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'activity-types' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Activity Types
            </button>
            <button wire:click="$set('activeTab', 'event-categories')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'event-categories' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Activity Categories
            </button>
            <button wire:click="$set('activeTab', 'event-types')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'event-types' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Activity Sub-Types
            </button>
            <button wire:click="$set('activeTab', 'firearm-types')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'firearm-types' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Firearm Types
            </button>
            <button wire:click="$set('activeTab', 'calibres')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'calibres' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}">
                Calibres
            </button>
        </nav>
    </div>

    <!-- Activity Types Tab -->
    @if($activeTab === 'activity-types')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Form -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingActivityTypeId ? 'Edit' : 'Add' }} Activity Type</h3>
                <form wire:submit="saveActivityType" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                        <input type="text" wire:model="activityTypeName" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('activityTypeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type</label>
                        <select wire:model="activityTypeDedicatedType" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="hunter">Hunter Only</option>
                            <option value="sport_shooter">Sport Shooter Only</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingActivityTypeId)
                            <button type="button" wire:click="resetActivityTypeForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Dedicated Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($activityTypes as $type)
                            <tr>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $type->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $type->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800' : ($type->dedicated_type === 'sport_shooter' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $type->dedicated_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="editActivityType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                    <button wire:click="deleteActivityType({{ $type->id }})" wire:confirm="Are you sure?" class="ml-4 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Activity Categories Tab -->
    @if($activeTab === 'event-categories')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingEventCategoryId ? 'Edit' : 'Add' }} Activity Category</h3>
                <form wire:submit="saveEventCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                        <input type="text" wire:model="eventCategoryName" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type</label>
                        <select wire:model="eventCategoryDedicatedType" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="hunter">Hunter Only</option>
                            <option value="sport_shooter">Sport Shooter Only</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Parent Activity Type (Optional)</label>
                        <select wire:model="eventCategoryActivityTypeId" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">No Parent (Applies to All)</option>
                            @foreach($activityTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingEventCategoryId)
                            <button type="button" wire:click="resetEventCategoryForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Dedicated Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Parent</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($eventCategories as $category)
                            <tr>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $category->name }}</td>
                                <td class="px-6 py-4">
                                    @if($category->dedicated_type === 'both')
                                        <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:text-purple-200">Both</span>
                                    @elseif($category->dedicated_type === 'hunter')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-200">Hunter</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">Sport Shooter</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500">{{ $category->activityType?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="editEventCategory({{ $category->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                    <button wire:click="deleteEventCategory({{ $category->id }})" wire:confirm="Are you sure?" class="ml-4 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Activity Sub-Types Tab -->
    @if($activeTab === 'event-types')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingEventTypeId ? 'Edit' : 'Add' }} Activity Sub-Type</h3>
                <form wire:submit="saveEventType" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                        <input type="text" wire:model="eventTypeName" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Activity Category</label>
                        <select wire:model="eventTypeEventCategoryId" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select Category</option>
                            @foreach($eventCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingEventTypeId)
                            <button type="button" wire:click="resetEventTypeForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($eventTypes as $type)
                            <tr>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $type->name }}</td>
                                <td class="px-6 py-4 text-sm text-zinc-500">{{ $type->eventCategory?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="editEventType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                    <button wire:click="deleteEventType({{ $type->id }})" wire:confirm="Are you sure?" class="ml-4 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Firearm Types Tab -->
    @if($activeTab === 'firearm-types')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingFirearmTypeId ? 'Edit' : 'Add' }} Firearm Type</h3>
                <form wire:submit="saveFirearmType" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                        <input type="text" wire:model="firearmTypeName" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Dedicated Type</label>
                        <select wire:model="firearmTypeDedicatedType" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="hunter">Hunter Only</option>
                            <option value="sport_shooter">Sport Shooter Only</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingFirearmTypeId)
                            <button type="button" wire:click="resetFirearmTypeForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Dedicated Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($firearmTypes as $type)
                            <tr>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $type->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $type->dedicated_type === 'hunter' ? 'bg-amber-100 text-amber-800' : ($type->dedicated_type === 'sport_shooter' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $type->dedicated_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="editFirearmType({{ $type->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                    <button wire:click="deleteFirearmType({{ $type->id }})" wire:confirm="Are you sure?" class="ml-4 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Calibres Tab -->
    @if($activeTab === 'calibres')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingCalibreId ? 'Edit' : 'Add' }} Calibre</h3>
                <form wire:submit="saveCalibre" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                        <input type="text" wire:model="calibreName" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                        <select wire:model="calibreCategory" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="rifle">Rifle</option>
                            <option value="handgun">Handgun</option>
                            <option value="shotgun">Shotgun</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Save</button>
                        @if($editingCalibreId)
                            <button type="button" wire:click="resetCalibreForm" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($calibres as $calibre)
                            <tr>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $calibre->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-300">
                                        {{ ucfirst($calibre->category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="editCalibre({{ $calibre->id }})" class="text-emerald-600 hover:text-emerald-700 text-sm">Edit</button>
                                    <button wire:click="deleteCalibre({{ $calibre->id }})" wire:confirm="Are you sure?" class="ml-4 text-red-600 hover:text-red-700 text-sm">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
