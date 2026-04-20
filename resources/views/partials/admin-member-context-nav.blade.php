@php
    /** @var \App\Models\User $user */
    $currentRoute = request()->route()?->getName() ?? '';
    $back = \App\Helpers\AdminMemberContext::backLink();

    $activeMembership = $user->activeMembership ?? $user->memberships()->latest()->first();
    $membershipTypeName = $activeMembership?->type?->name;
    $membershipNumber = $activeMembership?->membership_number ?? $user->formatted_member_number ?? null;

    $statusLabel = null;
    $statusClass = 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200';
    if ($user->activeMembership) {
        $m = $user->activeMembership;
        if ($m->expires_at && $m->expires_at->isPast()) {
            $statusLabel = 'Expired';
            $statusClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        } else {
            $statusLabel = 'Active';
            $statusClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
        }
    } elseif ($activeMembership) {
        $displayStatus = $activeMembership->status;
        if ($displayStatus === 'active' && $activeMembership->expires_at && $activeMembership->expires_at->isPast()) {
            $displayStatus = 'expired';
        }
        $statusLabel = ucfirst($displayStatus);
        $statusClass = match ($displayStatus) {
            'applied', 'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'expired' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
            'suspended', 'revoked' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        };
    } else {
        $statusLabel = 'No Membership';
    }

    $memberUrl = route('admin.members.show', $user);
    $onMemberPage = $currentRoute === 'admin.members.show';

    $actionClass = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors';
    $primaryActionClass = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors';
@endphp

<div class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto py-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {{-- Member identity --}}
        <div class="flex items-center gap-3 min-w-0">
            @if($back)
                <a href="{{ route($back[1], $back[2]) }}" wire:navigate
                   class="hidden sm:inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden md:inline">Back to</span> {{ $back[0] }}
                </a>
                <span class="hidden sm:inline h-5 w-px bg-zinc-200 dark:bg-zinc-700"></span>
            @endif

            <a href="{{ route('admin.members.show', $user) }}" wire:navigate
               class="flex items-center gap-3 min-w-0 group">
                <div class="flex-shrink-0 size-9 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                    {{ method_exists($user, 'initials') ? $user->initials() : strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        {{ $user->name }}
                    </p>
                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 truncate">
                        @if($membershipTypeName)
                            <span class="truncate">{{ $membershipTypeName }}</span>
                        @endif
                        @if($membershipNumber)
                            <span class="font-mono">{{ $membershipNumber }}</span>
                        @endif
                        @if($statusLabel)
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button"
                    x-data
                    @click="Livewire.dispatch('admin-send-message', { userId: {{ $user->id }}, userName: @js($user->name), userEmail: @js($user->email) })"
                    class="{{ $primaryActionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                Send Message
            </button>

            @if(!$onMemberPage)
                <a href="{{ $memberUrl }}" wire:navigate class="{{ $actionClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
            @endif

            <a href="{{ $onMemberPage ? '#membership' : $memberUrl . '#membership' }}" @unless($onMemberPage) wire:navigate @endunless class="{{ $actionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Membership
            </a>

            <a href="{{ $onMemberPage ? '#documents' : $memberUrl . '#documents' }}" @unless($onMemberPage) wire:navigate @endunless class="{{ $actionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documents
            </a>

            <a href="{{ $onMemberPage ? '#activities' : $memberUrl . '#activities' }}" @unless($onMemberPage) wire:navigate @endunless class="{{ $actionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Activities
            </a>

            <a href="{{ $onMemberPage ? '#endorsements' : $memberUrl . '#endorsements' }}" @unless($onMemberPage) wire:navigate @endunless class="{{ $actionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                Endorsements
            </a>

            <a href="{{ $onMemberPage ? '#messages' : $memberUrl . '#messages' }}" @unless($onMemberPage) wire:navigate @endunless class="{{ $actionClass }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                Messages
            </a>

            <a href="{{ route('admin.members.index') }}" wire:navigate
               class="ml-1 inline-flex items-center gap-1 px-2 py-1.5 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700 rounded-lg transition-colors"
               title="Exit member context">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="hidden lg:inline">Exit</span>
            </a>
        </div>
    </div>
</div>
