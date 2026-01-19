<?php

use App\Models\LearningArticle;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Article - Learning Center')] class extends Component {
    public LearningArticle $article;

    public function mount(LearningArticle $article): void
    {
        // Ensure article is published
        if (!$article->is_published) {
            abort(404);
        }

        $this->article = $article;

        // Mark as read
        $article->markAsReadBy(auth()->user());
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

    #[Computed]
    public function previousArticle()
    {
        return LearningArticle::published()
            ->where('learning_category_id', $this->article->learning_category_id)
            ->where('sort_order', '<', $this->article->sort_order)
            ->orderByDesc('sort_order')
            ->first();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
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
        {{-- Article Header --}}
        <article>
            @if($article->hasFeaturedImage())
            <div class="aspect-video overflow-hidden rounded-xl">
                <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
            </div>
            @endif

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
                </div>

                <h1 class="mt-4 text-3xl font-bold text-zinc-900 dark:text-white">{{ $article->title }}</h1>

                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($article->author)
                    <span class="inline-flex items-center gap-2">
                        <div class="flex size-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            {{ $article->author->initials() }}
                        </div>
                        {{ $article->author->name }}
                    </span>
                    @endif
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
        </article>

        {{-- Navigation --}}
        <div class="mt-12 flex items-center justify-between border-t border-zinc-200 pt-6 dark:border-zinc-700">
            @if($this->previousArticle)
            <a href="{{ route('learning.show', $this->previousArticle) }}" wire:navigate class="group flex items-center gap-3">
                <svg class="size-5 text-zinc-400 transition group-hover:-translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Previous</p>
                    <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ Str::limit($this->previousArticle->title, 40) }}</p>
                </div>
            </a>
            @else
            <div></div>
            @endif

            @if($this->nextArticle)
            <a href="{{ route('learning.show', $this->nextArticle) }}" wire:navigate class="group flex items-center gap-3 text-right">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Next</p>
                    <p class="text-sm font-medium text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ Str::limit($this->nextArticle->title, 40) }}</p>
                </div>
                <svg class="size-5 text-zinc-400 transition group-hover:translate-x-1 group-hover:text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
            @else
            <div></div>
            @endif
        </div>

        {{-- Related Articles --}}
        @if($this->relatedArticles->count() > 0)
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
