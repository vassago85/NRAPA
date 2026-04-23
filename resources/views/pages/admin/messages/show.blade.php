<?php

use App\Mail\MemberMessageMail;
use App\Models\MemberMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Conversation')] class extends Component {
    public MemberMessage $thread;

    public string $replyBody = '';

    public function mount(MemberMessage $message): void
    {
        $this->thread = $message->parent_id
            ? MemberMessage::findOrFail($message->parent_id)
            : $message;

        // Mark all member->admin messages in this thread as read for the admins
        MemberMessage::where('direction', MemberMessage::DIRECTION_MEMBER_TO_ADMIN)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->where('id', $this->thread->id)
                    ->orWhere('parent_id', $this->thread->id);
            })
            ->update(['read_at' => now()]);
    }

    #[Computed]
    public function threadMessages()
    {
        return MemberMessage::with('sender:id,name,role')
            ->where(function ($q) {
                $q->where('id', $this->thread->id)
                    ->orWhere('parent_id', $this->thread->id);
            })
            ->orderBy('created_at')
            ->get();
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|max:5000',
        ]);

        $admin = Auth::user();
        $member = $this->thread->user;

        $reply = MemberMessage::create([
            'user_id' => $member->id,
            'sent_by_user_id' => $admin->id,
            'direction' => MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
            'parent_id' => $this->thread->id,
            'subject' => $this->thread->subject,
            'body' => trim($this->replyBody),
        ]);

        if ($member->email) {
            try {
                Mail::to($member->email)->send(new MemberMessageMail($reply));
                $reply->update(['email_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to send admin reply to member', [
                    'reply_id' => $reply->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->replyBody = '';
        unset($this->threadMessages);
        session()->flash('success', 'Reply sent to the member.');
    }

    public function deleteMessage(int $id): void
    {
        $msg = MemberMessage::findOrFail($id);

        // If we delete the thread root, delete replies too
        if ($msg->id === $this->thread->id) {
            MemberMessage::where('parent_id', $this->thread->id)->delete();
            $msg->delete();
            session()->flash('success', 'Thread deleted.');
            $this->redirectRoute('admin.messages.index', navigate: true);
            return;
        }

        $msg->delete();
        unset($this->threadMessages);
        session()->flash('success', 'Message deleted.');
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.messages.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Inbox
            </a>
            @if($thread->user)
                <a href="{{ route('admin.members.show', $thread->user) }}" wire:navigate
                    class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    View member profile →
                </a>
            @endif
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $thread->subject }}</h1>
                    @if($thread->user)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            with <strong>{{ $thread->user->name }}</strong> &lt;{{ $thread->user->email }}&gt;
                        </p>
                    @endif
                </div>
                <button wire:click="deleteMessage({{ $thread->id }})" wire:confirm="Delete this entire thread?"
                    class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Delete thread</button>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-3">
            @foreach($this->threadMessages as $msg)
                @php $fromAdmin = $msg->isFromAdmin(); @endphp
                <div class="flex {{ $fromAdmin ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl border px-4 py-3 shadow-sm
                        {{ $fromAdmin
                            ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800'
                            : 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800' }}">
                        <div class="flex items-center justify-between gap-3 text-xs {{ $fromAdmin ? 'text-blue-700 dark:text-blue-300' : 'text-amber-800 dark:text-amber-300' }}">
                            <span class="font-medium">
                                @if($fromAdmin)
                                    {{ $msg->sender?->name ?? 'NRAPA Admin' }} <span class="font-normal">(Admin)</span>
                                @else
                                    {{ $msg->sender?->name ?? $thread->user?->name }} <span class="font-normal">(Member)</span>
                                @endif
                            </span>
                            <div class="flex items-center gap-2">
                                <time>{{ $msg->created_at->format('d M Y, H:i') }}</time>
                                <button wire:click="deleteMessage({{ $msg->id }})" wire:confirm="Delete this message?"
                                    class="text-red-600 hover:underline dark:text-red-400">delete</button>
                            </div>
                        </div>
                        <div class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-100">{{ $msg->body }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Reply as NRAPA Admin</h2>
            <form wire:submit="sendReply" class="space-y-3">
                <textarea wire:model="replyBody" rows="5" maxlength="5000"
                    placeholder="Type your reply here…"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('replyBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
