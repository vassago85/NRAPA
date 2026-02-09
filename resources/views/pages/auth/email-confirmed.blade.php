<x-layouts::auth>
    <div class="mt-4 flex flex-col gap-6">
        <div class="flex flex-col items-center gap-4">
            <div class="flex size-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <svg class="size-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white text-center">
                {{ __('Email address confirmed') }}
            </h2>

            <p class="text-center text-zinc-600 dark:text-zinc-400">
                {{ __('Your email address has been successfully verified. You can now log in to your account.') }}
            </p>
        </div>

        <div class="flex flex-col items-center">
            <a href="{{ route('login') }}"
               class="w-full text-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors">
                {{ __('Go to login') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
