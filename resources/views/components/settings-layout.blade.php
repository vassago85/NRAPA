@props(['heading' => '', 'subheading' => ''])

<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav class="space-y-1" aria-label="{{ __('Settings') }}">
            <a href="{{ route('profile.edit') }}" wire:navigate
               class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('profile.edit') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                {{ __('Profile') }}
            </a>
            <a href="{{ route('user-password.edit') }}" wire:navigate
               class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('user-password.edit') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                {{ __('Password') }}
            </a>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <a href="{{ route('two-factor.show') }}" wire:navigate
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('two-factor.show') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    {{ __('Two-Factor Auth') }}
                </a>
            @endif
            <a href="{{ route('appearance.edit') }}" wire:navigate
               class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('appearance.edit') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                {{ __('Appearance') }}
            </a>
            @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
                <a href="{{ route('notifications.edit') }}" wire:navigate
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('notifications.edit') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    {{ __('Notifications') }}
                </a>
            @endif
        </nav>
    </div>

    <hr class="md:hidden border-zinc-200 dark:border-zinc-700 w-full my-4" />

    <div class="flex-1 self-stretch max-md:pt-6">
        @if($heading)
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $heading }}</h2>
        @endif
        @if($subheading)
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">{{ $subheading }}</p>
        @endif

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
