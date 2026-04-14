<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-xl font-semibold text-nrapa-blue dark:text-white">{{ __('Create an account') }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Enter your details below to create your account') }}</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="p-3 text-sm text-center text-emerald-700 bg-emerald-100 dark:bg-emerald-900/50 dark:text-emerald-300 rounded-xl">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4" x-data="{ submitted: false }" x-on:submit="submitted = true">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Name') }}</label>
                <input 
                    type="text" 
                    name="name" 
                    id="name"
                    value="{{ old('name') }}"
                    required 
                    autofocus 
                    autocomplete="name"
                    placeholder="{{ __('Full name') }}"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Email address') }}</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email"
                    value="{{ old('email') }}"
                    required 
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Phone number') }}</label>
                <input 
                    type="tel" 
                    name="phone" 
                    id="phone"
                    value="{{ old('phone') }}"
                    required 
                    autocomplete="tel"
                    placeholder="e.g. 082 123 4567"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Password') }}</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    required 
                    autocomplete="new-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Requirements -->
            <div class="rounded-xl bg-nrapa-blue-light dark:bg-zinc-700/50 border border-nrapa-blue/10 dark:border-zinc-600 px-4 py-3">
                <p class="text-xs font-semibold text-nrapa-blue dark:text-zinc-300 mb-1.5">{{ __('Password requirements:') }}</p>
                <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                    <li class="flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-nrapa-blue/60 dark:text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        {{ __('Minimum 12 characters') }}
                    </li>
                    <li class="flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-nrapa-blue/60 dark:text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        {{ __('Upper and lowercase letters') }}
                    </li>
                    <li class="flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-nrapa-blue/60 dark:text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        {{ __('At least one number') }}
                    </li>
                    <li class="flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-nrapa-blue/60 dark:text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        {{ __('At least one symbol (e.g. !@#$%^&*)') }}
                    </li>
                </ul>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Confirm password') }}</label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    id="password_confirmation"
                    required 
                    autocomplete="new-password"
                    placeholder="{{ __('Confirm password') }}"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button 
                type="submit" 
                x-bind:disabled="submitted"
                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:ring-offset-2 dark:focus:ring-offset-zinc-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                data-test="register-user-button"
            >
                <span x-show="!submitted">{{ __('Create account') }}</span>
                <span x-show="submitted" x-cloak>{{ __('Creating account...') }}</span>
            </button>
        </form>

        <p class="text-sm text-center text-zinc-600 dark:text-zinc-400">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-nrapa-blue dark:text-nrapa-orange hover:underline">{{ __('Log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
