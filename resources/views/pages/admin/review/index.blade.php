<?php

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Models\Approval;
use App\Queries\ApprovalQueue;
use App\Queries\ReviewContext;
use App\Services\Approvals\ApprovalService;
use App\Support\Approvals\PreflightCheck;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Review queue - Admin')] class extends Component {

    public const LIST_LIMIT = 100;

    #[Url]
    public ?string $type = null;

    #[Locked]
    public ?int $currentId = null;

    /**
     * The list as it stood when the admin started, plus anything that
     * arrived since. Decided rows stay in place, greyed, so the list
     * never jumps under the cursor.
     *
     * @var list<int>
     */
    #[Locked]
    public array $listIds = [];

    public string $note = '';

    /** @var array{tone: string, message: string}|null */
    public ?array $flash = null;

    public function mount(?Approval $approval = null): void
    {
        if ($this->type && ! ApprovalType::tryFrom($this->type)) {
            $this->type = null;
        }

        $this->listIds = $this->waitingIds();

        if ($approval?->exists) {
            if (! in_array($approval->id, $this->listIds, true)) {
                array_unshift($this->listIds, $approval->id);
            }
            $this->currentId = $approval->id;

            return;
        }

        $this->currentId = $this->firstDecidableId();
    }

    // ===== Data =====

    #[Computed]
    public function items()
    {
        $order = array_flip($this->listIds);

        return Approval::query()
            ->whereIn('id', $this->listIds)
            ->with(['approvable', 'user', 'decider', 'firstApprover'])
            ->get()
            ->filter(fn (Approval $a) => $a->approvable !== null)
            ->sortBy(fn (Approval $a) => $order[$a->id] ?? PHP_INT_MAX)
            ->values();
    }

    #[Computed]
    public function current(): ?Approval
    {
        return $this->currentId
            ? $this->items->firstWhere('id', $this->currentId)
                ?? Approval::with(['approvable', 'user', 'decider', 'firstApprover'])->find($this->currentId)
            : null;
    }

    #[Computed]
    public function evidence()
    {
        return $this->current?->approvable?->evidence();
    }

    /**
     * @return list<PreflightCheck>
     */
    #[Computed]
    public function checks(): array
    {
        return $this->current?->approvable?->preflightChecks() ?? [];
    }

    #[Computed]
    public function memberCard(): ?array
    {
        return $this->current?->user ? app(ReviewContext::class)->memberCard($this->current->user) : null;
    }

    #[Computed]
    public function history()
    {
        return $this->current ? app(ReviewContext::class)->history($this->current) : collect();
    }

    #[Computed]
    public function counts(): array
    {
        return app(ApprovalQueue::class)->countsByType();
    }

    #[Computed]
    public function remaining(): int
    {
        return $this->items->filter(fn (Approval $a) => $a->state === ApprovalState::WaitingOnAdmin)->count();
    }

    #[Computed]
    public function blockedReason(): ?string
    {
        return $this->current?->approvalBlockedReason(auth()->user());
    }

    // ===== Navigation =====

    public function select(int $id): void
    {
        if (! in_array($id, $this->listIds, true)) {
            if (! Approval::whereKey($id)->exists()) {
                return;
            }
            $this->listIds[] = $id;
            unset($this->items);
        }

        $this->currentId = $id;
        $this->note = '';
        $this->flash = null;
        $this->resetErrorBag();
        $this->forgetCurrent();
        $this->syncUrl();
    }

    public function move(int $direction): void
    {
        $ids = $this->items->pluck('id')->all();
        $index = array_search($this->currentId, $ids, true);

        if ($ids === []) {
            return;
        }

        $target = $index === false ? 0 : max(0, min(count($ids) - 1, $index + ($direction >= 0 ? 1 : -1)));
        $this->select($ids[$target]);
    }

    public function filter(?string $type = null): void
    {
        $this->type = $type && ApprovalType::tryFrom($type) ? $type : null;
        $this->listIds = $this->waitingIds();
        $this->currentId = $this->firstDecidableId();
        $this->note = '';
        $this->flash = null;
        unset($this->items, $this->remaining);
        $this->forgetCurrent();
        $this->syncUrl();
    }

    // ===== Decisions =====

    public function approve(): void
    {
        $this->decide(ApprovalDecision::Approve, next: false);
    }

    public function approveAndNext(): void
    {
        $this->decide(ApprovalDecision::Approve, next: true);
    }

    public function decline(): void
    {
        $this->decide(ApprovalDecision::Decline, next: false);
    }

    public function requestChanges(): void
    {
        $this->decide(ApprovalDecision::RequestChanges, next: false);
    }

    private function decide(ApprovalDecision $decision, bool $next): void
    {
        $approval = $this->current;
        $actor = auth()->user();
        $this->resetErrorBag();
        $this->flash = null;

        if (! $approval) {
            return;
        }

        $failed = collect($this->checks)->reject(fn (PreflightCheck $c) => $c->passed);
        if ($decision === ApprovalDecision::Approve && $failed->isNotEmpty() && trim($this->note) === '') {
            $this->addError('note', 'Add a note: approving with a failed check ('.$failed->pluck('label')->implode(', ').') needs a reason on record.');

            return;
        }

        [$note, $items] = $decision === ApprovalDecision::RequestChanges
            ? [null, preg_split('/\R/', $this->note) ?: []]
            : [$this->note, []];

        try {
            $result = app(ApprovalService::class)->decide($approval, $decision, $actor, $note, $items);
        } catch (ApprovalAlreadyDecided $e) {
            $this->flash = ['tone' => 'error', 'message' => $e->describe()];
            $this->afterDecision();

            return;
        } catch (ApprovalNotAllowed $e) {
            $this->flash = ['tone' => 'error', 'message' => $e->getMessage()];

            return;
        } catch (ValidationException $e) {
            $this->addError('note', collect($e->errors())->flatten()->first());

            return;
        }

        $summary = $approval->approvable?->summaryLine() ?? 'item';
        $this->flash = ['tone' => 'success', 'message' => match (true) {
            $result->awaitsSecondSignature() => 'First signature recorded on '.$summary.'. A second admin needs to approve.',
            $decision === ApprovalDecision::Approve => 'Approved: '.$summary.'.',
            $decision === ApprovalDecision::Decline => 'Declined: '.$summary.'. The member has been told why.',
            default => 'Sent back to the member: '.$summary.'.',
        }];
        $this->note = '';
        $this->afterDecision();

        if ($next) {
            $nextApproval = app(ApprovalQueue::class)->nextAfter($result, $this->type, $actor);

            if ($nextApproval) {
                if (! in_array($nextApproval->id, $this->listIds, true)) {
                    $this->listIds[] = $nextApproval->id;
                    unset($this->items);
                }
                $this->currentId = $nextApproval->id;
                $this->forgetCurrent();
                $this->syncUrl();
            } else {
                $this->flash['message'] .= ' Nothing else is waiting on you.';
            }
        }
    }

    private function afterDecision(): void
    {
        foreach ($this->waitingIds() as $id) {
            if (! in_array($id, $this->listIds, true)) {
                $this->listIds[] = $id;
            }
        }

        unset($this->items, $this->counts, $this->remaining);
        $this->forgetCurrent();
    }

    // ===== Helpers =====

    /**
     * @return list<int>
     */
    private function waitingIds(): array
    {
        return app(ApprovalQueue::class)->waitingOnAdmin($this->type)
            ->limit(self::LIST_LIMIT)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function firstDecidableId(): ?int
    {
        $actor = auth()->user();

        return $this->items->first(fn (Approval $a) => $a->approvalBlockedReason($actor) === null)?->id
            ?? $this->items->first()?->id;
    }

    private function forgetCurrent(): void
    {
        unset($this->current, $this->evidence, $this->checks, $this->memberCard, $this->history, $this->blockedReason);
    }

    private function syncUrl(): void
    {
        $url = $this->currentId
            ? route('admin.review.show', ['approval' => $this->currentId] + array_filter(['type' => $this->type]))
            : route('admin.review.index', array_filter(['type' => $this->type]));

        $this->js('history.replaceState(history.state, "", '.json_encode($url).')');
    }
}; ?>

<div class="flex flex-col gap-4"
    x-data="{
        hint: null,
        typing(e) {
            const t = e.target;
            return !! t && (['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName) || t.isContentEditable);
        },
        needsNote(action) {
            if ($wire.note.trim() !== '') return false;
            this.hint = action === 'd' ? 'Type the reason for declining, then press Decline.' : 'Type what needs to change, then press Request changes.';
            this.$refs.note?.focus();
            return true;
        },
        key(e) {
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            if (e.key === 'Escape') { if (this.typing(e)) e.target.blur(); this.hint = null; return; }
            if (this.typing(e)) return;
            const k = e.key.toLowerCase();
            if (k === 'enter' && ['BUTTON', 'A'].includes(e.target.tagName)) return;
            const actions = {
                j: () => $wire.move(1),
                k: () => $wire.move(-1),
                a: () => $wire.approve(),
                enter: () => $wire.approveAndNext(),
                r: () => this.needsNote('r') || $wire.requestChanges(),
                d: () => this.needsNote('d') || $wire.decline(),
            };
            if (actions[k]) { e.preventDefault(); this.hint = null; actions[k](); }
        },
    }"
    x-on:keydown.window="key($event)">

    <div class="flex min-h-[calc(100vh-10rem)] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

        {{-- Left: the queue --}}
        <aside class="hidden w-[330px] flex-none flex-col border-r border-zinc-200 dark:border-zinc-800 lg:flex">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <h1 class="text-sm font-semibold text-zinc-900 dark:text-white">Review queue</h1>
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-nrapa-blue dark:bg-nrapa-blue/20 dark:text-blue-300">
                        {{ $this->remaining }} of {{ $this->items->count() }} left
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button type="button" wire:click="filter(null)"
                        @class(['rounded-full px-3 py-1 text-xs', 'bg-nrapa-blue font-semibold text-white' => ! $type, 'bg-zinc-100 font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $type])>All</button>
                    @foreach(ApprovalType::cases() as $case)
                        @php $n = $this->counts[$case->value]; @endphp
                        <button type="button" wire:click="filter('{{ $case->value }}')"
                            @class([
                                'rounded-full px-3 py-1 text-xs',
                                'bg-nrapa-blue font-semibold text-white' => $type === $case->value,
                                'bg-zinc-100 font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $type !== $case->value && $n > 0,
                                'bg-zinc-50 font-medium text-zinc-400 dark:bg-zinc-800/50 dark:text-zinc-500' => $type !== $case->value && $n === 0,
                            ])>{{ $case->pluralLabel() }} {{ $n }}</button>
                    @endforeach
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse($this->items as $row)
                    <x-admin.approval-row :approval="$row" variant="list" :current="$row->id === $currentId" />
                @empty
                    <p class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Nothing is waiting on an admin.</p>
                @endforelse
            </div>

            <div class="border-t border-zinc-200 bg-zinc-50 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">J / K</strong> move ·
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">A</strong> approve ·
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">Enter</strong> approve &amp; next ·
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">R</strong> request changes ·
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">D</strong> decline ·
                <strong class="font-semibold text-zinc-700 dark:text-zinc-200">Esc</strong> leave the note
            </div>
        </aside>

        {{-- Middle and right --}}
        <section class="flex min-w-0 flex-1 flex-col bg-zinc-100 dark:bg-zinc-950/40">
            @if($approval = $this->current)
                @php
                    $item = $approval->approvable;
                    $card = $this->memberCard;
                    $ageBadge = match ($approval->ageBand()) {
                        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                    };
                @endphp

                <header class="flex items-center gap-4 border-b border-zinc-200 bg-white px-6 py-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $approval->user?->initials() ?? '?' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $item?->summaryLine() }}</h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $approval->type->badgeClasses() }}">{{ $approval->type->label() }}</span>
                            @if($approval->state === ApprovalState::WaitingOnAdmin)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $ageBadge }}">Waiting {{ $approval->ageLabel() }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $approval->state->label() }}</span>
                            @endif
                            @if($approval->awaitsSecondSignature())
                                <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">1 of 2 · first signed by {{ $approval->firstApprover?->name }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $approval->user?->formatted_member_number }}
                            @if($card['membership']) · {{ $card['membership']->type?->name }} · paid to {{ $card['paid_to'] }} @endif
                            · submitted {{ $approval->submitted_at?->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <div class="flex flex-none items-center gap-2">
                        @if($url = $approval->detailUrl())
                            <a href="{{ $url }}" wire:navigate class="hidden px-4 py-2 text-sm font-medium rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 xl:inline-flex">Full record</a>
                        @endif
                        @if($approval->user)
                            <a href="{{ route('admin.members.show', $approval->user) }}" wire:navigate class="px-4 py-2 text-sm font-medium rounded-lg border border-zinc-300 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Open member record</a>
                        @endif
                    </div>
                </header>

                <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-6 xl:flex-row xl:items-start">
                    <div class="flex min-w-0 flex-1 flex-col gap-5">
                        @if($evidence = $this->evidence)
                            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $evidence->title }}</h3>
                                    @if($evidence->badge)
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $evidence->badgeTone === 'emerald',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $evidence->badgeTone === 'amber',
                                            'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200' => $evidence->badgeTone === 'violet',
                                            'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! in_array($evidence->badgeTone, ['emerald', 'amber', 'violet'], true),
                                        ])>{{ $evidence->badge }}</span>
                                    @endif
                                </div>
                                <div class="p-6">
                                    @include($evidence->view, $evidence->data)
                                </div>
                            </div>
                        @endif

                        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Before approving</h3>
                            </div>
                            <ul class="grid gap-x-5 gap-y-3 px-6 py-4 sm:grid-cols-2" data-preflight>
                                @foreach($this->checks as $check)
                                    <li class="flex gap-2.5 text-[13px] text-zinc-700 dark:text-zinc-300" data-check="{{ $check->key }}" data-passed="{{ $check->passed ? '1' : '0' }}">
                                        @if($check->passed)
                                            <svg class="mt-0.5 h-4 w-4 flex-none text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7"/></svg>
                                        @else
                                            <svg class="mt-0.5 h-4 w-4 flex-none text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                        @endif
                                        <span @class(['text-red-800 dark:text-red-300' => ! $check->passed])>{{ $check->reason }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="flex w-full flex-none flex-col gap-5 xl:w-[300px]">
                        @if($card)
                            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Member</h3>
                                <dl class="mt-3 flex flex-col gap-2.5 text-[13px]">
                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Name</dt><dd class="text-right font-medium text-zinc-900 dark:text-white">{{ $approval->user->name }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Membership</dt><dd class="text-right font-medium text-zinc-900 dark:text-white">{{ $card['membership']?->type?->name ?? 'None' }}</dd></div>
                                    @if($card['status'])
                                        <div class="flex justify-between gap-3"><dt class="text-zinc-500">Status</dt><dd><span @class(['rounded-full px-2 py-0.5 text-xs font-medium', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' => $card['status'] === 'active', 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $card['status'] !== 'active'])>{{ str($card['status'])->replace('_', ' ')->ucfirst() }}</span></dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-zinc-500">Paid to</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $card['paid_to'] }}</dd></div>
                                    @endif
                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Member since</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $card['member_since'] ?? '—' }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Documents</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $card['documents_valid'] }} valid, {{ $card['documents_expiring'] }} expiring</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Endorsements</dt><dd class="font-medium text-zinc-900 dark:text-white">{{ $card['endorsements'] ? $card['endorsements'].' issued'.($card['last_endorsement_year'] ? ' ('.$card['last_endorsement_year'].')' : '') : 'None' }}</dd></div>
                                </dl>
                                @if($card['hold'])
                                    <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-800 dark:bg-red-900/20 dark:text-red-300">Disciplinary hold: {{ $card['hold'] }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">History</h3>
                            <ol class="mt-3 flex flex-col gap-3">
                                @foreach($this->history as $entry)
                                    <li class="flex gap-2.5">
                                        <span @class(['mt-1.5 h-[7px] w-[7px] flex-none rounded-full', 'bg-nrapa-blue' => $entry['current'], 'bg-zinc-300 dark:bg-zinc-600' => ! $entry['current']])></span>
                                        <div>
                                            <p class="text-[13px] text-zinc-700 dark:text-zinc-300">{{ $entry['label'] }}</p>
                                            <p class="text-xs text-zinc-400">{{ $entry['at']?->format('d M Y, H:i') }}@if($entry['by']) · by {{ $entry['by'] }}@endif</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Decision bar --}}
                <footer class="border-t border-zinc-200 bg-white px-6 pb-3 pt-4 dark:border-zinc-800 dark:bg-zinc-900">
                    @if($flash)
                        <p @class([
                            'mb-3 rounded-lg px-3 py-2 text-sm',
                            'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300' => $flash['tone'] === 'success',
                            'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300' => $flash['tone'] === 'error',
                        ]) data-flash="{{ $flash['tone'] }}">{{ $flash['message'] }}</p>
                    @endif

                    @if($reason = $this->blockedReason)
                        <p class="py-2 text-sm text-zinc-600 dark:text-zinc-400" data-blocked>{{ $reason }}</p>
                    @else
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                            <div class="flex-1">
                                <textarea x-ref="note" wire:model="note" rows="1"
                                    placeholder="Add a note for the audit trail (required to decline or request changes)…"
                                    class="block w-full resize-y rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-nrapa-blue focus:ring-nrapa-blue dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                                @error('note') <p class="mt-1 text-xs text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                                <p x-show="hint" x-text="hint" x-cloak class="mt-1 text-xs text-amber-700 dark:text-amber-300"></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="decline" wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-red-200 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20 disabled:opacity-50">Decline</button>
                                <button type="button" wire:click="requestChanges" wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-zinc-300 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800 disabled:opacity-50">Request changes</button>
                                <button type="button" wire:click="approve" wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-emerald-200 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20 disabled:opacity-50">Approve</button>
                                <button type="button" wire:click="approveAndNext" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-nrapa-blue text-white hover:bg-nrapa-blue-dark disabled:opacity-50">
                                    Approve &amp; next
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13"/><path d="M13 6l6 6-6 6"/></svg>
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-zinc-400">
                            {{ $item?->approvalConsequence() }}
                            @if($approval->requires_second_approval && ! $approval->first_approved_by)
                                Your approval is the first of two signatures.
                            @endif
                        </p>
                    @endif
                </footer>
            @else
                <div class="flex flex-1 flex-col items-center justify-center p-12 text-center">
                    @if($flash)
                        <p class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">{{ $flash['message'] }}</p>
                    @endif
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">All caught up</h2>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Nothing is waiting on an admin{{ $type ? ' in '.strtolower(ApprovalType::from($type)->pluralLabel()) : '' }}.</p>
                </div>
            @endif
        </section>
    </div>
</div>
