@props([
    'sidebar' => false,
])

<a href="{{ route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-emerald-600 text-white">
        <x-app-logo-icon class="size-5 fill-current" />
    </div>
    <span class="text-lg font-bold text-zinc-900 dark:text-white">NRAPA Members</span>
</a>
