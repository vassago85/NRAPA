<?php

use App\Models\ActivityType;
use App\Models\ActivityTag;
use Livewire\Component;

new class extends Component {
    public string $activeTab = 'activity-types';

    // Activity Type form
    public ?int $editingActivityTypeId = null;
    public string $activityTypeName = '';
    public string $activityTypeTrack = '';
    public ?string $activityTypeGroup = null;
    public int $activityTypeSortOrder = 0;

    // Activity Tag form
    public ?int $editingActivityTagId = null;
    public string $activityTagKey = '';
    public string $activityTagLabel = '';
    public ?string $activityTagTrack = null;
    public int $activityTagSortOrder = 0;

    // === Activity Types ===
    public function saveActivityType(): void
    {
        $this->validate([
            'activityTypeName' => ['required', 'string', 'max:255'],
            'activityTypeTrack' => ['required', 'in:hunting,sport'],
            'activityTypeGroup' => ['nullable', 'string', 'max:255'],
            'activityTypeSortOrder' => ['required', 'integer', 'min:0'],
        ]);

        ActivityType::updateOrCreate(
            ['id' => $this->editingActivityTypeId],
            [
                'name' => $this->activityTypeName,
                'slug' => \Illuminate\Support\Str::slug($this->activityTypeName),
                'track' => $this->activityTypeTrack,
                'group' => $this->activityTypeGroup,
                'sort_order' => $this->activityTypeSortOrder,
                'is_active' => true,
            ]
        );

        $this->resetActivityTypeForm();
        session()->flash('success', 'Activity type saved successfully.');
    }

    public function editActivityType(ActivityType $activityType): void
    {
        $this->editingActivityTypeId = $activityType->id;
        $this->activityTypeName = $activityType->name;
        $this->activityTypeTrack = $activityType->track ?? '';
        $this->activityTypeGroup = $activityType->group;
        $this->activityTypeSortOrder = $activityType->sort_order;
    }

    public function deleteActivityType(ActivityType $activityType): void
    {
        // Soft delete by deactivating
        $activityType->update(['is_active' => false]);
        session()->flash('success', 'Activity type deactivated.');
    }

    public function resetActivityTypeForm(): void
    {
        $this->editingActivityTypeId = null;
        $this->activityTypeName = '';
        $this->activityTypeTrack = '';
        $this->activityTypeGroup = null;
        $this->activityTypeSortOrder = 0;
    }

    // === Activity Tags ===
    public function saveActivityTag(): void
    {
        $this->validate([
            'activityTagKey' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/'],
            'activityTagLabel' => ['required', 'string', 'max:255'],
            'activityTagTrack' => ['nullable', 'in:hunting,sport'],
            'activityTagSortOrder' => ['required', 'integer', 'min:0'],
        ]);

        ActivityTag::updateOrCreate(
            ['id' => $this->editingActivityTagId],
            [
                'key' => $this->activityTagKey,
                'label' => $this->activityTagLabel,
                'track' => $this->activityTagTrack,
                'sort_order' => $this->activityTagSortOrder,
                'is_active' => true,
            ]
        );

        $this->resetActivityTagForm();
        session()->flash('success', 'Activity tag saved successfully.');
    }

    public function editActivityTag(ActivityTag $activityTag): void
    {
        $this->editingActivityTagId = $activityTag->id;
        $this->activityTagKey = $activityTag->key;
        $this->activityTagLabel = $activityTag->label;
        $this->activityTagTrack = $activityTag->track;
        $this->activityTagSortOrder = $activityTag->sort_order;
    }

    public function deleteActivityTag(ActivityTag $activityTag): void
    {
        // Soft delete by deactivating
        $activityTag->update(['is_active' => false]);
        session()->flash('success', 'Activity tag deactivated.');
    }

    public function resetActivityTagForm(): void
    {
        $this->editingActivityTagId = null;
        $this->activityTagKey = '';
        $this->activityTagLabel = '';
        $this->activityTagTrack = null;
        $this->activityTagSortOrder = 0;
    }

    public function with(): array
    {
        return [
            'activityTypes' => ActivityType::ordered()->get(),
            'activityTags' => ActivityTag::ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Activity Configuration</h1>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Manage activity types and optional tags for activity logging</p>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-500">
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Firearm Types and Calibres are managed in 
                <a href="{{ route('admin.firearm-settings.index') }}" wire:navigate class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">
                    Firearm Settings
                </a>
            </span>
        </p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4">
            <button wire:click="$set('activeTab', 'activity-types')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'activity-types' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:border-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                Activity Types
            </button>
            <button wire:click="$set('activeTab', 'activity-tags')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors {{ $activeTab === 'activity-tags' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:border-zinc-300 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                Activity Tags
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
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="activityTypeName" placeholder="e.g., Hunting Safari" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('activityTypeName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Track <span class="text-red-500">*</span></label>
                        <select wire:model="activityTypeTrack" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">Select Track</option>
                            <option value="hunting">Hunting</option>
                            <option value="sport">Sport Shooting</option>
                        </select>
                        @error('activityTypeTrack') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Group (Optional)</label>
                        <select wire:model="activityTypeGroup" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">No Group</option>
                            @foreach(\App\Models\ActivityType::getGroups() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">UI-only grouping for better organization</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                        <input type="number" wire:model="activityTypeSortOrder" min="0" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">Save</button>
                        @if($editingActivityTypeId)
                            <button type="button" wire:click="resetActivityTypeForm" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Activity Types</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Track</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Group</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($activityTypes as $type)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{{ $type->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $type->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                            {{ ucfirst($type->track ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $type->group ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $type->sort_order }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <button wire:click="editActivityType({{ $type->id }})" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Edit</button>
                                        <button wire:click="deleteActivityType({{ $type->id }})" wire:confirm="Are you sure you want to deactivate this activity type?" class="ml-4 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Deactivate</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No activity types found. Add your first activity type above.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Activity Tags Tab -->
    @if($activeTab === 'activity-tags')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Form -->
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingActivityTagId ? 'Edit' : 'Add' }} Activity Tag</h3>
                <form wire:submit="saveActivityTag" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Key <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="activityTagKey" placeholder="e.g., prs, ipsc, idpa" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lowercase, alphanumeric, hyphens, underscores only</p>
                        @error('activityTagKey') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Label <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="activityTagLabel" placeholder="e.g., PRS, IPSC, IDPA" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                        @error('activityTagLabel') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Track (Optional)</label>
                        <select wire:model="activityTagTrack" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                            <option value="">All Tracks</option>
                            <option value="hunting">Hunting Only</option>
                            <option value="sport">Sport Shooting Only</option>
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">If set, tag only appears for this track</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sort Order</label>
                        <input type="number" wire:model="activityTagSortOrder" min="0" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2 text-sm text-zinc-900 dark:text-white">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">Save</button>
                        @if($editingActivityTagId)
                            <button type="button" wire:click="resetActivityTagForm" class="rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Activity Tags</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Optional tags that members can select when logging activities (e.g., PRS, IPSC, IDPA)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Key</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Label</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Track</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($activityTags as $tag)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 text-sm font-mono text-zinc-900 dark:text-white">{{ $tag->key }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{{ $tag->label }}</td>
                                    <td class="px-6 py-4">
                                        @if($tag->track)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tag->track === 'hunting' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                                {{ ucfirst($tag->track) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400 dark:text-zinc-500">All</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $tag->sort_order }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <button wire:click="editActivityTag({{ $tag->id }})" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Edit</button>
                                        <button wire:click="deleteActivityTag({{ $tag->id }})" wire:confirm="Are you sure you want to deactivate this tag?" class="ml-4 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Deactivate</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No activity tags found. Add your first tag above.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
