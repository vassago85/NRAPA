<?php

use App\Models\MemberMessage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Admin Inbox')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'filter')]
    public string $filter = 'all'; // all | unread | incoming

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilter(): void { $this->resetPage(); }

    #[Computed]
    public function threads()
    {
        // Build a per-thread summary: for each thread-root this is the latest activity time
        // and how many replies (and incoming unread) exist.
        $search = trim($this->search);

        $query = MemberMessage::query()
            ->whereNull('parent_id')
            ->with(['user:id,name,email,role', 'sender:id,name'])
            ->withCount([
                'replies',
                'replies as incoming_unread_count' => function ($q) {
                    $q->where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                        ->whereNull('read_at');
                },
            ])
            ->addSelect([
                'last_activity_at' => MemberMessage::selectRaw('MAX(created_at)')
                    ->from('member_messages as mm2')
                    ->whereColumn('mm2.parent_id', 'member_messages.id'),
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->filter === 'unread') {
            // Threads where root (if member->admin) is unread, OR any incoming reply is unread
            $query->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                        ->whereNull('read_at');
                })->orWhereHas('replies', function ($r) {
                    $r->where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                        ->whereNull('read_at');
                });
            });
        } elseif ($this->filter === 'incoming') {
            // Only threads that have at least one member->admin message
            $query->where(function ($q) {
                $q->where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                    ->orWhereHas('replies', function ($r) {
                        $r->where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN);
                    });
            });
        }

        return $query->orderByRaw('COALESCE(last_activity_at, created_at) DESC')->paginate(25);
    }

    #[Computed]
    public function incomingUnreadTotal(): int
    {
        return MemberMessage::where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
            ->whereNull('read_at')
            ->count();
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Admin Inbox</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    All message threads between NRAPA admin and members.
                    @if($this->incomingUnreadTotal > 0)
                        <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ $this->incomingUnreadTotal }} new from members
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[220px]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search subject, body, or member…"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            </div>
            <select wire:model.live="filter"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                <option value="all">All threads</option>
                <option value="unread">Unread (from members)</option>
                <option value="incoming">Only member enquiries</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            @if($this->threads->count() === 0)
                <div class="p-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    No threads match your filters.
                </div>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach($this->threads as $t)
                        @php
                            $hasIncomingUnread = $t->incoming_unread_count > 0
                                || ($t->direction === \App\Models\MemberMessage::DIRECTION_MEMBER_TO_ADMIN && ! $t->read_at);
                        @endphp
                        <li>
                            <a href="{{ route('admin.messages.show', $t) }}" wire:navigate
                                class="flex items-start gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors {{ $hasIncomingUnread ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                                <div class="mt-1 shrink-0">
                                    @if($hasIncomingUnread)
                                        <span class="inline-block size-2.5 rounded-full bg-amber-500" title="New from member"></span>
                                    @else
                                        <span class="inline-block size-2.5 rounded-full border border-zinc-300 dark:border-zinc-700"></span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-medium text-zinc-900 dark:text-white {{ $hasIncomingUnread ? 'font-semibold' : '' }}">
                                            {{ $t->subject }}
                                            @if($t->replies_count > 0)
                                                <span class="ml-2 inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                                    {{ $t->replies_count + 1 }} messages
                                                </span>
                                            @endif
                                            @if($t->incoming_unread_count > 0)
                                                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                    {{ $t->incoming_unread_count }} new
                                                </span>
                                            @endif
                                        </h3>
                                        <time class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($t->last_activity_at ?? $t->created_at)->diffForHumans() }}
                                        </time>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($t->body), 180) }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                        @if($t->user)
                                            {{ $t->user->name }} &lt;{{ $t->user->email }}&gt;
                                        @endif
                                        &middot;
                                        @if($t->direction === \App\Models\MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
                                            started by member
                                        @else
                                            started by admin{{ $t->sender ? ' (' . $t->sender->name . ')' : '' }}
                                        @endif
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>{{ $this->threads->links() }}</div>
    </div>
</div>
