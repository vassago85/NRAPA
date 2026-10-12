@props([
    'approval',
    'variant' => 'queue',
    'current' => false,
    'inlineAction' => 'approveInline',
])

@php
    /** @var \App\Models\Approval $approval */
    $item = $approval->approvable;
    $decided = ! $approval->isOpen() || $approval->state === \App\Enums\ApprovalState::WaitingOnMember;
    $stateTone = match ($approval->state) {
        \App\Enums\ApprovalState::Approved => 'text-emerald-600 dark:text-emerald-400',
        \App\Enums\ApprovalState::Declined => 'text-red-600 dark:text-red-400',
        default => 'text-amber-700 dark:text-amber-300',
    };
@endphp

@if($variant === 'list')
    <button type="button"
        wire:key="review-row-{{ $approval->id }}"
        wire:click="select({{ $approval->id }})"
        data-approval-id="{{ $approval->id }}"
        @class([
            'block w-full text-left px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 transition-colors',
            'border-l-[3px] border-l-nrapa-blue bg-[#f7fbff] dark:bg-nrapa-blue/10' => $current,
            'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => ! $current,
            'opacity-50' => $decided,
        ])>
        @if($decided)
            <span class="mb-1 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide {{ $stateTone }}">
                {{ $approval->state === \App\Enums\ApprovalState::WaitingOnMember ? 'Sent back' : $approval->state->label() }}
            </span>
        @else
            <span class="mb-1 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $approval->type->badgeClasses() }}">{{ $approval->type->label() }}</span>
                <span class="text-[11px] font-semibold {{ $approval->ageTextClasses() }}">{{ $approval->ageLabel() }}</span>
                @if($approval->awaitsSecondSignature())
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">1 of 2</span>
                @endif
            </span>
        @endif
        <span @class(['block text-[13px] text-zinc-900 dark:text-white', 'font-bold' => $current, 'font-semibold' => ! $current])>{{ $item?->summaryLine() ?? 'Record removed' }}</span>
        @if(! $decided && $item)
            <span class="block text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $item->detailLine() }}</span>
        @endif
    </button>
@else
    <div wire:key="approval-row-{{ $approval->id }}" {{ $attributes->class(['flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-100 last:border-b-0 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors']) }}>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $approval->type->badgeClasses() }}">{{ $approval->type->label() }}</span>
                <span class="text-xs font-semibold {{ $approval->ageTextClasses() }}">{{ $approval->ageLabel() }}</span>
                @if($approval->awaitsSecondSignature())
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">1 of 2</span>
                @endif
            </div>
            <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ $item?->summaryLine() }}</p>
            @if($item)
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->detailLine() }}</p>
                @if($warning = $item->detailWarning())
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $warning }}</p>
                @endif
            @endif
            {{ $slot }}
        </div>
        <div class="flex flex-shrink-0 items-center gap-2">
            @if($item && $approval->state === \App\Enums\ApprovalState::WaitingOnAdmin && $item->isInlineApprovable() && ! $approval->approvalBlockedReason(auth()->user()))
                <button type="button" wire:click="{{ $inlineAction }}({{ $approval->id }})" wire:loading.attr="disabled"
                    data-inline-approve="{{ $approval->id }}"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-emerald-300 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20 transition-colors disabled:opacity-50">
                    Approve
                </button>
            @endif
            <a href="{{ $approval->reviewUrl() }}" wire:navigate data-review-link
                class="px-4 py-2 text-sm font-medium rounded-lg bg-nrapa-blue text-white hover:bg-nrapa-blue-dark transition-colors">
                Review
            </a>
        </div>
    </div>
@endif
