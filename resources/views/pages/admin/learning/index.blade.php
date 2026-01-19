<?php

use App\Models\LearningCategory;
use App\Models\LearningArticle;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new #[Title('Learning Center - Admin')] class extends Component {
    use WithFileUploads;

    public string $tab = 'categories';

    // Category form
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public string $categoryDescription = '';
    public $categoryImage = null;
    public ?string $existingCategoryImage = null;
    public bool $removeCategoryImage = false;

    // Article form
    public ?int $editingArticleId = null;
    public ?int $articleCategoryId = null;
    public string $articleTitle = '';
    public string $articleExcerpt = '';
    public string $articleContent = '';
    public $articleFeaturedImage = null;
    public ?string $existingArticleFeaturedImage = null;
    public bool $removeArticleFeaturedImage = false;
    public bool $articleIsFeatured = false;

    #[Computed]
    public function categories()
    {
        return LearningCategory::ordered()->withCount('articles')->get();
    }

    #[Computed]
    public function articles()
    {
        return LearningArticle::with(['category', 'author'])
            ->withCount('pages')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        return [
            'categories' => LearningCategory::count(),
            'articles' => LearningArticle::count(),
            'published' => LearningArticle::published()->count(),
            'featured' => LearningArticle::featured()->count(),
        ];
    }

    // Category methods
    public function editCategory(?int $id = null): void
    {
        if ($id) {
            $category = LearningCategory::findOrFail($id);
            $this->editingCategoryId = $id;
            $this->categoryName = $category->name;
            $this->categoryDescription = $category->description ?? '';
            $this->existingCategoryImage = $category->image_path;
        } else {
            $this->editingCategoryId = 0;
            $this->categoryName = '';
            $this->categoryDescription = '';
            $this->existingCategoryImage = null;
        }
        $this->categoryImage = null;
        $this->removeCategoryImage = false;
    }

    public function cancelCategoryEdit(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryDescription = '';
        $this->categoryImage = null;
        $this->existingCategoryImage = null;
        $this->removeCategoryImage = false;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => ['required', 'string', 'max:255'],
            'categoryDescription' => ['nullable', 'string', 'max:1000'],
            'categoryImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = $this->existingCategoryImage;

        if ($this->removeCategoryImage && $this->existingCategoryImage) {
            Storage::disk('public')->delete($this->existingCategoryImage);
            $imagePath = null;
        }

        if ($this->categoryImage) {
            if ($this->existingCategoryImage) {
                Storage::disk('public')->delete($this->existingCategoryImage);
            }
            $imagePath = $this->categoryImage->store('learning/categories', 'public');
        }

        $data = [
            'name' => $this->categoryName,
            'slug' => Str::slug($this->categoryName),
            'description' => $this->categoryDescription ?: null,
            'image_path' => $imagePath,
        ];

        if ($this->editingCategoryId) {
            $category = LearningCategory::findOrFail($this->editingCategoryId);
            // Preserve slug if name hasn't changed
            if ($category->name === $this->categoryName) {
                unset($data['slug']);
            }
            $category->update($data);
            session()->flash('success', 'Category updated successfully.');
        } else {
            $data['sort_order'] = LearningCategory::max('sort_order') + 1;
            LearningCategory::create($data);
            session()->flash('success', 'Category created successfully.');
        }

        $this->cancelCategoryEdit();
    }

    public function toggleCategoryActive(int $id): void
    {
        $category = LearningCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
    }

    public function deleteCategory(int $id): void
    {
        $category = LearningCategory::findOrFail($id);

        if ($category->articles()->count() > 0) {
            session()->flash('error', 'Cannot delete a category with articles. Delete or move the articles first.');
            return;
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();
        session()->flash('success', 'Category deleted successfully.');
    }

    // Article methods
    public function editArticle(?int $id = null): void
    {
        if ($id) {
            $article = LearningArticle::findOrFail($id);
            $this->editingArticleId = $id;
            $this->articleCategoryId = $article->learning_category_id;
            $this->articleTitle = $article->title;
            $this->articleExcerpt = $article->excerpt ?? '';
            $this->articleContent = $article->content;
            $this->existingArticleFeaturedImage = $article->featured_image;
            $this->articleIsFeatured = $article->is_featured;
        } else {
            $this->editingArticleId = 0;
            $this->articleCategoryId = $this->categories->first()?->id;
            $this->articleTitle = '';
            $this->articleExcerpt = '';
            $this->articleContent = '';
            $this->existingArticleFeaturedImage = null;
            $this->articleIsFeatured = false;
        }
        $this->articleFeaturedImage = null;
        $this->removeArticleFeaturedImage = false;
        $this->tab = 'articles';
    }

    public function cancelArticleEdit(): void
    {
        $this->editingArticleId = null;
        $this->articleCategoryId = null;
        $this->articleTitle = '';
        $this->articleExcerpt = '';
        $this->articleContent = '';
        $this->articleFeaturedImage = null;
        $this->existingArticleFeaturedImage = null;
        $this->removeArticleFeaturedImage = false;
        $this->articleIsFeatured = false;
    }

    public function saveArticle(): void
    {
        $this->validate([
            'articleCategoryId' => ['required', 'exists:learning_categories,id'],
            'articleTitle' => ['required', 'string', 'max:255'],
            'articleExcerpt' => ['nullable', 'string', 'max:500'],
            'articleContent' => ['required', 'string'],
            'articleFeaturedImage' => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = $this->existingArticleFeaturedImage;

        if ($this->removeArticleFeaturedImage && $this->existingArticleFeaturedImage) {
            Storage::disk('public')->delete($this->existingArticleFeaturedImage);
            $imagePath = null;
        }

        if ($this->articleFeaturedImage) {
            if ($this->existingArticleFeaturedImage) {
                Storage::disk('public')->delete($this->existingArticleFeaturedImage);
            }
            $imagePath = $this->articleFeaturedImage->store('learning/articles', 'public');
        }

        $data = [
            'learning_category_id' => $this->articleCategoryId,
            'title' => $this->articleTitle,
            'slug' => Str::slug($this->articleTitle),
            'excerpt' => $this->articleExcerpt ?: null,
            'content' => $this->articleContent,
            'featured_image' => $imagePath,
            'is_featured' => $this->articleIsFeatured,
        ];

        if ($this->editingArticleId) {
            $article = LearningArticle::findOrFail($this->editingArticleId);
            // Preserve slug if title hasn't changed
            if ($article->title === $this->articleTitle) {
                unset($data['slug']);
            }
            $article->update($data);
            session()->flash('success', 'Article updated successfully.');
        } else {
            $data['author_id'] = auth()->id();
            $data['sort_order'] = LearningArticle::max('sort_order') + 1;
            LearningArticle::create($data);
            session()->flash('success', 'Article created successfully.');
        }

        $this->cancelArticleEdit();
    }

    public function toggleArticlePublished(int $id): void
    {
        $article = LearningArticle::findOrFail($id);
        if ($article->is_published) {
            $article->unpublish();
        } else {
            $article->publish();
        }
    }

    public function toggleArticleFeatured(int $id): void
    {
        $article = LearningArticle::findOrFail($id);
        $article->update(['is_featured' => !$article->is_featured]);
    }

    public function deleteArticle(int $id): void
    {
        $article = LearningArticle::findOrFail($id);

        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }

        // Delete article images
        foreach ($article->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $article->delete();
        session()->flash('success', 'Article deleted successfully.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Learning Center</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Manage educational content for members</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="editCategory()" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Category
            </button>
            <button wire:click="editArticle()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Article
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Categories</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['categories'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Articles</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['articles'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Published</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['published'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Featured</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['featured'] }}</p>
        </div>
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

    {{-- Category Form --}}
    @if($editingCategoryId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $editingCategoryId ? 'Edit Category' : 'New Category' }}
        </h2>

        <form wire:submit="saveCategory" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Category Name</label>
                <input type="text" wire:model="categoryName" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="e.g., Firearm Safety">
                @error('categoryName') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                <textarea wire:model="categoryDescription" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Brief description of this category..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Category Image (Optional)</label>
                <div class="mt-2">
                    @if($categoryImage)
                    <div class="relative inline-block">
                        <img src="{{ $categoryImage->temporaryUrl() }}" alt="Preview" class="h-24 w-40 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('categoryImage', null)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @elseif($existingCategoryImage && !$removeCategoryImage)
                    <div class="relative inline-block">
                        <img src="{{ Storage::url($existingCategoryImage) }}" alt="Current" class="h-24 w-40 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('removeCategoryImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @else
                    <label class="flex h-24 w-40 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <div class="text-center">
                            <svg class="mx-auto size-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-1 text-xs text-zinc-500">Upload</p>
                        </div>
                        <input type="file" wire:model="categoryImage" class="hidden" accept="image/*">
                    </label>
                    @endif
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    {{ $editingCategoryId ? 'Update Category' : 'Create Category' }}
                </button>
                <button type="button" wire:click="cancelCategoryEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Article Form --}}
    @if($editingArticleId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
            {{ $editingArticleId ? 'Edit Article' : 'New Article' }}
        </h2>

        <form wire:submit="saveArticle" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Category</label>
                    <select wire:model="articleCategoryId" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('articleCategoryId') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="articleIsFeatured" class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Featured Article</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Title</label>
                <input type="text" wire:model="articleTitle" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Article title...">
                @error('articleTitle') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Excerpt (Optional)</label>
                <textarea wire:model="articleExcerpt" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="Brief summary shown in listings..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Featured Image (Optional)</label>
                <div class="mt-2">
                    @if($articleFeaturedImage)
                    <div class="relative inline-block">
                        <img src="{{ $articleFeaturedImage->temporaryUrl() }}" alt="Preview" class="h-32 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('articleFeaturedImage', null)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @elseif($existingArticleFeaturedImage && !$removeArticleFeaturedImage)
                    <div class="relative inline-block">
                        <img src="{{ Storage::url($existingArticleFeaturedImage) }}" alt="Current" class="h-32 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('removeArticleFeaturedImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @else
                    <label class="flex h-32 w-full max-w-md cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <div class="text-center">
                            <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-2 text-sm text-zinc-500">Click to upload featured image</p>
                            <p class="text-xs text-zinc-400">PNG, JPG up to 5MB</p>
                        </div>
                        <input type="file" wire:model="articleFeaturedImage" class="hidden" accept="image/*">
                    </label>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Content</label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">You can use HTML tags for formatting. Use &lt;img&gt; tags to embed images.</p>
                <textarea wire:model="articleContent" rows="12" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 font-mono text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="<p>Article content goes here...</p>"></textarea>
                @error('articleContent') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    {{ $editingArticleId ? 'Update Article' : 'Create Article' }}
                </button>
                <button type="button" wire:click="cancelArticleEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4">
            <button wire:click="$set('tab', 'categories')" class="border-b-2 px-1 py-3 text-sm font-medium {{ $tab === 'categories' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Categories ({{ $this->stats['categories'] }})
            </button>
            <button wire:click="$set('tab', 'articles')" class="border-b-2 px-1 py-3 text-sm font-medium {{ $tab === 'articles' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Articles ({{ $this->stats['articles'] }})
            </button>
        </nav>
    </div>

    {{-- Categories Tab --}}
    @if($tab === 'categories')
    <div class="space-y-4">
        @forelse($this->categories as $category)
        <div class="rounded-xl border {{ $category->is_active ? 'border-zinc-200 dark:border-zinc-700' : 'border-zinc-100 dark:border-zinc-800 opacity-60' }} bg-white p-6 shadow-sm dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    @if($category->hasImage())
                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-16 w-24 rounded-lg object-cover">
                    @else
                    <div class="flex h-16 w-24 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                    </div>
                    @endif
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</h3>
                            @if(!$category->is_active)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">Inactive</span>
                            @endif
                        </div>
                        @if($category->description)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $category->description }}</p>
                        @endif
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $category->articles_count }} {{ Str::plural('article', $category->articles_count) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleCategoryActive({{ $category->id }})" class="text-sm {{ $category->is_active ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400' : 'text-green-600 hover:text-green-700 dark:text-green-400' }}">
                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button wire:click="editCategory({{ $category->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                        Edit
                    </button>
                    @if($category->articles_count === 0)
                    <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Are you sure you want to delete this category?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                        Delete
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No categories yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create categories to organize your learning content.</p>
            <button wire:click="editCategory()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Category
            </button>
        </div>
        @endforelse
    </div>
    @endif

    {{-- Articles Tab --}}
    @if($tab === 'articles')
    <div class="space-y-4">
        @forelse($this->articles as $article)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    @if($article->hasFeaturedImage())
                    <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="h-20 w-32 rounded-lg object-cover">
                    @else
                    <div class="flex h-20 w-32 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    @endif
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $article->title }}</h3>
                            @if($article->is_published)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Published</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">Draft</span>
                            @endif
                            @if($article->is_featured)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Featured</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                </svg>
                                {{ $article->category->name }}
                            </span>
                            @if($article->reading_time_minutes)
                            <span class="ml-3 inline-flex items-center gap-1">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ $article->reading_time_minutes }} min read
                            </span>
                            @endif
                            @if($article->pages_count > 0)
                            <span class="ml-3 inline-flex items-center gap-1">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                {{ $article->pages_count }} {{ Str::plural('page', $article->pages_count) }}
                            </span>
                            @endif
                        </p>
                        @if($article->excerpt)
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ $article->excerpt }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.learning.pages', $article) }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Pages{{ $article->pages_count > 0 ? " ({$article->pages_count})" : '' }}
                    </a>
                    <button wire:click="toggleArticlePublished({{ $article->id }})" class="text-sm {{ $article->is_published ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400' : 'text-green-600 hover:text-green-700 dark:text-green-400' }}">
                        {{ $article->is_published ? 'Unpublish' : 'Publish' }}
                    </button>
                    <button wire:click="toggleArticleFeatured({{ $article->id }})" class="text-sm {{ $article->is_featured ? 'text-zinc-600 hover:text-zinc-700 dark:text-zinc-400' : 'text-amber-600 hover:text-amber-700 dark:text-amber-400' }}">
                        {{ $article->is_featured ? 'Unfeature' : 'Feature' }}
                    </button>
                    <button wire:click="editArticle({{ $article->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                        Edit
                    </button>
                    <button wire:click="deleteArticle({{ $article->id }})" wire:confirm="Are you sure you want to delete this article?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
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
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No articles yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create articles to share knowledge with your members.</p>
            @if($this->categories->count() > 0)
            <button wire:click="editArticle()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Article
            </button>
            @else
            <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">Create a category first before adding articles.</p>
            @endif
        </div>
        @endforelse
    </div>
    @endif
</div>
