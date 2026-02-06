@props([
    'sidebar' => false,
])

<a href="{{ route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <img src="{{ asset('nrapa-logo.png') }}" alt="NRAPA" class="size-8 object-contain" />
    <span class="text-lg font-bold text-zinc-900 dark:text-white">NRAPA Members</span>
</a>
