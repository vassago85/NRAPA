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
            
            // Re-apply theme on Livewire navigation
            document.addEventListener('livewire:navigated', () => {
                const theme = localStorage.getItem('theme') || 'system';
                if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
        </script>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-900" x-data="{ sidebarOpen: false }">
        {{-- Impersonation Banner --}}
        @if(session('impersonating_from'))
            <div class="bg-red-600 text-white px-4 py-2 text-center text-sm">
                <span class="font-medium">You are impersonating {{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
                <a href="{{ route('dev.stop-impersonating') }}" class="ml-4 underline hover:no-underline font-semibold">
                    ← Return to your account
                </a>
            </div>
        @endif

        <div class="min-h-screen lg:flex">
            <!-- Mobile sidebar overlay -->
            <div 
                x-show="sidebarOpen" 
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-zinc-900/80 lg:hidden" 
                @click="sidebarOpen = false"
                x-cloak
            ></div>

            <!-- Sidebar -->
            <aside 
                :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
                class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-zinc-50 dark:bg-zinc-800 border-r border-zinc-200 dark:border-zinc-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:w-64 lg:flex-shrink-0"
            >
                
                <!-- Logo -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                    <a href="{{ route('dashboard') }}" class="block" wire:navigate @click="sidebarOpen = false">
                        <img src="{{ asset('logo-nrapa-blue-text.png') }}" alt="NRAPA" class="h-10 w-auto object-contain dark:hidden" />
                        <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" class="h-10 w-auto object-contain hidden dark:block" />
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden p-2 -mr-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                    @php
                        $menu = \App\Helpers\SidebarMenu::getMenu();
                    @endphp
                    
                    @foreach($menu as $index => $section)
                        <div class="space-y-1 {{ $section['section'] === 'OWNER' && $index > 0 ? 'pt-4 mt-4 border-t border-zinc-200 dark:border-zinc-700' : '' }}">
                            {{-- Section Header --}}
                            <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                {{ $section['section'] }}
                            </p>
                            
                            {{-- Section Items --}}
                            <div class="space-y-1">
                                @foreach($section['items'] as $item)
                                    @include('partials.sidebar-menu-item', ['item' => $item, 'level' => 0])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <!-- User Menu -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 p-4 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-nrapa-blue flex items-center justify-center text-white font-semibold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-300 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    
                    {{-- View as Member Toggle (for admin/owner/dev) --}}
                    @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
                        @php
                            $viewingAsMember = session('view_as_member', false);
                        @endphp
                        <form method="POST" action="{{ route('toggle-member-view') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 text-xs font-medium text-center {{ $viewingAsMember ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/50' : 'text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800' }} rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                </svg>
                                {{ $viewingAsMember ? 'View as Admin' : 'View as Member' }}
                            </button>
                        </form>
                    @endif
                    
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('profile.edit') }}" wire:navigate @click="sidebarOpen = false" class="flex-1 px-3 py-2 text-xs font-medium text-center text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 text-xs font-medium text-center text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/50 rounded-lg hover:bg-red-200 dark:hover:bg-red-900 transition-colors">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-h-screen">
                <!-- Mobile Header -->
                <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600 lg:hidden">
                    <button @click="sidebarOpen = true" class="p-2 -ml-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <img src="{{ asset('logo-nrapa-blue-text.png') }}" alt="NRAPA" class="h-8 w-auto object-contain dark:hidden" />
                        <img src="{{ asset('logo-nrapa-wiite_text.png') }}" alt="NRAPA" class="h-8 w-auto object-contain hidden dark:block" />
                    </div>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="p-2 -mr-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                </header>

                <!-- Page Header -->
                @if(isset($header))
                <div class="bg-gradient-to-r from-blue-50 via-sky-50 to-blue-50 dark:from-zinc-800 dark:via-zinc-800 dark:to-zinc-800 border-b border-blue-100 dark:border-zinc-700 px-4 sm:px-6 lg:px-8 py-4">
                    <div class="max-w-7xl mx-auto">
                        {{ $header }}
                    </div>
                </div>
                @endif
                @auth
                    @php $navRoute = request()->route()?->getName() ?? ''; @endphp
                    @if(str_starts_with($navRoute, 'admin.'))
                        @include('partials.admin-nav-tabs')
                    @elseif(str_starts_with($navRoute, 'owner.'))
                        @include('partials.owner-nav-tabs')
                    @elseif(str_starts_with($navRoute, 'developer.'))
                        @include('partials.developer-nav-tabs')
                    @else
                        @include('partials.member-nav-tabs')
                    @endif
                @endauth

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-white dark:bg-zinc-800 lg:bg-zinc-100 lg:dark:bg-zinc-900">
                    <div class="max-w-7xl mx-auto">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
