<?php

use App\Helpers\StorageHelper;
use App\Models\LearningCategory;
use App\Models\LearningArticle;
use App\Services\WordDocumentConverter;
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
    public $articleDocument = null;
    public ?string $existingArticleDocument = null;
    public bool $removeArticleDocument = false;
    public bool $articleIsFeatured = false;

    // JSON Import/Export
    public $jsonImportFile = null;
    public bool $showJsonImportModal = false;

    // Word Document Import
    public $wordDocumentFile = null;
    public bool $showWordImportModal = false;
    public string $wordDefaultCategory = '';
    public string $wordDedicatedType = 'both';

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
            StorageHelper::deleteLearningCenterFile($this->existingCategoryImage);
            $imagePath = null;
        }

        if ($this->categoryImage) {
            if ($this->existingCategoryImage) {
                StorageHelper::deleteLearningCenterFile($this->existingCategoryImage);
            }
            $imagePath = StorageHelper::storeLearningCenterFile($this->categoryImage, 'learning/categories');
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

    public function deleteCategory(int $id, ?int $moveToCategoryId = null): void
    {
        $category = LearningCategory::with(['articles.pages', 'articles.images'])->findOrFail($id);
        $articleCount = $category->articles->count();

        // Move articles to another category if specified
        if ($articleCount > 0 && $moveToCategoryId) {
            $targetCategory = LearningCategory::findOrFail($moveToCategoryId);
            $category->articles()->update(['learning_category_id' => $moveToCategoryId]);
            session()->flash('success', "Category deleted successfully. {$articleCount} article(s) moved to '{$targetCategory->name}'.");
        } elseif ($articleCount > 0) {
            // Delete all articles in this category
            foreach ($category->articles as $article) {
                // Delete article images and documents
                if ($article->featured_image) {
                    StorageHelper::deleteLearningCenterFile($article->featured_image);
                }
                if ($article->document_path) {
                    StorageHelper::deleteLearningCenterFile($article->document_path);
                }
                // Delete article pages and their images
                foreach ($article->pages as $page) {
                    if ($page->image_path) {
                        StorageHelper::deleteLearningCenterFile($page->image_path);
                    }
                }
                // Delete article images
                foreach ($article->images as $image) {
                    StorageHelper::deleteLearningCenterFile($image->path);
                }
                $article->delete();
            }
            session()->flash('success', "Category and {$articleCount} article(s) deleted successfully.");
        } else {
            session()->flash('success', 'Category deleted successfully.');
        }

        // Delete category image if exists
        if ($category->image_path) {
            StorageHelper::deleteLearningCenterFile($category->image_path);
        }

        $category->delete();
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
            $this->existingArticleDocument = $article->document_path;
            $this->articleIsFeatured = $article->is_featured;
        } else {
            $this->editingArticleId = 0;
            $this->articleCategoryId = $this->categories->first()?->id;
            $this->articleTitle = '';
            $this->articleExcerpt = '';
            $this->articleContent = '';
            $this->existingArticleFeaturedImage = null;
            $this->existingArticleDocument = null;
            $this->articleIsFeatured = false;
        }
        $this->articleFeaturedImage = null;
        $this->removeArticleFeaturedImage = false;
        $this->articleDocument = null;
        $this->removeArticleDocument = false;
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
        $this->articleDocument = null;
        $this->existingArticleDocument = null;
        $this->removeArticleDocument = false;
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
            'articleDocument' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $imagePath = $this->existingArticleFeaturedImage;

        if ($this->removeArticleFeaturedImage && $this->existingArticleFeaturedImage) {
            StorageHelper::deleteLearningCenterFile($this->existingArticleFeaturedImage);
            $imagePath = null;
        }

        if ($this->articleFeaturedImage) {
            if ($this->existingArticleFeaturedImage) {
                StorageHelper::deleteLearningCenterFile($this->existingArticleFeaturedImage);
            }
            $imagePath = StorageHelper::storeLearningCenterFile($this->articleFeaturedImage, 'learning/articles');
        }

        $documentPath = $this->existingArticleDocument;

        if ($this->removeArticleDocument && $this->existingArticleDocument) {
            StorageHelper::deleteLearningCenterFile($this->existingArticleDocument);
            $documentPath = null;
        }

        if ($this->articleDocument) {
            if ($this->existingArticleDocument) {
                StorageHelper::deleteLearningCenterFile($this->existingArticleDocument);
            }
            $documentPath = StorageHelper::storeLearningCenterFile($this->articleDocument, 'learning/articles/documents');
        }

        $data = [
            'learning_category_id' => $this->articleCategoryId,
            'title' => $this->articleTitle,
            'slug' => Str::slug($this->articleTitle),
            'excerpt' => $this->articleExcerpt ?: null,
            'content' => $this->articleContent,
            'featured_image' => $imagePath,
            'document_path' => $documentPath,
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
            StorageHelper::deleteLearningCenterFile($article->featured_image);
        }

        if ($article->document_path) {
            StorageHelper::deleteLearningCenterFile($article->document_path);
        }

        // Delete article images
        foreach ($article->images as $image) {
            StorageHelper::deleteLearningCenterFile($image->path);
        }

        // Delete page images
        foreach ($article->pages as $page) {
            if ($page->image_path) {
                StorageHelper::deleteLearningCenterFile($page->image_path);
            }
        }

        $article->delete();
        session()->flash('success', 'Article deleted successfully.');
    }

    // JSON Import/Export Methods
    public function openJsonImportModal(): void
    {
        $this->showJsonImportModal = true;
        $this->jsonImportFile = null;
    }

    public function closeJsonImportModal(): void
    {
        $this->showJsonImportModal = false;
        $this->jsonImportFile = null;
    }

    public function openWordImportModal(): void
    {
        $this->showWordImportModal = true;
        $this->wordDocumentFile = null;
        $this->wordDefaultCategory = $this->categories->first()?->name ?? '';
        $this->wordDedicatedType = 'both';
    }

    public function closeWordImportModal(): void
    {
        $this->showWordImportModal = false;
        $this->wordDocumentFile = null;
        $this->wordDefaultCategory = '';
        $this->wordDedicatedType = 'both';
    }

    public function importFromWord(): void
    {
        $this->validate([
            'wordDocumentFile' => ['required', 'file', 'mimes:docx,doc', 'max:20480'],
            'wordDefaultCategory' => ['required', 'string', 'max:255'],
            'wordDedicatedType' => ['required', 'in:hunter,sport,both'],
        ]);

        try {
            $converter = app(WordDocumentConverter::class);
            $data = $converter->convertToArticles(
                $this->wordDocumentFile->getRealPath(),
                $this->wordDefaultCategory,
                $this->wordDedicatedType === 'both' ? null : $this->wordDedicatedType
            );

            if (empty($data['articles'])) {
                session()->flash('error', 'No articles could be extracted from the Word document.');
                return;
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data['articles'] as $index => $articleData) {
                try {
                    // Validate required fields
                    if (empty($articleData['title']) || empty($articleData['category'])) {
                        $skipped++;
                        $errors[] = "Article #{$index}: Missing title or category";
                        continue;
                    }

                    // Find or create category
                    $category = LearningCategory::where('slug', Str::slug($articleData['category']))
                        ->orWhere('name', $articleData['category'])
                        ->first();

                    if (!$category) {
                        $category = LearningCategory::create([
                            'name' => $articleData['category'],
                            'slug' => Str::slug($articleData['category']),
                            'description' => $articleData['category_description'] ?? null,
                            'sort_order' => LearningCategory::max('sort_order') + 1,
                            'is_active' => true,
                        ]);
                    }

                    // Check if article already exists
                    $existing = LearningArticle::where('slug', Str::slug($articleData['title']))->first();
                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Create article
                    LearningArticle::create([
                        'learning_category_id' => $category->id,
                        'author_id' => auth()->id(),
                        'title' => $articleData['title'],
                        'slug' => Str::slug($articleData['title']),
                        'excerpt' => $articleData['excerpt'] ?? null,
                        'content' => $articleData['content'] ?? $articleData['title'],
                        'is_published' => $articleData['is_published'] ?? true,
                        'is_featured' => $articleData['is_featured'] ?? false,
                        'dedicated_type' => $articleData['dedicated_type'] ?? null,
                        'sort_order' => LearningArticle::max('sort_order') + 1,
                        'published_at' => isset($articleData['published_at']) ? now()->parse($articleData['published_at']) : now(),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Article #{$index}: " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} article(s) from Word document";
            if ($skipped > 0) {
                $message .= ", skipped {$skipped}";
            }
            if (!empty($errors) && count($errors) <= 5) {
                $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));
            }

            session()->flash('success', $message);
            $this->closeWordImportModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Word document import failed: ' . $e->getMessage());
        }
    }

    public function importFromJson(): void
    {
        $this->validate([
            'jsonImportFile' => ['required', 'file', 'mimes:json,txt', 'max:5120'],
        ]);

        try {
            $content = file_get_contents($this->jsonImportFile->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                session()->flash('error', 'Invalid JSON file: ' . json_last_error_msg());
                return;
            }

            if (!isset($data['articles']) || !is_array($data['articles'])) {
                session()->flash('error', 'JSON file must contain an "articles" array.');
                return;
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data['articles'] as $index => $articleData) {
                try {
                    // Validate required fields
                    if (empty($articleData['title']) || empty($articleData['category'])) {
                        $skipped++;
                        $errors[] = "Article #{$index}: Missing title or category";
                        continue;
                    }

                    // Find or create category
                    $category = LearningCategory::where('slug', Str::slug($articleData['category']))
                        ->orWhere('name', $articleData['category'])
                        ->first();

                    if (!$category) {
                        $category = LearningCategory::create([
                            'name' => $articleData['category'],
                            'slug' => Str::slug($articleData['category']),
                            'description' => $articleData['category_description'] ?? null,
                            'sort_order' => LearningCategory::max('sort_order') + 1,
                            'is_active' => true,
                        ]);
                    }

                    // Check if article already exists
                    $existing = LearningArticle::where('slug', Str::slug($articleData['title']))->first();
                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Create article
                    LearningArticle::create([
                        'learning_category_id' => $category->id,
                        'author_id' => auth()->id(),
                        'title' => $articleData['title'],
                        'slug' => Str::slug($articleData['title']),
                        'excerpt' => $articleData['excerpt'] ?? null,
                        'content' => $articleData['content'] ?? $articleData['title'],
                        'is_published' => $articleData['is_published'] ?? false,
                        'is_featured' => $articleData['is_featured'] ?? false,
                        'dedicated_type' => $articleData['dedicated_type'] ?? null,
                        'sort_order' => LearningArticle::max('sort_order') + 1,
                        'published_at' => isset($articleData['published_at']) ? now()->parse($articleData['published_at']) : null,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Article #{$index}: " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} article(s)";
            if ($skipped > 0) {
                $message .= ", skipped {$skipped}";
            }
            if (!empty($errors) && count($errors) <= 5) {
                $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));
            }

            session()->flash('success', $message);
            $this->closeJsonImportModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function exportToJson()
    {
        $articles = LearningArticle::with(['category', 'author'])->get();

        $data = [
            'export_date' => now()->toIso8601String(),
            'version' => '1.0',
            'articles' => $articles->map(function ($article) {
                return [
                    'title' => $article->title,
                    'category' => $article->category->name,
                    'category_description' => $article->category->description,
                    'excerpt' => $article->excerpt,
                    'content' => $article->content,
                    'is_published' => $article->is_published,
                    'is_featured' => $article->is_featured,
                    'dedicated_type' => $article->dedicated_type,
                    'published_at' => $article->published_at?->toIso8601String(),
                ];
            })->toArray(),
        ];

        $filename = 'learning-articles-export-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
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
            <button wire:click="editCategory()" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Category
            </button>
            <button wire:click="editArticle()" class="inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Article
            </button>
            <button wire:click="openWordImportModal" class="inline-flex items-center gap-2 rounded-lg border border-purple-300 bg-white px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50 dark:border-purple-600 dark:bg-zinc-700 dark:text-purple-400 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Import Word Doc
            </button>
            <button wire:click="openJsonImportModal" class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-600 dark:bg-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                Import JSON
            </button>
            <button wire:click="exportToJson" class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-600 dark:bg-zinc-700 dark:text-emerald-400 dark:hover:bg-zinc-600 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export JSON
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Categories</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['categories'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Articles</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['articles'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Published</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->stats['published'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Featured</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['featured'] }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Category Form --}}
    @if($editingCategoryId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                        <img src="{{ StorageHelper::getLearningCenterUrl($existingCategoryImage) }}" alt="Current" class="h-24 w-40 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('removeCategoryImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
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
                                    const input = this.$refs.categoryImageInput;
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
                        class="flex h-24 w-40 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                    >
                        <label class="cursor-pointer text-center">
                            <svg class="mx-auto size-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-1 text-xs text-zinc-500">Drop or click</p>
                            <input x-ref="categoryImageInput" type="file" wire:model="categoryImage" class="hidden" accept="image/*">
                        </label>
                    </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                    {{ $editingCategoryId ? 'Update Category' : 'Create Category' }}
                </button>
                <button type="button" wire:click="cancelCategoryEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Article Form --}}
    @if($editingArticleId !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                        <img src="{{ StorageHelper::getLearningCenterUrl($existingArticleFeaturedImage) }}" alt="Current" class="h-32 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="$set('removeArticleFeaturedImage', true)" class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600">
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
                                    const input = this.$refs.articleImageInput;
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
                        class="flex h-32 w-full max-w-md cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                    >
                        <label class="cursor-pointer text-center">
                            <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            <p class="mt-2 text-sm text-zinc-500">Drop or click to upload featured image</p>
                            <p class="text-xs text-zinc-400">PNG, JPG up to 5MB</p>
                            <input x-ref="articleImageInput" type="file" wire:model="articleFeaturedImage" class="hidden" accept="image/*">
                        </label>
                    </div>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Document File (Optional)</label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Upload a PDF, DOC, or DOCX file to attach to this article</p>
                <div class="mt-2">
                    @if($articleDocument)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $articleDocument->getClientOriginalName() }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($articleDocument->getSize() / 1024, 2) }} KB</p>
                        </div>
                        <button type="button" wire:click="$set('articleDocument', null)" class="rounded-lg bg-red-500 p-1.5 text-white hover:bg-red-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @elseif($existingArticleDocument && !$removeArticleDocument)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <svg class="size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ basename($existingArticleDocument) }}</p>
                            <a href="{{ StorageHelper::getLearningCenterUrl($existingArticleDocument) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400">View Document</a>
                        </div>
                        <button type="button" wire:click="$set('removeArticleDocument', true)" class="rounded-lg bg-red-500 p-1.5 text-white hover:bg-red-600">
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
                                    const input = this.$refs.articleDocumentInput;
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
                            <input x-ref="articleDocumentInput" type="file" wire:model="articleDocument" class="hidden" accept=".pdf,.doc,.docx">
                        </label>
                    </div>
                    @endif
                </div>
                @error('articleDocument') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Content</label>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">You can use HTML tags for formatting. Use &lt;img&gt; tags to embed images.</p>
                <textarea wire:model="articleContent" rows="12" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 font-mono text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" placeholder="<p>Article content goes here...</p>"></textarea>
                @error('articleContent') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                    {{ $editingArticleId ? 'Update Article' : 'Create Article' }}
                </button>
                <button type="button" wire:click="cancelArticleEdit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">
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
        <div class="rounded-xl border {{ $category->is_active ? 'border-zinc-200 dark:border-zinc-700' : 'border-zinc-100 dark:border-zinc-800 opacity-60' }} bg-white p-6 dark:bg-zinc-800">
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
                    <button wire:click="toggleCategoryActive({{ $category->id }})" class="text-sm transition-colors {{ $category->is_active ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400' : 'text-emerald-600 hover:text-emerald-700 dark:text-emerald-400' }}">
                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button wire:click="editCategory({{ $category->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors">
                        Edit
                    </button>
                    @if($category->articles_count > 0)
                    <div x-data="{ showDeleteModal: false, moveToCategoryId: '', deleteMode: 'delete_all' }" class="relative">
                        <button @click="showDeleteModal = true" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 transition-colors">
                            Delete ({{ $category->articles_count }} articles)
                        </button>
                        <div x-show="showDeleteModal" x-cloak @click.away="showDeleteModal = false" class="absolute right-0 top-full mt-2 z-10 w-72 rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-3 font-medium">This category has {{ $category->articles_count }} article(s).</p>
                            
                            <div class="mb-3 space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" x-model="deleteMode" value="delete_all" class="text-red-600 focus:ring-red-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Delete category and all articles</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" x-model="deleteMode" value="move" class="text-red-600 focus:ring-red-500">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Move articles to another category</span>
                                </label>
                            </div>
                            
                            <div x-show="deleteMode === 'move'" class="mb-3">
                                <select x-model="moveToCategoryId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">Select category...</option>
                                    @foreach($this->categories->where('id', '!=', $category->id) as $otherCategory)
                                    <option value="{{ $otherCategory->id }}">{{ $otherCategory->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex gap-2">
                                <button @click="showDeleteModal = false" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">
                                    Cancel
                                </button>
                                <button 
                                    @click="if(deleteMode === 'delete_all') { if(confirm('Are you sure? This will permanently delete the category and all {{ $category->articles_count }} articles.')) { $wire.deleteCategory({{ $category->id }}); showDeleteModal = false; } } else if(deleteMode === 'move' && moveToCategoryId) { $wire.deleteCategory({{ $category->id }}, parseInt(moveToCategoryId)); showDeleteModal = false; }" 
                                    :disabled="deleteMode === 'move' && !moveToCategoryId" 
                                    class="flex-1 rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <span x-text="deleteMode === 'delete_all' ? 'Delete All' : 'Delete & Move'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @else
                    <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Are you sure you want to delete this category?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 transition-colors">
                        Delete
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No categories yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create categories to organize your learning content.</p>
            <button wire:click="editCategory()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
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
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Published</span>
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
                    <a href="{{ route('admin.learning.pages', $article) }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors">
                        Pages{{ $article->pages_count > 0 ? " ({$article->pages_count})" : '' }}
                    </a>
                    <button wire:click="toggleArticlePublished({{ $article->id }})" class="text-sm transition-colors {{ $article->is_published ? 'text-amber-600 hover:text-amber-700 dark:text-amber-400' : 'text-emerald-600 hover:text-emerald-700 dark:text-emerald-400' }}">
                        {{ $article->is_published ? 'Unpublish' : 'Publish' }}
                    </button>
                    <button wire:click="toggleArticleFeatured({{ $article->id }})" class="text-sm transition-colors {{ $article->is_featured ? 'text-zinc-600 hover:text-zinc-700 dark:text-zinc-400' : 'text-amber-600 hover:text-amber-700 dark:text-amber-400' }}">
                        {{ $article->is_featured ? 'Unfeature' : 'Feature' }}
                    </button>
                    <button wire:click="editArticle({{ $article->id }})" class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors">
                        Edit
                    </button>
                    <button wire:click="deleteArticle({{ $article->id }})" wire:confirm="Are you sure you want to delete this article?" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No articles yet</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create articles to share knowledge with your members.</p>
            @if($this->categories->count() > 0)
            <button wire:click="editArticle()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
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

    {{-- JSON Import Modal --}}
    @if($showJsonImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showJsonImportModal') }" x-show="open" x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="closeJsonImportModal" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Import Articles from JSON</h2>
                    <button wire:click="closeJsonImportModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>JSON Format:</strong> Your JSON file should contain an "articles" array. Each article should have:
                            <code class="block mt-2 p-2 bg-white dark:bg-zinc-800 rounded text-xs font-mono">title, category, content, excerpt (optional), is_published (optional), is_featured (optional), dedicated_type (optional)</code>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select JSON File</label>
                        <div 
                            x-data="{ 
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        const input = this.$refs.jsonFileInput;
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
                            :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragging }"
                            class="flex h-32 w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                        >
                            <label class="cursor-pointer text-center">
                                <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-2 text-sm text-zinc-500">Drop or click to upload JSON file</p>
                                <p class="text-xs text-zinc-400">JSON, TXT up to 5MB</p>
                                <input x-ref="jsonFileInput" type="file" wire:model="jsonImportFile" class="hidden" accept=".json,.txt">
                            </label>
                        </div>
                        @if($jsonImportFile)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span class="flex-1 text-sm text-zinc-900 dark:text-white">{{ $jsonImportFile->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('jsonImportFile', null)" class="text-red-500 hover:text-red-700">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        @endif
                        @error('jsonImportFile') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="importFromJson" class="flex-1 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                            Import Articles
                        </button>
                        <button wire:click="closeJsonImportModal" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Word Document Import Modal --}}
    @if($showWordImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showWordImportModal') }" x-show="open" x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div wire:click="closeWordImportModal" class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Import Articles from Word Document</h2>
                    <button wire:click="closeWordImportModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            <strong>Word Document Import:</strong> Upload a Word document (.docx or .doc) and it will be automatically converted into learning articles. The system will detect headings and create separate articles for each section.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Default Category</label>
                        <select wire:model="wordDefaultCategory" class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @foreach($this->categories as $category)
                            <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('wordDefaultCategory') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Dedicated Type</label>
                        <select wire:model="wordDedicatedType" class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <option value="both">Both (Hunter & Sport Shooter)</option>
                            <option value="hunter">Dedicated Hunter Only</option>
                            <option value="sport">Dedicated Sport Shooter Only</option>
                        </select>
                        @error('wordDedicatedType') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Select Word Document</label>
                        <div 
                            x-data="{ 
                                dragging: false,
                                handleDrop(e) {
                                    this.dragging = false;
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        const input = this.$refs.wordFileInput;
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
                            :class="{ 'border-purple-500 bg-purple-50 dark:bg-purple-900/20': dragging }"
                            class="flex h-32 w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-800 transition-colors"
                        >
                            <label class="cursor-pointer text-center">
                                <svg class="mx-auto size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="mt-2 text-sm text-zinc-500">Drop or click to upload Word document</p>
                                <p class="text-xs text-zinc-400">DOCX, DOC up to 20MB</p>
                                <input x-ref="wordFileInput" type="file" wire:model="wordDocumentFile" class="hidden" accept=".docx,.doc">
                            </label>
                        </div>
                        @if($wordDocumentFile)
                        <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <svg class="size-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span class="flex-1 text-sm text-zinc-900 dark:text-white">{{ $wordDocumentFile->getClientOriginalName() }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($wordDocumentFile->getSize() / 1024, 2) }} KB</span>
                            <button type="button" wire:click="$set('wordDocumentFile', null)" class="text-red-500 hover:text-red-700">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        @endif
                        @error('wordDocumentFile') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="importFromWord" class="flex-1 rounded-lg bg-nrapa-blue px-4 py-2 text-sm font-medium text-white hover:bg-nrapa-blue-dark transition-colors">
                            Convert & Import Articles
                        </button>
                        <button wire:click="closeWordImportModal" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
