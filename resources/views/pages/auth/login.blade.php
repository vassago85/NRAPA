<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Log in to your account') }}</h1>
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
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    required 
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
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
                    class="w-4 h-4 text-emerald-600 bg-white dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600 rounded focus:ring-emerald-500"
                />
                <label for="remember" class="ml-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Remember me') }}</label>
            </div>

            <button 
                type="submit" 
                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-800 transition"
                data-test="login-button"
            >
                {{ __('Log in') }}
            </button>
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-center text-zinc-600 dark:text-zinc-400">
                {{ __('Don\'t have an account?') }}
                <a href="{{ route('register') }}" wire:navigate class="text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Sign up') }}</a>
            </p>
        @endif
    </div>
</x-layouts::auth>
