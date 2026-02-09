<?php

use App\Models\LearningCategory;
use App\Models\LearningArticle;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Category - Learning Center')] class extends Component {
    public LearningCategory $category;

    public function mount(LearningCategory $category): void
    {
        // Ensure category is active
        if (!$category->is_active) {
            abort(404);
        }
        $this->category = $category;
    }

    #[Computed]
    public function articles()
    {
        return $this->category->articles()
            ->published()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function readArticleIds()
    {
        return auth()->user()->learningArticlesRead()->pluck('learning_article_id')->toArray();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm">
        <a href="{{ route('learning.index') }}" wire:navigate class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Learning Center</a>
        <svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
        <span class="text-zinc-900 dark:text-white">{{ $category->name }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-start gap-6">
        @if($category->hasImage())
        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-24 w-36 rounded-xl object-cover shadow-lg">
        @else
        <div class="flex h-24 w-36 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg">
            <svg class="size-12 text-white/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>
        </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $category->name }}</h1>
            @if($category->description)
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $category->description }}</p>
            @endif
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->articles->count() }} {{ Str::plural('article', $this->articles->count()) }}</p>
        </div>
    </div>

    {{-- Articles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($this->articles as $article)
        <a href="{{ route('learning.show', $article) }}" wire:navigate class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:border-emerald-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-600">
            @if($article->hasFeaturedImage())
            <div class="aspect-video overflow-hidden">
                <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition group-hover:scale-105">
            </div>
            @else
            <div class="flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-700">
                <svg class="size-12 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            @endif
            <div class="p-4">
                <h3 class="font-semibold text-zinc-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">{{ $article->title }}</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $article->excerpt_or_summary }}</p>
                <div class="mt-3 flex items-center gap-3 text-xs text-zinc-400">
                    @if($article->reading_time_minutes)
                    <span class="inline-flex items-center gap-1">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ $article->reading_time_minutes }} min read
                    </span>
                    @endif
                </div>
            </div>
            @if(in_array($article->id, $this->readArticleIds))
            <div class="absolute right-2 top-2 rounded-full bg-emerald-500 p-1.5 shadow">
                <svg class="size-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            @endif
        </a>
        @empty
        <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <svg class="mx-auto size-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">No articles in this category</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Check back soon for new content.</p>
            <a href="{{ route('learning.index') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Learning Center
            </a>
        </div>
        @endforelse
    </div>
</div>
