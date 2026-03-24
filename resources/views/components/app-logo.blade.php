<a href="{{ route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'inline-block']) }}>
    <img src="{{ asset('logo-nrapa-blue-text.png') }}" alt="NRAPA" class="h-10 w-auto object-contain dark:hidden" />
    <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" class="h-10 w-auto object-contain hidden dark:block" />
</a>
