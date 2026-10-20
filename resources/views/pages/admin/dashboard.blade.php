<?php

use App\Enums\ApprovalType;
use App\Enums\ChaseOutcome;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Models\Approval;
use App\Models\User;
use App\Queries\ApprovalQueue;
use App\Services\Approvals\ApprovalService;
use App\Services\Approvals\ChaseRunner;
use App\Support\Membership\MembershipSnapshot;
use App\Support\Membership\MembershipStats;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dashboard - Admin')] class extends Component {
    public const QUEUE_LIMIT = 25;

    public const MEMBER_ROWS_LIMIT = 5;

    #[Url]
    public ?string $type = null;

    public ?array $flash = null;

    public function mount(): void
    {
        if ($this->type && ! ApprovalType::tryFrom($this->type)) {
            $this->type = null;
        }
    }

    public function filter(?string $type): void
    {
        $this->type = $type && ApprovalType::tryFrom($type) ? $type : null;
        unset($this->queue);
    }

    public function refresh(): void
    {
        MembershipStats::refresh();
        unset($this->counts, $this->queue, $this->chaseRows, $this->snapshot);
    }

    public function approveInline(int $approvalId): void
    {
        $approval = Approval::findOrFail($approvalId);

        try {
            $result = app(ApprovalService::class)->approveInline($approval, auth()->user());
            $summary = $result->approvable?->summaryLine() ?? 'item';
            $this->flash = ['tone' => 'success', 'message' => $result->awaitsSecondSignature()
                ? "First signature recorded on {$summary}. A second admin needs to approve."
                : "Approved: {$summary}."];
        } catch (ApprovalAlreadyDecided $e) {
            $this->flash = ['tone' => 'warning', 'message' => $e->describe()];
        } catch (ApprovalNotAllowed $e) {
            $this->flash = ['tone' => 'error', 'message' => $e->getMessage()];
        } catch (ValidationException $e) {
            $this->flash = ['tone' => 'error', 'message' => collect($e->errors())->flatten()->first()];
        }

        unset($this->counts, $this->queue);
    }

    public function chase(int $approvalId): void
    {
        $approval = Approval::with(['user', 'approvable'])->find($approvalId);

        if (! $approval) {
            $this->flash = ['tone' => 'error', 'message' => 'That approval is no longer there.'];

            return;
        }

        $outcome = app(ChaseRunner::class)->chase($approval, auth()->user(), source: 'manual');

        $this->flash = [
            'tone' => $outcome->sent() ? 'success' : ($outcome === ChaseOutcome::MemberCannotReceiveEmail ? 'error' : 'warning'),
            'message' => $outcome->describe($approval, $approval->user),
        ];

        unset($this->chaseRows, $this->tiles);
    }

    public function chaseAll(): void
    {
        $runner = app(ChaseRunner::class);
        $actor = auth()->user();
        $sent = $skipped = $blocked = 0;

        foreach ($this->chaseRows as $approval) {
            $outcome = $runner->chase($approval, $actor, source: 'manual');
            match (true) {
                $outcome->sent() => $sent++,
                $outcome === ChaseOutcome::MemberCannotReceiveEmail => $blocked++,
                default => $skipped++,
            };
        }

        $parts = ["Chased {$sent}"];
        if ($skipped) {
            $parts[] = "{$skipped} already chased in the last 24h";
        }
        if ($blocked) {
            $parts[] = "{$blocked} with bounced email";
        }

        $this->flash = [
            'tone' => $sent > 0 ? 'success' : 'warning',
            'message' => implode(' · ', $parts).'.',
        ];

        unset($this->chaseRows, $this->tiles);
    }

    #[Computed]
    public function counts(): array
    {
        return app(ApprovalQueue::class)->countsByType();
    }

    #[Computed]
    public function tiles(): array
    {
        $queue = app(ApprovalQueue::class);
        $weekStart = now()->startOfWeek();

        return [
            'oldest' => $queue->oldest(),
            'breaches' => $queue->breachCount(Approval::SLA_HOURS),
            'waiting_on_member' => $queue->waitingOnMemberCount(),
            'outstanding' => $queue->amountOutstandingFromMembers(),
            'cleared' => $queue->clearedSince($weekStart),
            'median_hours' => $queue->medianHoursToDecision($weekStart),
        ];
    }

    #[Computed]
    public function queue()
    {
        $queue = app(ApprovalQueue::class);

        return $queue->loadForDisplay($queue->waitingOnAdmin($this->type), self::QUEUE_LIMIT);
    }

    #[Computed]
    public function chaseRows()
    {
        $queue = app(ApprovalQueue::class);

        return $queue->withChaseSummary($queue->waitingOnMember())
            ->with(['user', 'approvable'])
            ->limit(self::MEMBER_ROWS_LIMIT)
            ->get()
            ->filter(fn (Approval $a) => $a->approvable !== null)
            ->values();
    }

    #[Computed]
    public function snapshot(): MembershipSnapshot
    {
        return MembershipStats::snapshot();
    }

    public function totalWaiting(): int
    {
        return array_sum($this->counts);
    }

    public function filteredTotal(): int
    {
        return $this->type ? ($this->counts[$this->type] ?? 0) : $this->totalWaiting();
    }

    public function breakdownLine(): string
    {
        return collect(ApprovalType::cases())
            ->filter(fn (ApprovalType $t) => ($this->counts[$t->value] ?? 0) > 0)
            ->map(fn (ApprovalType $t) => $this->counts[$t->value].' '.strtolower($this->counts[$t->value] === 1 ? $t->label() : $t->pluralLabel()))
            ->implode(' · ');
    }
}; ?>

@php
    $total = $this->totalWaiting();
    $typesWaiting = count(array_filter($this->counts));
    $tiles = $this->tiles;
    $snapshot = $this->snapshot;
    $shown = count($this->queue);
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Everything waiting on an admin, oldest first
                @if($total > 0)
                    · {{ $total }} {{ str('item')->plural($total) }} across {{ $typesWaiting }} {{ str('type')->plural($typesWaiting) }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="refresh" wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium rounded-lg border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                Refresh
            </button>
            <a href="{{ route('admin.review.index') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium rounded-lg bg-nrapa-blue text-white hover:bg-nrapa-blue-dark transition-colors">
                Start review queue
            </a>
        </div>
    </div>

    @if($flash)
        <div data-flash @class([
            'rounded-lg border px-4 py-3 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200' => $flash['tone'] === 'success',
            'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200' => $flash['tone'] === 'warning',
            'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200' => $flash['tone'] === 'error',
        ])>{{ $flash['message'] }}</div>
    @endif

    {{-- Tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div data-tile="waiting-on-you" class="rounded-2xl border border-blue-200 bg-[#f7fbff] p-6 shadow-sm dark:border-nrapa-blue/40 dark:bg-nrapa-blue/10">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Waiting on you</p>
            <p class="mt-2 text-3xl font-bold text-nrapa-blue dark:text-blue-300" data-tile-value>{{ $total }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->breakdownLine() ?: 'Nothing waiting' }}</p>
        </div>

        <div data-tile="oldest" class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 dark:bg-zinc-800 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Oldest item</p>
            @if($tiles['oldest'])
                <p class="mt-2 text-3xl font-bold {{ $tiles['oldest']->ageTextClasses() }}">{{ $tiles['oldest']->ageLabel() }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $tiles['breaches'] }} {{ str('item')->plural($tiles['breaches']) }} past the {{ \App\Models\Approval::SLA_HOURS }}-hour target
                </p>
            @else
                <p class="mt-2 text-3xl font-bold text-zinc-400">None</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Queue is clear</p>
            @endif
        </div>

        <div data-tile="waiting-on-member" class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 dark:bg-zinc-800 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Waiting on the member</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $tiles['waiting_on_member'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                R{{ number_format($tiles['outstanding'], 0, '.', ',') }} outstanding · not your queue
            </p>
        </div>

        <div data-tile="cleared" class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 dark:bg-zinc-800 dark:border-zinc-700">
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Cleared this week</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $tiles['cleared'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                @if($tiles['median_hours'] !== null)
                    Team median {{ rtrim(rtrim(number_format($tiles['median_hours'], 1), '0'), '.') }} hours to decision
                @else
                    No decisions yet this week
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        {{-- Queue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 overflow-hidden"
            x-data="{
                cursor: -1,
                rows() { return [...$el.querySelectorAll('[data-queue-row]')] },
                mark() {
                    this.rows().forEach((row, i) => row.classList.toggle('ring-2', i === this.cursor));
                    this.rows()[this.cursor]?.scrollIntoView({ block: 'nearest' });
                },
                move(step) {
                    const count = this.rows().length;
                    if (! count) return;
                    this.cursor = Math.min(Math.max(this.cursor + step, 0), count - 1);
                    this.mark();
                },
                handle(e) {
                    const tag = (e.target.tagName || '').toLowerCase();
                    if (['input', 'textarea', 'select'].includes(tag) || e.target.isContentEditable) return;
                    if (e.metaKey || e.ctrlKey || e.altKey) return;
                    const key = e.key.toLowerCase();
                    if (key === 'j') { e.preventDefault(); this.move(1); }
                    else if (key === 'k') { e.preventDefault(); this.move(-1); }
                    else if (key === 'a') { this.rows()[this.cursor]?.querySelector('[data-inline-approve]')?.click(); }
                    else if (e.key === 'Enter' && ! ['button', 'a'].includes(tag)) { this.rows()[this.cursor]?.querySelector('[data-review-link]')?.click(); }
                },
            }"
            x-on:keydown.window="handle($event)">
            <div class="flex flex-col gap-3 border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Waiting on you</h2>
                    <p class="hidden text-xs text-zinc-500 sm:block dark:text-zinc-400">J / K move · A approve · Enter open · Oldest first</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="filter(null)" data-chip="all"
                        @class([
                            'px-3 py-1 text-xs font-medium rounded-full border transition-colors',
                            'border-nrapa-blue bg-nrapa-blue text-white' => ! $type,
                            'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' => $type,
                        ])>
                        All <span @class(['ml-1', 'text-zinc-400' => $total === 0 && $type])>{{ $total }}</span>
                    </button>
                    @foreach(\App\Enums\ApprovalType::cases() as $chipType)
                        @php $chipCount = $this->counts[$chipType->value] ?? 0; @endphp
                        <button type="button" wire:click="filter('{{ $chipType->value }}')" data-chip="{{ $chipType->value }}"
                            @class([
                                'px-3 py-1 text-xs font-medium rounded-full border transition-colors',
                                'border-nrapa-blue bg-nrapa-blue text-white' => $type === $chipType->value,
                                'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' => $type !== $chipType->value,
                            ])>
                            {{ $chipType->pluralLabel() }}
                            <span @class(['ml-1', 'text-zinc-400' => $chipCount === 0 && $type !== $chipType->value])>{{ $chipCount }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                @forelse($this->queue as $approval)
                    <x-admin.approval-row :approval="$approval" data-queue-row class="ring-inset ring-nrapa-blue" />
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">All caught up</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Nothing is waiting on an admin{{ $type ? ' for this type' : '' }}.</p>
                    </div>
                @endforelse
            </div>

            @if($this->filteredTotal() > $shown)
                <div class="border-t border-zinc-200 px-6 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Showing the oldest {{ $shown }} of {{ $this->filteredTotal() }}.
                    <a href="{{ route('admin.review.index', array_filter(['type' => $type])) }}" wire:navigate class="font-medium text-nrapa-blue hover:underline">Work through them in the review queue →</a>
                </div>
            @endif
        </div>

        {{-- Chase rail --}}
        @php
            // Pre-compute who is chaseable right now so the Chase-all header
            // and the per-row button agree on the count without a second query.
            // Non-essential: unsubscribed members are hidden from the
            // chase-all count and per-row buttons, matching ChaseRunner's
            // gate so the UI and the service agree.
            $chaseable = $this->chaseRows->filter(fn ($row) => $row->user?->canReceiveNonEssentialEmail());
        @endphp
        <aside class="bg-white rounded-2xl shadow-sm border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 overflow-hidden self-start">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Waiting on the member</h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Blocked on the member, nothing for you to approve yet</p>
                    </div>
                    @if($chaseable->isNotEmpty())
                        <button type="button" wire:click="chaseAll" wire:loading.attr="disabled" data-chase-all
                            wire:confirm="Email {{ $chaseable->count() }} {{ str('member')->plural($chaseable->count()) }} now? Anyone chased in the last 24 hours is skipped."
                            class="flex-none inline-flex items-center gap-1 rounded-lg bg-nrapa-blue px-3 py-1.5 text-xs font-semibold text-white hover:bg-nrapa-blue-dark disabled:opacity-50">
                            Chase {{ $chaseable->count() }}
                        </button>
                    @endif
                </div>
            </div>
            <div>
                @forelse($this->chaseRows as $row)
                    @php
                        $item = $row->approvable;
                        $member = $row->user;
                        $bounced = $member && $member->email && ! $member->canReceiveEmail();
                    @endphp
                    <div data-member-row wire:key="chase-{{ $row->id }}" class="border-b border-zinc-100 px-5 py-3 last:border-b-0 dark:border-zinc-700/60">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                            {{ $member?->name ?? 'Unknown member' }}
                            @if($item instanceof \App\Models\Payment)
                                <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ $item->formattedAmount() }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                            {{ $row->type->label() }}
                            @if($item instanceof \App\Models\Payment && $item->reference)
                                · {{ $item->reference }}
                            @endif
                            · waiting since {{ $row->waitingSince()?->diffForHumans() }}
                        </p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ $row->chaseSummary() }}</span>
                            @if($bounced)
                                <span data-bounced class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">Email bounced</span>
                            @else
                                <button type="button" wire:click="chase({{ $row->id }})" wire:loading.attr="disabled" data-chase
                                    class="ml-auto inline-flex items-center gap-1 rounded-md border border-nrapa-blue/30 bg-nrapa-blue/5 px-2 py-0.5 text-[11px] font-semibold text-nrapa-blue hover:bg-nrapa-blue hover:text-white disabled:opacity-50 dark:border-nrapa-blue/40 dark:bg-nrapa-blue/10 dark:text-blue-300">
                                    Chase
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Nobody is holding anything up.</p>
                @endforelse
            </div>
            <div class="border-t border-zinc-200 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                @if($tiles['waiting_on_member'] > count($this->chaseRows))
                    <a href="{{ route('admin.approvals.index') }}" wire:navigate class="mb-1 block font-medium text-nrapa-blue hover:underline">
                        See all {{ $tiles['waiting_on_member'] }} →
                    </a>
                @endif
                Auto-chase runs at 7, 14 and 21 days. Bounced addresses are skipped.
            </div>
        </aside>
    </div>

    {{-- Membership strip --}}
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 dark:bg-zinc-800 dark:border-zinc-700" data-membership-strip>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Membership</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="text-lg font-bold text-zinc-900 dark:text-white" data-stat="total">{{ number_format($snapshot->total) }}</span> total</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="text-lg font-bold text-emerald-700 dark:text-emerald-400" data-stat="active">{{ number_format($snapshot->active) }}</span> active</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300"><span class="text-lg font-bold text-amber-700 dark:text-amber-400" data-stat="expired">{{ number_format($snapshot->expired) }}</span> expired</p>
            </div>
            <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                <span>One snapshot, computed {{ $snapshot->computedAt->timezone(config('app.timezone'))->format('H:i') }}</span>
                <a href="{{ route('admin.members.index') }}" wire:navigate class="text-sm font-medium text-nrapa-blue hover:underline">Open members →</a>
            </div>
        </div>
    </div>
</div>
