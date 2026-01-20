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
    <body class="app-shell" x-data="{ sidebarOpen: false }">
        {{-- Impersonation Banner --}}
        @if(session('impersonating_from'))
            <div class="bg-gradient-to-r from-red-600 to-red-500 text-white px-4 py-2.5 text-center text-sm font-medium">
                <span>Impersonating {{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
                <a href="{{ route('dev.stop-impersonating') }}" class="ml-4 underline hover:no-underline font-semibold">
                    ← Return to your account
                </a>
            </div>
        @endif

        <div class="min-h-screen lg:flex">
            <!-- Mobile sidebar overlay -->
            <div 
                x-show="sidebarOpen" 
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" 
                @click="sidebarOpen = false"
                x-cloak
            ></div>

            <!-- Sidebar -->
            <aside 
                :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
                class="sidebar"
            >
                <!-- Logo -->
                <div class="sidebar-header">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate @click="sidebarOpen = false">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-sm">
                            <svg class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold" style="color: rgb(var(--color-text))">NRAPA</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden p-2 -mr-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" style="color: rgb(var(--color-text-muted))">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="sidebar-nav">
                    @php
                        $hasActiveMembership = auth()->user()->activeMembership !== null;
                    @endphp
                    
                    <!-- Member Portal -->
                    <div class="mb-6">
                        <p class="nav-section nav-section-brand">Member Portal</p>
                        
                        <a href="{{ route('dashboard') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('dashboard') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Dashboard
                        </a>
                        
                        <a href="{{ route('membership.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('membership.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            My Membership
                        </a>
                        
                        @if($hasActiveMembership)
                        <a href="{{ route('certificates.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('certificates.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Certificates
                        </a>
                        
                        <a href="{{ route('documents.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('documents.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            My Documents
                        </a>
                        
                        <a href="{{ route('armoury.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('armoury.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Virtual Safe
                        </a>
                        
                        <a href="{{ route('load-data.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('load-data.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            Loading Bench
                        </a>
                        
                        @if(auth()->user()->activeMembership?->type?->allows_dedicated_status)
                        <a href="{{ route('knowledge-test.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('knowledge-test.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Knowledge Test
                        </a>
                        
                        <a href="{{ route('activities.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('activities.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Activities
                        </a>
                        @endif
                        
                        <a href="{{ route('learning.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('learning.*') ? 'nav-link-active' : 'nav-link' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Learning Center
                        </a>
                        @endif
                    </div>

                    <!-- Admin Section -->
                    @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
                    <div class="mb-6">
                        <p class="nav-section" style="color: rgb(59 130 246);">Administration</p>
                        
                        <a href="{{ route('admin.members.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('admin.members.*') ? 'nav-link-active' : 'nav-link' }}"
                            style="{{ request()->routeIs('admin.members.*') ? 'background-color: rgb(219 234 254); color: rgb(37 99 235);' : '' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Members
                        </a>
                        
                        {{-- Approvals with dropdown --}}
                        @php
                            $pendingDocs = 0;
                            $pendingMemberships = 0;
                            $pendingActivities = 0;
                            $pendingCalibres = 0;
                            try {
                                if (\Illuminate\Support\Facades\Schema::hasTable('member_documents')) {
                                    $pendingDocs = \App\Models\MemberDocument::where('status', 'pending')->count();
                                }
                                if (\Illuminate\Support\Facades\Schema::hasTable('memberships')) {
                                    $pendingMemberships = \App\Models\Membership::where('status', 'applied')->count();
                                }
                                if (\Illuminate\Support\Facades\Schema::hasTable('shooting_activities')) {
                                    $pendingActivities = \App\Models\ShootingActivity::where('status', 'pending')->count();
                                }
                                if (\Illuminate\Support\Facades\Schema::hasTable('calibre_requests')) {
                                    $pendingCalibres = \App\Models\CalibreRequest::where('status', 'pending')->count();
                                }
                            } catch (\Exception $e) {}
                            $totalPending = $pendingDocs + $pendingMemberships + $pendingActivities + $pendingCalibres;
                        @endphp
                        
                        <div x-data="{ open: {{ request()->routeIs('admin.approvals.*') || request()->routeIs('admin.documents.*') || request()->routeIs('admin.calibre-requests.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="nav-link w-full justify-between">
                                <span class="flex items-center gap-3">
                                    <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Approvals
                                    @if($totalPending > 0)
                                    <span class="badge badge-warning">{{ $totalPending }}</span>
                                    @endif
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse class="ml-8 mt-1 space-y-0.5">
                                <a href="{{ route('admin.approvals.index') }}" wire:navigate @click="sidebarOpen = false" 
                                    class="nav-link text-sm py-2">All Approvals</a>
                                <a href="{{ route('admin.documents.index') }}" wire:navigate @click="sidebarOpen = false" 
                                    class="nav-link text-sm py-2 justify-between">
                                    Documents
                                    @if($pendingDocs > 0)<span class="badge badge-warning text-[10px] px-1.5 py-0.5">{{ $pendingDocs }}</span>@endif
                                </a>
                                <a href="{{ route('admin.approvals.index', ['type' => 'memberships']) }}" wire:navigate @click="sidebarOpen = false" 
                                    class="nav-link text-sm py-2 justify-between">
                                    Memberships
                                    @if($pendingMemberships > 0)<span class="badge badge-warning text-[10px] px-1.5 py-0.5">{{ $pendingMemberships }}</span>@endif
                                </a>
                                <a href="{{ route('admin.approvals.index', ['type' => 'activities']) }}" wire:navigate @click="sidebarOpen = false" 
                                    class="nav-link text-sm py-2 justify-between">
                                    Activities
                                    @if($pendingActivities > 0)<span class="badge badge-warning text-[10px] px-1.5 py-0.5">{{ $pendingActivities }}</span>@endif
                                </a>
                                <a href="{{ route('admin.calibre-requests.index') }}" wire:navigate @click="sidebarOpen = false" 
                                    class="nav-link text-sm py-2 justify-between">
                                    Calibres
                                    @if($pendingCalibres > 0)<span class="badge badge-warning text-[10px] px-1.5 py-0.5">{{ $pendingCalibres }}</span>@endif
                                </a>
                            </div>
                        </div>
                        
                        {{-- Settings with dropdown --}}
                        <div x-data="{ open: {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.membership-types.*') || request()->routeIs('admin.activity-config.*') || request()->routeIs('admin.firearm-settings.*') || request()->routeIs('admin.learning.*') || request()->routeIs('admin.knowledge-tests.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="nav-link w-full justify-between">
                                <span class="flex items-center gap-3">
                                    <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Settings
                                </span>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse class="ml-8 mt-1 space-y-0.5">
                                <a href="{{ route('admin.membership-types.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Membership Types</a>
                                <a href="{{ route('admin.activity-config.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Activities</a>
                                <a href="{{ route('admin.firearm-settings.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Firearm Settings</a>
                                <a href="{{ route('admin.learning.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Learning Center</a>
                                <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Knowledge Tests</a>
                                <a href="{{ route('admin.settings.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">General Settings</a>
                                <a href="{{ route('admin.email-logs.index') }}" wire:navigate @click="sidebarOpen = false" class="nav-link text-sm py-2">Email Logs</a>
                            </div>
                        </div>

                        <!-- Owner-only items -->
                        @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_OWNER))
                        <div class="mt-4 pt-4 border-t" style="border-color: rgb(var(--color-border))">
                            <p class="nav-section" style="color: rgb(168 85 247);">Owner</p>
                            
                            <a href="{{ route('owner.dashboard') }}" wire:navigate @click="sidebarOpen = false" 
                                class="{{ request()->routeIs('owner.dashboard') ? 'nav-link-active' : 'nav-link' }}"
                                style="{{ request()->routeIs('owner.dashboard') ? 'background-color: rgb(243 232 255); color: rgb(147 51 234);' : '' }}">
                                <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Owner Dashboard
                            </a>
                            
                            <a href="{{ route('owner.admins.index') }}" wire:navigate @click="sidebarOpen = false" 
                                class="{{ request()->routeIs('owner.admins.*') ? 'nav-link-active' : 'nav-link' }}"
                                style="{{ request()->routeIs('owner.admins.*') ? 'background-color: rgb(243 232 255); color: rgb(147 51 234);' : '' }}">
                                <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Manage Admins
                            </a>
                            
                            <a href="{{ route('owner.settings.index') }}" wire:navigate @click="sidebarOpen = false" 
                                class="{{ request()->routeIs('owner.settings.*') ? 'nav-link-active' : 'nav-link' }}"
                                style="{{ request()->routeIs('owner.settings.*') ? 'background-color: rgb(243 232 255); color: rgb(147 51 234);' : '' }}">
                                <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Platform Settings
                            </a>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Developer Section -->
                    @if(auth()->user()->isDeveloper())
                    <div class="mb-6">
                        <p class="nav-section" style="color: rgb(239 68 68);">Developer</p>
                        
                        <a href="{{ route('developer.dashboard') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('developer.dashboard') ? 'nav-link-active' : 'nav-link' }}"
                            style="{{ request()->routeIs('developer.dashboard') ? 'background-color: rgb(254 226 226); color: rgb(220 38 38);' : '' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Dev Dashboard
                            <span class="badge badge-danger text-[10px]">DEV</span>
                        </a>
                        
                        <a href="{{ route('developer.owners.index') }}" wire:navigate @click="sidebarOpen = false" 
                            class="{{ request()->routeIs('developer.owners.*') ? 'nav-link-active' : 'nav-link' }}"
                            style="{{ request()->routeIs('developer.owners.*') ? 'background-color: rgb(254 226 226); color: rgb(220 38 38);' : '' }}">
                            <svg class="nav-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            Manage Owners
                        </a>
                    </div>
                    @endif
                </nav>

                <!-- User Menu -->
                <div class="sidebar-footer">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate" style="color: rgb(var(--color-text))">{{ auth()->user()->name }}</p>
                            <p class="text-xs truncate" style="color: rgb(var(--color-text-muted))">{{ auth()->user()->email }}</p>
                        </div>
                        
                        {{-- Theme Toggle --}}
                        <div x-data="{ 
                            theme: localStorage.getItem('theme') || 'system',
                            setTheme(value) {
                                this.theme = value;
                                localStorage.setItem('theme', value);
                                if (value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                            }
                        }">
                            <button @click="setTheme(theme === 'dark' ? 'light' : 'dark')" class="theme-toggle" title="Toggle theme">
                                <svg x-show="theme !== 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                                <svg x-show="theme === 'dark'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ route('profile.edit') }}" wire:navigate @click="sidebarOpen = false" class="btn-secondary flex-1 text-xs justify-center">
                            Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="btn-ghost w-full text-xs justify-center" style="color: rgb(var(--color-danger))">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-h-screen lg:ml-0">
                <!-- Mobile Header -->
                <header class="topbar lg:hidden">
                    <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-xl transition-colors" style="color: rgb(var(--color-text-secondary))">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600">
                            <svg class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold" style="color: rgb(var(--color-text))">NRAPA</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="p-2 -mr-2 rounded-xl transition-colors" style="color: rgb(var(--color-text-secondary))">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-6 lg:p-8">
                    <div class="app-container">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
