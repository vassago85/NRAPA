@props(['current' => 'articles'])

@php
    $currentRoute = request()->route()?->getName() ?? '';
    $tabs = [
        ['id' => 'articles', 'label' => 'Learning Center', 'route' => 'admin.learning.index'],
        ['id' => 'tests', 'label' => 'Knowledge Tests', 'route' => 'admin.knowledge-tests.index'],
    ];
@endphp

<div class="mb-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="px-4 py-2 bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Learning & Compliance</span>
    </div>
    <nav class="flex overflow-x-auto scrollbar-none">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               wire:navigate
               class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                      {{ str_starts_with($currentRoute, $tab['route']) || $current === $tab['id']
                          ? 'border-nrapa-blue text-nrapa-blue bg-blue-50/50 dark:bg-blue-900/10'
                          : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
