<?php

use App\Models\LearningArticle;
use App\Models\LearningArticlePage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Article - Learning Center')] class extends Component {
    public LearningArticle $article;
    public ?LearningArticlePage $currentPage = null;
    public int $pageNumber = 0; // 0 = intro, 1+ = page numbers

    public function mount(LearningArticle $article, ?int $page = null): void
    {
        // Ensure article is published
        if (!$article->is_published) {
            abort(404);
        }

        $this->article = $article->load('pages');

        // If article has pages, handle page navigation
        if ($article->hasPages()) {
            if ($page === null || $page === 0) {
                // Show intro
                $this->pageNumber = 0;
                $this->currentPage = null;
            } else {
                // Show specific page
                $this->currentPage = $article->pages()->where('page_number', $page)->first();
                if (!$this->currentPage) {
                    abort(404);
                }
                $this->pageNumber = $page;
                // Mark page as read
                $this->currentPage->markAsReadBy(auth()->user());
            }
        } else {
            // No pages, just mark article as read
            $article->markAsReadBy(auth()->user());
        }
    }

    public function goToPage(int $page): void
    {
        if ($page === 0) {
            $this->pageNumber = 0;
            $this->currentPage = null;
        } else {
            $this->currentPage = $this->article->pages()->where('page_number', $page)->first();
            if ($this->currentPage) {
                $this->pageNumber = $page;
                $this->currentPage->markAsReadBy(auth()->user());
            }
        }
    }

    public function nextPage(): void
    {
        if ($this->pageNumber === 0 && $this->article->hasPages()) {
            // Go from intro to first page
            $this->goToPage(1);
        } elseif ($this->currentPage && $this->currentPage->next_page) {
            $this->goToPage($this->currentPage->next_page->page_number);
        }
    }

    public function previousPage(): void
    {
        if ($this->pageNumber === 1) {
            // Go back to intro
            $this->goToPage(0);
        } elseif ($this->currentPage && $this->currentPage->previous_page) {
            $this->goToPage($this->currentPage->previous_page->page_number);
        }
    }

    #[Computed]
    public function totalPages(): int
    {
        return $this->article->pages()->count();
    }

    #[Computed]
    public function completionPercentage(): int
    {
        return $this->article->getCompletionPercentageFor(auth()->user());
    }

    #[Computed]
    public function isComplete(): bool
    {
        return $this->article->isCompletedBy(auth()->user());
    }

    #[Computed]
    public function relatedArticles()
    {
        return LearningArticle::published()
            ->where('learning_category_id', $this->article->learning_category_id)
            ->where('id', '!=', $this->article->id)
            ->ordered()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function nextArticle()
    {
        return LearningArticle::published()
            ->where('learning_category_id', $this->article->learning_category_id)
            ->where('sort_order', '>', $this->article->sort_order)
            ->orderBy('sort_order')
            ->first();
    }
}; ?>

<div class="flex flex-col gap-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm">
        <a href="{{ route('learning.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Learning Center</a>
        <svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
        <a href="{{ route('learning.category', $article->category) }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">{{ $article->category->name }}</a>
        <svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
        <span class="text-zinc-900 dark:text-white line-clamp-1">{{ $article->title }}</span>
    </nav>

    <div class="mx-auto w-full max-w-4xl">
        {{-- Progress Bar (for multi-page articles) --}}
        @if($this->totalPages > 0)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">
                    @if($pageNumber === 0)
                        Introduction
                    @else
                        Page {{ $pageNumber }} of {{ $this->totalPages }}
                    @endif
                </span>
                <span class="font-medium {{ $this->isComplete ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                    @if($this->isComplete)
                        <span class="inline-flex items-center gap-1">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Complete
                        </span>
                    @else
                        {{ $this->completionPercentage }}% complete
                    @endif
                </span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $this->completionPercentage }}%"></div>
            </div>
            {{-- Page dots --}}
            <div class="mt-3 flex items-center justify-center gap-2">
                <button wire:click="goToPage(0)" class="flex size-8 items-center justify-center rounded-full text-xs font-medium transition {{ $pageNumber === 0 ? 'bg-emerald-500 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-600' }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </button>
                @foreach($article->pages as $p)
                @php
                    $isRead = $p->isReadBy(auth()->user());
                @endphp
                <button wire:click="goToPage({{ $p->page_number }})" class="flex size-8 items-center justify-center rounded-full text-xs font-medium transition {{ $pageNumber === $p->page_number ? 'bg-emerald-500 text-white' : ($isRead ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-600') }}">
                    {{ $p->page_number }}
                </button>
                @endforeach
            </div>
        </div>
        @endif

        <article>
            {{-- Intro Page (pageNumber === 0) or Single Article --}}
            @if($pageNumber === 0)
                <div class="aspect-video overflow-hidden rounded-xl">
                    <img src="{{ $article->display_image_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                </div>

                <header class="mt-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('learning.category', $article->category) }}" wire:navigate class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:hover:bg-emerald-900">
                            {{ $article->category->name }}
                        </a>
                        @if($article->is_featured)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                            Featured
                        </span>
                        @endif
                        @if($this->totalPages > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            {{ $this->totalPages }} {{ Str::plural('page', $this->totalPages) }}
                        </span>
                        @endif
                    </div>

                    <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white">{{ $article->title }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <span class="inline-flex items-center gap-2">
                            <div class="flex size-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                {{ substr($article->author_name, 0, 1) }}
                            </div>
                            {{ $article->author_name }}
                        </span>
                        @if($article->published_at)
                        <span class="inline-flex items-center gap-1">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            {{ $article->published_at->format('d M Y') }}
                        </span>
                        @endif
                        @if($article->reading_time_minutes)
                        <span class="inline-flex items-center gap-1">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ $article->reading_time_minutes }} min read
                        </span>
                        @endif
                    </div>

                    @if($article->excerpt)
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-300">{{ $article->excerpt }}</p>
                    @endif
                </header>

                {{-- Article Content --}}
                <div class="prose prose-zinc mt-8 max-w-none dark:prose-invert prose-headings:font-semibold prose-a:text-emerald-600 dark:prose-a:text-emerald-400 prose-img:rounded-xl">
                    {!! $article->content !!}
                </div>

            {{-- Page Content --}}
            @else
                <header class="mb-8">
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Page {{ $pageNumber }} of {{ $this->totalPages }}</p>
                    <h1 class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ $currentPage->title }}</h1>
                </header>

                <figure class="mb-8">
                    <img src="{{ $currentPage->display_image_url }}" alt="{{ $currentPage->title }}" class="w-full rounded-xl object-contain max-h-96">
                    @if($currentPage->image_caption)
                    <figcaption class="mt-2 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ $currentPage->image_caption }}</figcaption>
                    @endif
                </figure>

                <div class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-semibold prose-a:text-emerald-600 dark:prose-a:text-emerald-400 prose-img:rounded-xl">
                    {!! $currentPage->content !!}
                </div>
            @endif
        </article>

        {{-- Navigation --}}
        <div class="mt-12 flex items-center justify-between border-t border-zinc-200 pt-6 dark:border-zinc-700">
            @if($this->totalPages > 0)
                {{-- Multi-page navigation --}}
                @if($pageNumber > 0)
                <button wire:click="previousPage" class="group flex items-center gap-3">
                    <svg class="size-5 text-zinc-400 transition group-hover:-translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Previous</p>
                        <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">
                            @if($pageNumber === 1)
                                Introduction
                            @else
                                {{ $currentPage->previous_page?->title ?? 'Previous Page' }}
                            @endif
                        </p>
                    </div>
                </button>
                @else
                <div></div>
                @endif

                @if($pageNumber === 0 || ($currentPage && $currentPage->next_page))
                <button wire:click="nextPage" class="group flex items-center gap-3 text-right">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Next</p>
                        <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">
                            @if($pageNumber === 0)
                                {{ $article->pages->first()?->title ?? 'Start Reading' }}
                            @else
                                {{ $currentPage->next_page?->title ?? 'Next Page' }}
                            @endif
                        </p>
                    </div>
                    <svg class="size-5 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
                @elseif($this->nextArticle)
                {{-- Last page, show next article --}}
                <a href="{{ route('learning.show', $this->nextArticle) }}" wire:navigate class="group flex items-center gap-3 text-right">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Next Article</p>
                        <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ Str::limit($this->nextArticle->title, 40) }}</p>
                    </div>
                    <svg class="size-5 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
                @else
                {{-- Completed all pages, no next article --}}
                <div class="flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 dark:bg-emerald-900/20">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Article Complete!</span>
                </div>
                @endif
            @else
                {{-- Single article navigation (no pages) --}}
                <div></div>
                @if($this->nextArticle)
                <a href="{{ route('learning.show', $this->nextArticle) }}" wire:navigate class="group flex items-center gap-3 text-right">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Next Article</p>
                        <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ Str::limit($this->nextArticle->title, 40) }}</p>
                    </div>
                    <svg class="size-5 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
                @else
                <div></div>
                @endif
            @endif
        </div>

        {{-- Related Articles --}}
        @if($this->relatedArticles->count() > 0 && ($pageNumber === 0 || ($this->totalPages > 0 && $currentPage && !$currentPage->next_page)))
        <div class="mt-12">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">More in {{ $article->category->name }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach($this->relatedArticles as $related)
                <a href="{{ route('learning.show', $related) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-600">
                    <h3 class="font-semibold text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400 line-clamp-2">{{ $related->title }}</h3>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $related->excerpt_or_summary }}</p>
                    @if($related->reading_time_minutes)
                    <p class="mt-2 text-xs text-zinc-400">{{ $related->reading_time_minutes }} min read</p>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
