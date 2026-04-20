<?php

use App\Mail\MemberMessageMail;
use App\Models\MemberMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Message')] class extends Component {
    public MemberMessage $thread;

    public string $replyBody = '';

    public function mount(MemberMessage $message): void
    {
        abort_unless($message->user_id === Auth::id(), 403);

        // Walk up to the thread root in case we were handed a reply link
        $this->thread = $message->parent_id
            ? MemberMessage::findOrFail($message->parent_id)
            : $message;

        abort_unless($this->thread->user_id === Auth::id(), 403);

        // Mark all admin->member messages in this thread as read for the member
        MemberMessage::where('user_id', Auth::id())
            ->where('direction', MemberMessage::DIRECTION_ADMIN_TO_MEMBER)
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

        $user = Auth::user();

        $reply = MemberMessage::create([
            'user_id' => $user->id,
            'sent_by_user_id' => $user->id,
            'direction' => MemberMessage::DIRECTION_MEMBER_TO_ADMIN,
            'parent_id' => $this->thread->id,
            'subject' => $this->thread->subject,
            'body' => trim($this->replyBody),
        ]);

        // Email all admins (use setting-configured list if available, fallback to admin users)
        $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OWNER])
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        if (! empty($adminEmails)) {
            try {
                Mail::to($adminEmails)->queue(new MemberMessageMail($reply));
                $reply->update(['email_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to queue member reply email to admins', [
                    'reply_id' => $reply->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'new_member',
                'Member Reply',
                $user->name . ' replied: "' . \Illuminate\Support\Str::limit($reply->body, 80) . '"',
                'low',
            );
        } catch (\Throwable $e) {}

        $this->replyBody = '';
        unset($this->threadMessages);
        session()->flash('success', 'Your reply has been sent.');
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
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $thread->subject }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $this->threadMessages->count() }} {{ \Illuminate\Support\Str::plural('message', $this->threadMessages->count()) }}
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-3">
        @foreach($this->threadMessages as $msg)
            @php $mine = $msg->isFromMember(); @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] rounded-2xl border px-4 py-3 shadow-sm
                    {{ $mine
                        ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800'
                        : 'bg-white border-zinc-200 dark:bg-zinc-900 dark:border-zinc-800' }}">
                    <div class="flex items-center justify-between gap-3 text-xs {{ $mine ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-500 dark:text-zinc-400' }}">
                        <span class="font-medium">
                            @if($mine)
                                You
                            @elseif($msg->sender)
                                {{ $msg->sender->name }} <span class="font-normal">(NRAPA Admin)</span>
                            @else
                                NRAPA Admin
                            @endif
                        </span>
                        <time>{{ $msg->created_at->format('d M Y, H:i') }}</time>
                    </div>
                    <div class="mt-2 whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-100">{{ $msg->body }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Reply to NRAPA</h2>
        <form wire:submit="sendReply" class="space-y-3">
            <textarea wire:model="replyBody" rows="5" maxlength="5000"
                placeholder="Type your reply here. NRAPA admins will see it in their inbox."
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
            @error('replyBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
                    Send Reply
                </button>
            </div>
        </form>
    </div>
</div>
