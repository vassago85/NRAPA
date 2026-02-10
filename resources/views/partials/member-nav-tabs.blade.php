@php
    $membership = auth()->user()->activeMembership;
    $currentRoute = request()->route()?->getName();
@endphp

@if($membership)
<nav class="flex border-b border-blue-200/50 dark:border-zinc-700 -mb-4 mt-3">
    <a href="{{ route('dashboard') }}" wire:navigate
       class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'dashboard' ? 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-400 dark:text-zinc-400 dark:hover:text-zinc-200' }} transition-colors whitespace-nowrap">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
        </svg>
        Dashboard
    </a>
    <a href="{{ route('certificates.index') }}" wire:navigate
       class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute ?? '', 'certificates.') ? 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-400 dark:text-zinc-400 dark:hover:text-zinc-200' }} transition-colors whitespace-nowrap">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Certificates
    </a>
    <a href="{{ route('profile.edit') }}" wire:navigate
       class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute ?? '', 'profile.') || str_starts_with($currentRoute ?? '', 'user-password.') || str_starts_with($currentRoute ?? '', 'appearance.') || str_starts_with($currentRoute ?? '', 'two-factor.') || str_starts_with($currentRoute ?? '', 'security-questions.') || str_starts_with($currentRoute ?? '', 'notifications.') ? 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-400 dark:text-zinc-400 dark:hover:text-zinc-200' }} transition-colors whitespace-nowrap">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
        Profile
    </a>
    @if($membership->requiresRenewal() && $membership->isRenewable())
    <a href="{{ route('membership.apply') }}" wire:navigate
       class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'membership.apply' ? 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-400 dark:text-zinc-400 dark:hover:text-zinc-200' }} transition-colors whitespace-nowrap">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
        Renew
    </a>
    @endif
    @if($membership->allowsDedicatedStatus())
    <a href="{{ route('member.endorsements.index') }}" wire:navigate
       class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute ?? '', 'member.endorsements.') ? 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-400 dark:text-zinc-400 dark:hover:text-zinc-200' }} transition-colors whitespace-nowrap">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
        </svg>
        Dedicated Status
    </a>
    @endif
</nav>
@endif
