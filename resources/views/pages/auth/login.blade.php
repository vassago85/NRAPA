<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-xl font-semibold text-nrapa-blue dark:text-white">{{ __('Log in to your account') }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Enter your email and password below to log in') }}</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="p-3 text-sm text-center text-green-700 bg-green-100 dark:bg-green-900/50 dark:text-green-300 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Email address') }}</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email"
                    value="{{ old('email') }}"
                    required 
                    autofocus 
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('email')
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
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:border-transparent"
                />
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    name="remember" 
                    id="remember"
                    {{ old('remember') ? 'checked' : '' }}
                    class="w-4 h-4 text-nrapa-blue bg-white dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600 rounded focus:ring-nrapa-blue"
                />
                <label for="remember" class="ml-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Remember me') }}</label>
            </div>

            <button 
                type="submit" 
                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-nrapa-blue rounded-lg hover:bg-nrapa-blue-dark focus:outline-none focus:ring-2 focus:ring-nrapa-blue focus:ring-offset-2 dark:focus:ring-offset-zinc-800 transition"
                data-test="login-button"
            >
                {{ __('Log in') }}
            </button>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-center text-nrapa-orange dark:text-nrapa-orange hover:underline">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-center text-zinc-600 dark:text-zinc-400">
                {{ __('Don\'t have an account?') }}
                <a href="{{ route('register') }}" wire:navigate class="font-medium text-nrapa-blue dark:text-nrapa-orange hover:underline">{{ __('Sign up') }}</a>
            </p>
        @endif

        {{-- Dev/Test Quick Login (only in non-production) --}}
        @if (app()->environment('local', 'development', 'testing'))
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
                    <span class="px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 rounded-full">DEV MODE</span>
                    <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
                </div>
                <p class="text-xs text-center text-zinc-500 dark:text-zinc-400 mb-3">Quick login as test user (development only)</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('dev.login', 'developer') }}" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/30 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Developer
                    </a>
                    <a href="{{ route('dev.login', 'owner') }}" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Owner
                    </a>
                    <a href="{{ route('dev.login', 'admin') }}" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/30 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Admin
                    </a>
                    <a href="{{ route('dev.login', 'member') }}" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Member
                    </a>
                </div>
                <p class="mt-3 text-xs text-center text-red-500 dark:text-red-400">
                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Not available in production
                </p>
            </div>
        @endif
    </div>
</x-layouts::auth>
