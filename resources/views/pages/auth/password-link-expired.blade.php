<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Link expired')"
            :description="__('This was a single-use link to set your password')" />

        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
            <p class="font-medium">This password link is no longer valid.</p>
            <p class="mt-2">
                The link in your email could only be used once to set your password, and it has now expired
                or already been used. For your security, we can’t reuse it.
            </p>
        </div>

        <div class="text-sm text-zinc-600 dark:text-zinc-400">
            <p>You can:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Request a fresh link to set your password, or</li>
                <li>Contact NRAPA at
                    <a href="mailto:info@nrapa.co.za" class="font-medium text-nrapa-blue hover:underline">info@nrapa.co.za</a>
                    and we’ll help you get signed in.
                </li>
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <a href="{{ route('password.request') }}"
               class="w-full rounded-lg bg-nrapa-blue px-4 py-2 text-center font-medium text-white transition-colors hover:bg-nrapa-blue-dark">
                {{ __('Send me a new link') }}
            </a>
            <a href="{{ route('login') }}"
               class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-center font-medium text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
