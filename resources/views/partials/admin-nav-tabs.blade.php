@php
    $currentRoute = request()->route()?->getName() ?? '';
    $active = 'border-nrapa-blue text-nrapa-blue dark:text-blue-400 dark:border-blue-400';
    $inactive = 'border-transparent text-zinc-500 hover:text-zinc-900 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200';
    $separator = 'border-l border-zinc-200 dark:border-zinc-800 mx-1 self-stretch';

    $configRoutes = ['admin.membership-types.', 'admin.affiliated-clubs.', 'admin.activity-config.', 'admin.calibre-requests.', 'admin.firearm-settings.', 'admin.firearm-reference.', 'admin.bullet-database.'];
    $isConfigRoute = collect($configRoutes)->contains(fn($prefix) => str_starts_with($currentRoute, $prefix));
    $isLearningRoute = str_starts_with($currentRoute, 'admin.learning.') || str_starts_with($currentRoute, 'admin.knowledge-tests.');
@endphp

<div class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <nav class="flex flex-wrap gap-x-0.5 -mb-px overflow-x-auto scrollbar-none">
            {{-- Administration --}}
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'admin.dashboard' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
                Dashboard
            </a>

            @php
                $inboxUnread = \App\Models\MemberMessage::where('direction', \App\Models\MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                    ->whereNull('read_at')
                    ->count();
            @endphp
            <a href="{{ route('admin.messages.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.messages.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.67 1.09-.086 2.17-.208 3.238-.365 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                </svg>
                Inbox
                @if($inboxUnread > 0)
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $inboxUnread }}</span>
                @endif
            </a>

            <a href="{{ route('admin.members.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.members.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Members
            </a>

            <a href="{{ route('admin.approvals.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.approvals.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Approvals
            </a>

            <a href="{{ route('admin.endorsements.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.endorsements.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                Endorsements
            </a>

            <a href="{{ route('admin.documents.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.documents.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                Documents
            </a>

            @php $pendingActivities = \App\Models\ShootingActivity::where('status', 'pending')->count(); @endphp
            <a href="{{ route('admin.activities.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.activities.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Activities
                @if($pendingActivities > 0)
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $pendingActivities }}</span>
                @endif
            </a>

            <a href="{{ route('admin.billing.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'admin.billing.index' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                Billing
            </a>

            <a href="{{ route('admin.reports.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'admin.reports.index' ? $active : $inactive }} transition-colors whitespace-nowrap">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/>
                </svg>
                Reports
            </a>

            <div class="{{ $separator }}"></div>

            {{-- Configuration (group link) --}}
            <a href="{{ route('admin.membership-types.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $isConfigRoute ? $active : $inactive }} transition-colors whitespace-nowrap">
                Configuration
            </a>

            {{-- Learning (group link) --}}
            <a href="{{ route('admin.learning.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $isLearningRoute ? $active : $inactive }} transition-colors whitespace-nowrap">
                Learning
            </a>

            <div class="{{ $separator }}"></div>

            {{-- System --}}
            <a href="{{ route('admin.email-logs.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ $currentRoute === 'admin.email-logs.index' ? $active : $inactive }} transition-colors whitespace-nowrap">
                Email Logs
            </a>

            <a href="{{ route('admin.settings.index') }}" wire:navigate
               class="flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border-b-2 {{ str_starts_with($currentRoute, 'admin.settings.') ? $active : $inactive }} transition-colors whitespace-nowrap">
                Settings
            </a>
        </nav>
    </div>
</div>
