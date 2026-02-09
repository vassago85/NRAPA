<?php

use App\Helpers\StorageHelper;
use App\Models\KnowledgeTest;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Knowledge Test - Admin')] class extends Component {
    use WithFileUploads;

    public KnowledgeTest $test;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public $testDocument = null;
    public ?string $existingTestDocument = null;
    public bool $removeTestDocument = false;

    #[Validate('required|integer|min:1|max:100')]
    public int $passing_score = 70;

    #[Validate('nullable|integer|min:1|max:480')]
    public ?int $time_limit_minutes = 60;

    #[Validate('required|integer|min:1|max:10')]
    public int $max_attempts = 3;

    public bool $is_active = false;

    #[Validate('required|in:hunter,sport,both')]
    public string $dedicated_type = 'both';

    public function mount(KnowledgeTest $test): void
    {
        $this->test = $test;
        $this->name = $test->name;
        $this->description = $test->description ?? '';
        $this->existingTestDocument = $test->document_path;
        $this->passing_score = $test->passing_score;
        $this->time_limit_minutes = $test->time_limit_minutes;
        $this->max_attempts = $test->max_attempts;
        $this->is_active = $test->is_active;
        $this->dedicated_type = $test->dedicated_type ?? 'both';
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'testDocument' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'dedicated_type' => ['required', 'in:hunter,sport,both'],
        ]);

        $documentPath = $this->existingTestDocument;

        if ($this->removeTestDocument && $this->existingTestDocument) {
            StorageHelper::deleteFile($this->existingTestDocument);
            $documentPath = null;
        }

        if ($this->testDocument) {
            if ($this->existingTestDocument) {
                StorageHelper::deleteFile($this->existingTestDocument);
            }
            $documentPath = StorageHelper::storeFile($this->testDocument, 'knowledge-tests/documents');
        }

        $this->test->update([
            'slug' => Str::slug($this->name),
            'name' => $this->name,
            'description' => $this->description,
            'document_path' => $documentPath,
            'passing_score' => $this->passing_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'max_attempts' => $this->max_attempts,
            'is_active' => $this->is_active,
            'dedicated_type' => $this->dedicated_type,
        ]);

        session()->flash('success', 'Test updated successfully.');

        $this->redirect(route('admin.knowledge-tests.index'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back
        </a>
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Edit: {{ $test->name }}</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Update test settings and configuration.</p>
        </div>
    </div>

    {{-- Form --}}
    <form wire:submit="save" class="max-w-2xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Test Details</h2>

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Test Name</label>
                    <input type="text" id="name" wire:model="name" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea id="description" wire:model="description" rows="3" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Test Document (Optional)</label>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Upload a PDF, DOC, or DOCX file containing test materials or reference documents</p>
                    <div class="mt-2">
                        @if($testDocument)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $testDocument->getClientOriginalName() }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($testDocument->getSize() / 1024, 2) }} KB</p>
                            </div>
                            <button type="button" wire:click="$set('testDocument', null)" class="rounded-lg bg-red-500 p-1.5 text-white hover:bg-red-600">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        @elseif($existingTestDocument && !$removeTestDocument)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ basename($existingTestDocument) }}</p>
                                <a href="{{ StorageHelper::getUrl($existingTestDocument) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400">View Document</a>
                            </div>
                            <button type="button" wire:click="$set('removeTestDocument', true)" class="rounded-lg bg-red-500 p-1.5 text-white hover:bg-red-600">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        @else
                        <div 
                            x-data="{ 
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        const input = this.$refs.testDocumentInput;
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
                            :class="{ 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20': dragging }"
                            class="flex h-24 w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                        >
                            <label class="cursor-pointer text-center">
                                <svg class="mx-auto size-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-1 text-xs text-zinc-500">Drop or click to upload document</p>
                                <p class="text-xs text-zinc-400">PDF, DOC, DOCX up to 10MB</p>
                                <input x-ref="testDocumentInput" type="file" wire:model="testDocument" class="hidden" accept=".pdf,.doc,.docx">
                            </label>
                        </div>
                        @endif
                    </div>
                    @error('testDocument') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Dedicated Status Type</h2>

            <div>
                <label for="dedicated_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Required For</label>
                <select id="dedicated_type" wire:model="dedicated_type" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="hunter">Dedicated Hunter Only</option>
                    <option value="sport">Dedicated Sport Shooter Only</option>
                    <option value="both">Both (Hunter & Sport Shooter)</option>
                </select>
                @error('dedicated_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Test is active</span>
                </label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-nrapa-blue px-6 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                Save Changes
            </button>
            <a href="{{ route('admin.knowledge-tests.questions', $test) }}" wire:navigate class="rounded-lg border border-blue-300 bg-white px-6 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-600 dark:bg-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-600">
                Manage Questions
            </a>
            <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
