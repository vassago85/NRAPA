<?php

use App\Models\TermsVersion;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app.sidebar')] class extends Component {
    use WithFileUploads;

    public $versions;
    public $showCreateForm = false;
    public $editingVersion = null;
    
    // Form fields
    public $version = '';
    public $title = 'NRAPA Membership Terms & Conditions';
    public $htmlFile = null;
    public $htmlContent = '';
    public $useFile = true;

    public function mount(): void
    {
        $this->loadVersions();
    }

    public function loadVersions(): void
    {
        $this->versions = TermsVersion::orderBy('created_at', 'desc')->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showCreateForm = true;
        $this->editingVersion = null;
    }

    public function edit(TermsVersion $version): void
    {
        $this->editingVersion = $version;
        $this->version = $version->version;
        $this->title = $version->title;
        $this->htmlContent = $version->html_content ?? '';
        $this->useFile = !empty($version->html_path);
        $this->showCreateForm = true;
    }

    public function resetForm(): void
    {
        $this->version = '';
        $this->title = 'NRAPA Membership Terms & Conditions';
        $this->htmlFile = null;
        $this->htmlContent = '';
        $this->useFile = true;
        $this->editingVersion = null;
    }

    public function save(): void
    {
        $this->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'htmlFile' => $this->useFile ? 'required|file|mimes:html,htm|max:10240' : 'nullable',
            'htmlContent' => $this->useFile ? 'nullable' : 'required|string',
        ], [
            'version.required' => 'Version is required (e.g., "2026-01")',
            'title.required' => 'Title is required',
            'htmlFile.required' => 'HTML file is required when using file upload',
            'htmlContent.required' => 'HTML content is required when not using file upload',
        ]);

        $data = [
            'version' => $this->version,
            'title' => $this->title,
        ];

        if ($this->useFile && $this->htmlFile) {
            $disk = \App\Helpers\StorageHelper::getPublicDisk();
            $path = $this->htmlFile->store('terms', $disk);
            $data['html_path'] = $path;
            $data['html_content'] = null;
        } else {
            $data['html_content'] = $this->htmlContent;
            $data['html_path'] = null;
        }

        if ($this->editingVersion) {
            // Update existing
            $this->editingVersion->update($data);
            session()->flash('success', 'Terms version updated successfully.');
        } else {
            // Create new
            $data['published_at'] = now();
            TermsVersion::create($data);
            session()->flash('success', 'Terms version created successfully.');
        }

        $this->resetForm();
        $this->showCreateForm = false;
        $this->loadVersions();
    }

    public function activate(TermsVersion $version): void
    {
        $version->activate();
        session()->flash('success', 'Terms version activated successfully.');
        $this->loadVersions();
    }

    public function preview(TermsVersion $version): void
    {
        $this->redirect(route('admin.settings.terms.preview', $version));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showCreateForm = false;
    }

    public function render(): mixed
    {
        return view('pages.admin.settings.terms');
    }
};

?>

<div>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">General Settings</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage terms and conditions versions</p>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Terms & Conditions Management</h1>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            Manage NRAPA Membership Terms & Conditions versions. Members must accept the active version.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
            <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ session('success') }}</p>
        </div>
    @endif

    @if(!$showCreateForm)
        <div class="mb-4 flex justify-end">
            <button 
                wire:click="createNew"
                class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create New Version
            </button>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Version</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Published</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($versions as $version)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $version->version }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $version->title }}</td>
                                <td class="px-4 py-3">
                                    @if($version->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $version->published_at?->format('d M Y') ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button 
                                            wire:click="preview({{ $version->id }})"
                                            class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors"
                                        >
                                            Preview
                                        </button>
                                        <button 
                                            wire:click="edit({{ $version->id }})"
                                            class="text-sm text-zinc-600 hover:text-zinc-700 dark:text-zinc-400 transition-colors"
                                        >
                                            Edit
                                        </button>
                                        @if(!$version->is_active)
                                            <button 
                                                wire:click="activate({{ $version->id }})"
                                                wire:confirm="Are you sure you want to activate this version? This will deactivate all other versions."
                                                class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors"
                                            >
                                                Activate
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No terms versions found. Create the first version to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $editingVersion ? 'Edit Terms Version' : 'Create New Terms Version' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="version" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Version <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="version" 
                        wire:model="version"
                        placeholder="e.g., 2026-01"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                    >
                    @error('version') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        wire:model="title"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                    >
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        HTML Source
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input 
                                type="radio" 
                                wire:model.live="useFile" 
                                value="1"
                                class="h-4 w-4 text-emerald-600"
                            >
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Upload HTML File</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input 
                                type="radio" 
                                wire:model.live="useFile" 
                                value="0"
                                class="h-4 w-4 text-emerald-600"
                            >
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Paste HTML Content</span>
                        </label>
                    </div>
                </div>

                @if($useFile)
                    <div>
                        <label for="htmlFile" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            HTML File <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="file" 
                            id="htmlFile" 
                            wire:model="htmlFile"
                            accept=".html,.htm"
                            class="mt-1 block w-full text-sm text-zinc-500 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                        >
                        @error('htmlFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label for="htmlContent" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            HTML Content <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="htmlContent" 
                            wire:model="htmlContent"
                            rows="15"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        ></textarea>
                        @error('htmlContent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex gap-3">
                    <button 
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors"
                    >
                        {{ $editingVersion ? 'Update' : 'Create' }} Version
                    </button>
                    <button 
                        type="button"
                        wire:click="cancel"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
