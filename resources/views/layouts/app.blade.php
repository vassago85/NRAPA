<x-layouts::app.sidebar :title="$title ?? null">
    <div class="max-w-7xl mx-auto">
        {{ $slot }}
    </div>
</x-layouts::app.sidebar>
