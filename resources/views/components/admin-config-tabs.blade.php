@props(['current' => 'membership-types'])

@php
    $currentRoute = request()->route()?->getName() ?? '';
    $tabs = [
        ['id' => 'membership-types', 'label' => 'Membership Types', 'route' => 'admin.membership-types.index'],
        ['id' => 'clubs', 'label' => 'Affiliated Clubs', 'route' => 'admin.affiliated-clubs.index'],
        ['id' => 'activity-types', 'label' => 'Activity Types', 'route' => 'admin.activity-config.index'],
        ['id' => 'calibres', 'label' => 'Calibres', 'route' => 'admin.calibre-requests.index'],
        ['id' => 'firearm-settings', 'label' => 'Firearm Settings', 'route' => 'admin.firearm-settings.index'],
        ['id' => 'firearm-reference', 'label' => 'Firearm Reference', 'route' => 'admin.firearm-reference.index'],
        ['id' => 'bullet-database', 'label' => 'Bullet Database', 'route' => 'admin.bullet-database.index'],
    ];
@endphp

<div class="mb-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="px-4 py-2 bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800">
        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Configuration</span>
    </div>
    <nav class="flex overflow-x-auto scrollbar-none">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               wire:navigate
               class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                      {{ $currentRoute === $tab['route'] || (isset($current) && $current === $tab['id'])
                          ? 'border-nrapa-blue text-nrapa-blue bg-blue-50/50 dark:bg-blue-900/10'
                          : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
