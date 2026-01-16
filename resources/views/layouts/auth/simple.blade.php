<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium mb-8" wire:navigate>
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-lg">
                        <svg class="size-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-zinc-900 dark:text-white">{{ config('app.name', 'NRAPA') }}</span>
                </a>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
