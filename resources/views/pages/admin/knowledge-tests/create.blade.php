<?php

use App\Models\KnowledgeTest;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Knowledge Test - Admin')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|integer|min:1|max:100')]
    public int $passing_score = 70;

    #[Validate('nullable|integer|min:1|max:480')]
    public ?int $time_limit_minutes = 60;

    #[Validate('required|integer|min:1|max:10')]
    public int $max_attempts = 3;

    public bool $is_active = false;

    #[Validate('required|in:hunter,sport_shooter,both')]
    public string $dedicated_type = 'both';

    public function save(): void
    {
        $this->validate();

        $test = KnowledgeTest::create([
            'slug' => Str::slug($this->name),
            'name' => $this->name,
            'description' => $this->description,
            'passing_score' => $this->passing_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'max_attempts' => $this->max_attempts,
            'is_active' => $this->is_active,
            'dedicated_type' => $this->dedicated_type,
        ]);

        session()->flash('success', 'Test created successfully. Now add some questions.');

        $this->redirect(route('admin.knowledge-tests.questions', $test), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back
        </a>
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Create Knowledge Test</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Set up a new online knowledge test for members.</p>
        </div>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="max-w-2xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Test Details</h2>

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Test Name</label>
                    <input type="text" id="name" wire:model="name" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="e.g. NRAPA Firearm Safety Test">
                    @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea id="description" wire:model="description" rows="3" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Brief description of the test..."></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Dedicated Status Type</h2>
            <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">Knowledge tests are required for members applying for Dedicated Status. Select which type of dedicated status this test applies to.</p>

            <div>
                <label for="dedicated_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Required For</label>
                <select id="dedicated_type" wire:model="dedicated_type" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="hunter">Dedicated Hunter Only</option>
                    <option value="sport_shooter">Dedicated Sport Shooter Only</option>
                    <option value="both">Both (Hunter & Sport Shooter)</option>
                </select>
                @error('dedicated_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Test Configuration</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="passing_score" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pass Mark (%)</label>
                    <input type="number" id="passing_score" wire:model="passing_score" min="1" max="100" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('passing_score') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="time_limit_minutes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Time Limit (minutes)</label>
                    <input type="number" id="time_limit_minutes" wire:model="time_limit_minutes" min="1" max="480" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Leave empty for no limit">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave empty for unlimited time</p>
                    @error('time_limit_minutes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="max_attempts" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Max Attempts</label>
                    <input type="number" id="max_attempts" wire:model="max_attempts" min="1" max="10" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('max_attempts') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" wire:model="is_active" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Make test active immediately</span>
                </label>
                <p class="ml-7 mt-1 text-xs text-zinc-500 dark:text-zinc-400">You can activate it later after adding questions</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                Create Test
            </button>
            <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                Cancel
            </a>
        </div>
    </form>
</div>
