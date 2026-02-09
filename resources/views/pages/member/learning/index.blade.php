<?php

use App\Models\LearningCategory;
use App\Models\LearningArticle;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Learning Center')] class extends Component {
    public string $search = '';

    /**
     * Get the user's dedicated type from their active membership.
     */
    #[Computed]
    public function userDedicatedType(): ?string
    {
        return auth()->user()->activeMembership?->type?->dedicated_type;
    }

    #[Computed]
    public function categories()
    {
        return LearningCategory::active()
            ->forDedicatedType($this->userDedicatedType)
            ->ordered()
            ->withCount(['articles' => fn($q) => $q->published()->forDedicatedType($this->userDedicatedType)])
            ->get();
    }

    #[Computed]
    public function featuredArticles()
    {
        return LearningArticle::published()
            ->featured()
            ->forDedicatedType($this->userDedicatedType)
            ->with('category')
            ->latest()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function recentArticles()
    {
        return LearningArticle::published()
            ->forDedicatedType($this->userDedicatedType)
            ->with('category')
            ->latest()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return LearningArticle::published()
            ->forDedicatedType($this->userDedicatedType)
            ->with('category')
            ->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('excerpt', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            })
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function readArticleIds()
    {
        return auth()->user()->learningArticlesRead()->pluck('learning_article_id')->toArray();
    }
}; ?>

<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Learning Center</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Educational resources to help you stay informed and compliant</p>
        </div>
    </x-slot>

    <div class="flex flex-col gap-6">

    {{-- Search --}}
    <div class="mx-auto w-full max-w-xl">
        <div class="relative">
            <svg class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                class="w-full rounded-xl border border-zinc-300 bg-white py-3 pl-12 pr-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                placeholder="Search articles..."
            >
        </div>
    </div>

    {{-- Search Results --}}
    @if(strlen($search) >= 2)
    <div class="mx-auto w-full max-w-4xl">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Search Results</h2>
        @if($this->searchResults->count() > 0)
        <div class="space-y-4">
            @foreach($this->searchResults as $article)
            <a href="{{ route('learning.show', $article) }}" wire:navigate class="block rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-emerald-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-600">
                <div class="flex items-start gap-4">
                    @if($article->hasFeaturedImage())
                    <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="h-16 w-24 rounded-lg object-cover">
                    @endif
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ $article->category->name }}</span>
                            @if(in_array($article->id, $this->readArticleIds))
                            <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Read
                            </span>
                            @endif
                        </div>
                        <h3 class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $article->title }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $article->excerpt_or_summary }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-zinc-500 dark:text-zinc-400">No articles found matching "{{ $search }}"</p>
        </div>
        @endif
    </div>
    @else
    {{-- Featured Articles --}}
    @if($this->featuredArticles->count() > 0)
    <div>
        <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">Featured</h2>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach($this->featuredArticles as $article)
            <a href="{{ route('learning.show', $article) }}" wire:navigate class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                @if($article->hasFeaturedImage())
                <div class="aspect-video overflow-hidden">
                    <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition group-hover:scale-105">
                </div>
                @else
                <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-emerald-500 to-teal-600">
                    <svg class="size-12 text-white/50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                @endif
                <div class="p-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ $article->category->name }}</span>
                        @if($article->reading_time_minutes)
                        <span class="text-xs text-zinc-400">• {{ $article->reading_time_minutes }} min</span>
                        @endif
                    </div>
                    <h3 class="mt-2 font-semibold text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ $article->title }}</h3>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $article->excerpt_or_summary }}</p>
                </div>
                @if(in_array($article->id, $this->readArticleIds))
                <div class="absolute right-2 top-2 rounded-full bg-emerald-500 p-1">
                    <svg class="size-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Categories --}}
    <div>
        <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">Browse by Category</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($this->categories as $category)
            <a href="{{ route('learning.category', $category) }}" wire:navigate class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-emerald-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-600">
                @if($category->hasImage())
                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-16 w-24 rounded-lg object-cover">
                @else
                <div class="flex h-16 w-24 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="size-8 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                </div>
                @endif
                <div class="flex-1">
                    <h3 class="font-semibold text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ $category->name }}</h3>
                    @if($category->description)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $category->description }}</p>
                    @endif
                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $category->articles_count }} {{ Str::plural('article', $category->articles_count) }}</p>
                </div>
                <svg class="size-5 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
            @empty
            <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No content available yet</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Check back soon for educational resources.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Articles --}}
    @if($this->recentArticles->count() > 0)
    <div>
        <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">Recent Articles</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->recentArticles as $article)
            <a href="{{ route('learning.show', $article) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-emerald-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-600">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ $article->category->name }}</span>
                    @if(in_array($article->id, $this->readArticleIds))
                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Read
                    </span>
                    @endif
                </div>
                <h3 class="mt-2 font-semibold text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ $article->title }}</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $article->excerpt_or_summary }}</p>
                <div class="mt-3 flex items-center gap-3 text-xs text-zinc-400">
                    @if($article->reading_time_minutes)
                    <span class="inline-flex items-center gap-1">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ $article->reading_time_minutes }} min
                    </span>
                    @endif
                    @if($article->published_at)
                    <span>{{ $article->published_at->format('d M Y') }}</span>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @endif
    </div>
</div>
