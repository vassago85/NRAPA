<?php

use App\Models\MemberMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Message')] class extends Component {
    public MemberMessage $message;

    public function mount(MemberMessage $message): void
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $this->message = $message;
        $this->message->markRead();
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-4">
    <div>
        <a href="{{ route('messages.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Messages
        </a>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-800">
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $message->subject }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($message->sender)
                        from {{ $message->sender->name }} (NRAPA Admin) &middot;
                    @endif
                    {{ $message->created_at->format('d M Y, H:i') }}
                </p>
            </div>
        </div>
        <div class="prose prose-zinc dark:prose-invert mt-6 max-w-none">
            <p class="whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $message->body }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
        To reply to this message, please contact NRAPA directly. This inbox is read-only.
    </div>
</div>
