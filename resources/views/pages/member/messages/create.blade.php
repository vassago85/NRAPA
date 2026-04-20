<?php

use App\Mail\MemberMessageMail;
use App\Models\MemberMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Message')] class extends Component {
    public string $subject = '';
    public string $body = '';

    public function send(): void
    {
        $this->validate([
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:5000',
        ]);

        $user = Auth::user();

        $message = MemberMessage::create([
            'user_id' => $user->id,
            'sent_by_user_id' => $user->id,
            'direction' => MemberMessage::DIRECTION_MEMBER_TO_ADMIN,
            'parent_id' => null,
            'subject' => trim($this->subject),
            'body' => trim($this->body),
        ]);

        $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OWNER])
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        if (! empty($adminEmails)) {
            try {
                Mail::to($adminEmails)->queue(new MemberMessageMail($message));
                $message->update(['email_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to queue member enquiry email to admins', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'new_member',
                'Member Enquiry',
                $user->name . ': ' . \Illuminate\Support\Str::limit($message->subject, 80),
                'default',
            );
        } catch (\Throwable $e) {}

        session()->flash('success', 'Your message has been sent to NRAPA admin.');
        $this->redirectRoute('messages.show', ['message' => $message->id], navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <a href="{{ route('messages.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Messages
        </a>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">New message to NRAPA</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Send an enquiry or question directly to the NRAPA admin team. They will reply to this thread.
        </p>

        <form wire:submit="send" class="mt-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Subject</label>
                <input type="text" wire:model="subject" maxlength="150" required
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Message</label>
                <textarea wire:model="body" rows="8" maxlength="5000" required
                    placeholder="Write your enquiry here..."
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('messages.index') }}" wire:navigate
                    class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"/></svg>
                    Send Message
                </button>
            </div>
        </form>
    </div>
</div>
