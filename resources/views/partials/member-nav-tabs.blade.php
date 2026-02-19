@php
    $membership = auth()->user()?->activeMembership;
    $currentRoute = request()->route()?->getName() ?? '';
    $isOwnerRoute = str_starts_with($currentRoute, 'owner.') || str_starts_with($currentRoute, 'admin.') || str_starts_with($currentRoute, 'developer.');
    $active = 'border-nrapa-blue text-nrapa-blue dark:text-nrapa-blue dark:border-nrapa-blue dark:bg-zinc-700/50';
    $inactive = 'border-transparent text-zinc-500 hover:text-zinc-900 hover:border-zinc-300 dark:text-zinc-300 dark:hover:text-zinc-100 dark:hover:border-zinc-600';
@endphp

@if($membership && !$isOwnerRoute)
<div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <nav class="flex flex-wrap gap-x-1 -mb-px overflow-x-auto scrollbar-none">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'dashboard' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                </svg>
                Dashboard
            </a>

            {{-- Membership --}}
            <a href="{{ route('membership.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'membership.') && $currentRoute !== 'membership.apply' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                </svg>
                Membership
            </a>

            {{-- Documents --}}
            <a href="{{ route('documents.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'documents.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Documents
            </a>

            {{-- Activities --}}
            <a href="{{ route('activities.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'activities.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Activities
            </a>

            {{-- Virtual Safe --}}
            <a href="{{ route('armoury.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'armoury.') || str_starts_with($currentRoute, 'load-data.') || str_starts_with($currentRoute, 'ladder-test.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Virtual Safe
            </a>

            {{-- Certificates --}}
            <a href="{{ route('certificates.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'certificates.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Certificates
            </a>

            {{-- Learning --}}
            <a href="{{ route('learning.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'learning.') || str_starts_with($currentRoute, 'knowledge-test.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Learning
            </a>

            {{-- Dedicated Status (conditional) --}}
            @if($membership->allowsDedicatedStatus())
            <a href="{{ route('member.endorsements.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'member.endorsements.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                </svg>
                Dedicated Status
            </a>
            @endif

            {{-- Renew (conditional) --}}
            @if($membership->requiresRenewal() && $membership->isRenewable())
            <a href="{{ route('membership.apply') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'membership.apply' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Renew
            </a>
            @endif

            {{-- Profile (at the end) --}}
            <a href="{{ route('profile.edit') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'profile.') || str_starts_with($currentRoute, 'user-password.') || str_starts_with($currentRoute, 'appearance.') || str_starts_with($currentRoute, 'two-factor.') || str_starts_with($currentRoute, 'security-questions.') || str_starts_with($currentRoute, 'notifications.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                Profile
            </a>
        </nav>
    </div>
</div>
@endif
