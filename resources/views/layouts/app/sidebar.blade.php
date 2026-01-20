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
                class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:w-64 lg:flex-shrink-0"
            >
                
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate @click="sidebarOpen = false">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700">
                            <svg class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">NRAPA</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden p-2 -mr-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    @php
                        $hasActiveMembership = auth()->user()->activeMembership !== null;
                    @endphp
                    <!-- Member Portal -->
                    <div class="mb-4">
                        <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Member Portal</p>
                        <a href="{{ route('dashboard') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('membership.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('membership.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            My Membership
                        </a>
                        
                        @if($hasActiveMembership)
                        <a href="{{ route('certificates.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('certificates.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Certificates & Endorsements
                        </a>
                        <a href="{{ route('documents.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('documents.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            My Documents
                        </a>
                        <a href="{{ route('armoury.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('armoury.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Virtual Safe
                        </a>
                        <a href="{{ route('load-data.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('load-data.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            Virtual Loading Bench
                        </a>
                        @if(auth()->user()->activeMembership?->type?->allows_dedicated_status)
                        <a href="{{ route('knowledge-test.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('knowledge-test.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Knowledge Test
                        </a>
                        <a href="{{ route('activities.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('activities.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Activities
                        </a>
                        <a href="{{ route('member.endorsements.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('member.endorsements.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            Dedicated Status
                        </a>
                        @endif
                        <a href="{{ route('learning.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('learning.*') ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Learning Center
                        </a>
                        @endif
                    </div>

                    <!-- Admin Section -->
                    @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_ADMIN))
                    <div class="mb-4">
                        <p class="px-3 mb-2 text-xs font-semibold text-blue-500 dark:text-blue-400 uppercase tracking-wider">Administration</p>
                        
                        {{-- Members --}}
                        <a href="{{ route('admin.members.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.members.*') ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
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
                            <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.approvals.*') || request()->routeIs('admin.documents.*') || request()->routeIs('admin.calibre-requests.*') ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Approvals
                                    @if($totalPending > 0)
                                    <span class="px-2 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full">{{ $totalPending }}</span>
                                    @endif
                                </span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <a href="{{ route('admin.approvals.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.approvals.index') && !request()->has('type') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    All Approvals
                                </a>
                                <a href="{{ route('admin.documents.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.documents.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Documents
                                    @if($pendingDocs > 0)
                                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingDocs }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('admin.approvals.index', ['type' => 'memberships']) }}" wire:navigate @click="sidebarOpen = false" class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.approvals.*') && request('type') === 'memberships' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Memberships
                                    @if($pendingMemberships > 0)
                                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingMemberships }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('admin.approvals.index', ['type' => 'activities']) }}" wire:navigate @click="sidebarOpen = false" class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.approvals.*') && request('type') === 'activities' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Activities
                                    @if($pendingActivities > 0)
                                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingActivities }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('admin.calibre-requests.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center justify-between gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.calibre-requests.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Calibres
                                    @if($pendingCalibres > 0)
                                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">{{ $pendingCalibres }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                        
                        {{-- Settings with dropdown --}}
                        <div x-data="{ open: {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.membership-types.*') || request()->routeIs('admin.activity-config.*') || request()->routeIs('admin.firearm-settings.*') || request()->routeIs('admin.learning.*') || request()->routeIs('admin.knowledge-tests.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.membership-types.*') || request()->routeIs('admin.activity-config.*') || request()->routeIs('admin.firearm-settings.*') || request()->routeIs('admin.learning.*') || request()->routeIs('admin.knowledge-tests.*') ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Settings
                                </span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <a href="{{ route('admin.membership-types.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.membership-types.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Membership Types
                                </a>
                                <a href="{{ route('admin.activity-config.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.activity-config.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Activities
                                </a>
                                <a href="{{ route('admin.firearm-settings.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.firearm-settings.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Firearm Settings
                                </a>
                                <a href="{{ route('admin.learning.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.learning.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Learning Center
                                </a>
                                <a href="{{ route('admin.knowledge-tests.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.knowledge-tests.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Knowledge Tests
                                </a>
                                <a href="{{ route('admin.settings.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.settings.index') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    General Settings
                                </a>
                                <a href="{{ route('admin.email-logs.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg {{ request()->routeIs('admin.email-logs.*') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    Email Logs
                                </a>
                            </div>
                        </div>

                        <!-- Owner-only items within Administration -->
                        @if(auth()->user()->hasRoleLevel(\App\Models\User::ROLE_OWNER))
                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                            <p class="px-3 mb-2 text-xs font-semibold text-purple-500 dark:text-purple-400 uppercase tracking-wider">Owner</p>
                            <a href="{{ route('owner.dashboard') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('owner.dashboard') ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Owner Dashboard
                            </a>
                            <a href="{{ route('owner.admins.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('owner.admins.*') ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Manage Admins
                            </a>
                            <a href="{{ route('owner.settings.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('owner.settings.index') || request()->routeIs('owner.settings.email') || request()->routeIs('owner.settings.storage') ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Platform Settings
                            </a>
                            <a href="{{ route('owner.settings.approvals') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('owner.settings.approvals') ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Config Approvals
                                @php
                                    $pendingCount = 0;
                                    try {
                                        if (\Illuminate\Support\Facades\Schema::hasTable('configuration_change_requests')) {
                                            $pendingCount = \App\Models\ConfigurationChangeRequest::pending()->count();
                                        }
                                    } catch (\Exception $e) {
                                        $pendingCount = 0;
                                    }
                                @endphp
                                @if($pendingCount > 0)
                                <span class="ml-auto px-2 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Developer Section -->
                    @if(auth()->user()->isDeveloper())
                    <div class="mb-4">
                        <p class="px-3 mb-2 text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">Developer</p>
                        <a href="{{ route('developer.dashboard') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('developer.dashboard') ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Dev Dashboard
                            <span class="px-1.5 py-0.5 text-xs bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded">DEV</span>
                        </a>
                        <a href="{{ route('developer.owners.index') }}" wire:navigate @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('developer.owners.*') ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            Manage Owners
                        </a>
                    </div>
                    @endif
                </nav>

                <!-- User Menu -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 p-4 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white font-semibold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
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
                <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 lg:hidden">
                    <button @click="sidebarOpen = true" class="p-2 -ml-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-700">
                            <svg class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L4 6V12C4 16.42 7.58 20.58 12 22C16.42 20.58 20 16.42 20 12V6L12 2Z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold text-zinc-900 dark:text-white">NRAPA</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="p-2 -mr-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                </header>

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
