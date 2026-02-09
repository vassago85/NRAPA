<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <script>
            // Apply theme immediately to prevent flash
            (function() {
                const theme = localStorage.getItem('theme') || 'system';
                if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-nrapa-blue-light dark:bg-zinc-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 font-medium mb-8" wire:navigate>
                    <img src="{{ asset('nrapa-logo.png') }}" alt="{{ config('app.name', 'NRAPA') }}" class="h-20 w-auto bg-white rounded-xl p-2 shadow-sm" />
                </a>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-nrapa-blue/10 dark:border-zinc-700 p-6">
                    {{ $slot }}
                </div>
                <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">&copy; {{ date('Y') }} {{ config('app.name', 'NRAPA') }}. All rights reserved.</p>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
