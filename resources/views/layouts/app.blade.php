<x-layouts::app.sidebar :title="$title ?? null">
    @if(isset($header))
    <x-slot name="header">{{ $header }}</x-slot>
    @endif
    {{ $slot }}
</x-layouts::app.sidebar>
