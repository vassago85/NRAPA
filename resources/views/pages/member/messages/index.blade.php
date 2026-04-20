<?php

use App\Models\MemberMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Messages')] class extends Component {
    #[Computed]
    public function messages()
    {
        return MemberMessage::where('user_id', Auth::id())
            ->with('sender:id,name')
            ->latest()
            ->paginate(25);
    }

    public function markAllRead(): void
    {
        MemberMessage::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        unset($this->messages);
        session()->flash('success', 'All messages marked as read.');
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Messages</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Messages from the NRAPA admin team.</p>
        </div>
        @php $unread = \App\Models\MemberMessage::where('user_id', auth()->id())->whereNull('read_at')->count(); @endphp
        @if($unread > 0)
            <button wire:click="markAllRead"
                class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 transition-colors">
                Mark all as read
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if($this->messages->count() === 0)
        <div class="rounded-2xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-800 dark:bg-zinc-900">
            <svg class="mx-auto size-12 text-zinc-300 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H4a2 2 0 00-2 2v7a2 2 0 002 2h2v4l4-4h4m-4-5h.01M14 13h.01M9 13h.01"/></svg>
            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No messages yet. Messages from NRAPA admin will appear here.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($this->messages as $msg)
                <li>
                    <a href="{{ route('messages.show', $msg) }}" wire:navigate
                        class="flex items-start gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors {{ ! $msg->read_at ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                        <div class="mt-1 shrink-0">
                            @if(! $msg->read_at)
                                <span class="inline-block size-2.5 rounded-full bg-blue-500" title="Unread"></span>
                            @else
                                <span class="inline-block size-2.5 rounded-full border border-zinc-300 dark:border-zinc-700"></span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-medium text-zinc-900 dark:text-white {{ ! $msg->read_at ? 'font-semibold' : '' }}">
                                    {{ $msg->subject }}
                                </h3>
                                <time class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $msg->created_at->diffForHumans() }}</time>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ \Illuminate\Support\Str::limit(strip_tags($msg->body), 180) }}
                            </p>
                            @if($msg->sender)
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">from NRAPA &mdash; {{ $msg->sender->name }}</p>
                            @endif
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        <div>{{ $this->messages->links() }}</div>
    @endif
</div>
