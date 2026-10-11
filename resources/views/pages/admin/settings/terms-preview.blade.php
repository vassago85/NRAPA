<x-layouts::app :title="'Preview: '.$version->title">
    <div class="mx-auto max-w-4xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $version->title }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Version {{ $version->version }}
                    &bull; {{ $version->is_active ? 'Active' : 'Not active' }}
                    &bull; Published: {{ $version->published_at?->format('d F Y') ?? 'Not published' }}
                </p>
            </div>
            <a href="{{ route('admin.settings.terms') }}" wire:navigate
                class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                Back to terms
            </a>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            @if($html !== '')
                <div class="prose prose-zinc max-w-none dark:prose-invert">
                    {!! $html !!}
                </div>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">This version has no content yet.</p>
            @endif
        </div>
    </div>
</x-layouts::app>
