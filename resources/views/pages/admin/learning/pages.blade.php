<?php

use App\Helpers\StorageHelper;
use App\Models\LearningArticle;
use App\Models\LearningArticlePage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Title('Manage Pages - Learning Center')] class extends Component {
    use WithFileUploads;

    public LearningArticle $article;

    // Page form
    public ?int $editingPageId = null;
    public string $pageTitle = '';
    public $pageImage = null;
    public ?string $existingPageImage = null;
    public bool $removePageImage = false;
    public string $pageImageCaption = '';
    public string $pageContent = '';

    public function mount(LearningArticle $article): void
    {
        $this->article = $article;
    }

    #[Computed]
    public function pages()
    {
        return $this->article->pages()->ordered()->get();
    }

    public function editPage(?int $id = null): void
    {
        if ($id) {
            $page = LearningArticlePage::findOrFail($id);
            $this->editingPageId = $id;
            $this->pageTitle = $page->title;
            $this->existingPageImage = $page->image_path;
            $this->pageImageCaption = $page->image_caption ?? '';
            $this->pageContent = $page->content;
        } else {
            $this->editingPageId = 0;
            $this->pageTitle = '';
            $this->existingPageImage = null;
            $this->pageImageCaption = '';
            $this->pageContent = '';
        }
        $this->pageImage = null;
        $this->removePageImage = false;
    }

    public function cancelPageEdit(): void
    {
        $this->editingPageId = null;
        $this->pageTitle = '';
        $this->pageImage = null;
        $this->existingPageImage = null;
        $this->removePageImage = false;
        $this->pageImageCaption = '';
        $this->pageContent = '';
    }

    public function savePage(): void
    {
        $this->validate([
            'pageTitle' => ['required', 'string', 'max:255'],
            'pageImage' => ['nullable', 'image', 'max:5120'],
            'pageImageCaption' => ['nullable', 'string', 'max:255'],
            'pageContent' => ['required', 'string'],
        ]);

        $imagePath = $this->existingPageImage;

        if ($this->removePageImage && $this->existingPageImage) {
            StorageHelper::deleteFile($this->existingPageImage);
            $imagePath = null;
        }

        if ($this->pageImage) {
            if ($this->existingPageImage) {
                StorageHelper::deleteFile($this->existingPageImage);
            }
            $imagePath = StorageHelper::storeFile($this->pageImage, 'learning/pages');
        }

        $data = [
            'learning_article_id' => $this->article->id,
            'title' => $this->pageTitle,
            'image_path' => $imagePath,
            'image_caption' => $this->pageImageCaption ?: null,
            'content' => $this->pageContent,
        ];

        if ($this->editingPageId) {
            $page = LearningArticlePage::findOrFail($this->editingPageId);
            $page->update($data);
            session()->flash('success', 'Page updated successfully.');
        } else {
            $data['page_number'] = ($this->article->pages()->max('page_number') ?? 0) + 1;
            LearningArticlePage::create($data);
            session()->flash('success', 'Page added successfully.');
        }

        $this->cancelPageEdit();
    }

    public function deletePage(int $id): void
    {
        $page = LearningArticlePage::findOrFail($id);

        if ($page->image_path) {
            StorageHelper::deleteFile($page->image_path);
        }

        $deletedPageNumber = $page->page_number;
        $page->delete();

        // Reorder remaining pages
        $this->article->pages()
            ->where('page_number', '>', $deletedPageNumber)
            ->decrement('page_number');

        session()->flash('success', 'Page deleted successfully.');
    }

    public function movePageUp(int $id): void
    {
        $page = LearningArticlePage::findOrFail($id);
        $previousPage = $this->article->pages()
            ->where('page_number', '<', $page->page_number)
            ->orderByDesc('page_number')
            ->first();

        if ($previousPage) {
            $tempOrder = $page->page_number;
            $page->update(['page_number' => $previousPage->page_number]);
            $previousPage->update(['page_number' => $tempOrder]);
        }
    }

    public function movePageDown(int $id): void
    {
        $page = LearningArticlePage::findOrFail($id);
        $nextPage = $this->article->pages()
            ->where('page_number', '>', $page->page_number)
            ->orderBy('page_number')
            ->first();

        if ($nextPage) {
            $tempOrder = $page->page_number;
            $page->update(['page_number' => $nextPage->page_number]);
            $nextPage->update(['page_number' => $tempOrder]);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.learning.index') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $article->title }}</h1>
                <p class="text-zinc-600 dark:text-zinc-400">{{ $this->pages->count() }} {{ Str::plural('page', $this->pages->count()) }} • {{ $article->category->name }}</p>
            </div>
        </div>
        @if($editingPageId === null)
        <button wire:click="editPage()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Page
        </button>
        @endif
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Page Form --}}
    @if($editingPageId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $editingPageId ? 'Edit Page' : 'Add New Page' }}
        </h2>

        <form wire:submit="savePage" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Page Title / Subheading</label>
                <input type="text" wire:model="pageTitle" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="e.g., Introduction to Safe Handling">
                @error('pageTitle') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Page Image (Optional)</label>
                <div class="mt-2">
                    @if($pageImage)
                    <div class="relative inline-block">
                        <img src="{{ $pageImage->temporaryUrl() }}" alt="Preview" class="max-h-48 rounded-lg border border-zinc-200 object-contain dark:border-zinc-700">
                        <button type="button" wire:click="$set('pageImage', null)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @elseif($existingPageImage && !$removePageImage)
                    <div class="relative inline-block">
                        <img src="{{ StorageHelper::getUrl($existingPageImage) }}" alt="Current" class="max-h-48 rounded-lg border border-zinc-200 object-contain dark:border-zinc-700">
                        <button type="button" wire:click="$set('removePageImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @else
                    <div 
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="dragging = false; $refs.pageImageInput.files = $event.dataTransfer.files; $refs.pageImageInput.dispatchEvent(new Event('input', { bubbles: true }))"
                        :class="{ 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20': dragging }"
                        class="flex h-32 w-full max-w-md cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                    >
                        <label class="cursor-pointer text-center">
                            <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-2 text-sm text-zinc-500">Drop or click to upload page image</p>
                            <p class="text-xs text-zinc-400">PNG, JPG up to 5MB</p>
                            <input x-ref="pageImageInput" type="file" wire:model="pageImage" class="hidden" accept="image/*">
                        </label>
                    </div>
                    @endif
                </div>
                @error('pageImage') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            @if($pageImage || ($existingPageImage && !$removePageImage))
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Image Caption (Optional)</label>
                <input type="text" wire:model="pageImageCaption" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Describe the image...">
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Content</label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">You can use HTML tags for formatting.</p>
                <textarea wire:model="pageContent" rows="10" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 font-mono text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="<p>Page content goes here...</p>"></textarea>
                @error('pageContent') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    {{ $editingPageId ? 'Update Page' : 'Add Page' }}
                </button>
                <button type="button" wire:click="cancelPageEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Pages List --}}
    <div class="space-y-4">
        @forelse($this->pages as $index => $page)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="flex flex-col gap-1">
                        <button wire:click="movePageUp({{ $page->id }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === 0 ? 'opacity-30 cursor-not-allowed' : '' }}" {{ $index === 0 ? 'disabled' : '' }}>
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                            </svg>
                        </button>
                        <button wire:click="movePageDown({{ $page->id }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === $this->pages->count() - 1 ? 'opacity-30 cursor-not-allowed' : '' }}" {{ $index === $this->pages->count() - 1 ? 'disabled' : '' }}>
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                        {{ $page->page_number }}
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $page->title }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ Str::limit(strip_tags($page->content), 150) }}</p>
                        <div class="mt-2 flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            @if($page->hasImage())
                            <span class="inline-flex items-center gap-1">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                Has image
                            </span>
                            @endif
                            <span>{{ str_word_count(strip_tags($page->content)) }} words</span>
                        </div>
                    </div>
                    @if($page->hasImage())
                    <img src="{{ $page->image_url }}" alt="{{ $page->title }}" class="h-20 w-32 rounded-lg object-cover">
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="editPage({{ $page->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                        Edit
                    </button>
                    <button wire:click="deletePage({{ $page->id }})" wire:confirm="Are you sure you want to delete this page?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No pages yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Add pages to create a multi-page article that members can read sequentially.</p>
            <button wire:click="editPage()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add First Page
            </button>
        </div>
        @endforelse
    </div>

    {{-- Info Box --}}
    @if($this->pages->count() > 0)
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-start gap-3">
            <svg class="size-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Multi-page Article</p>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    This article has {{ $this->pages->count() }} {{ Str::plural('page', $this->pages->count()) }}. Members will read through each page sequentially. 
                    The article's main content field will be used as an introduction before the pages.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
