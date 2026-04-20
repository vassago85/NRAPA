<?php

namespace App\Livewire\Admin;

use App\Mail\MemberMessageMail;
use App\Models\MemberMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Component;

class MemberMessageQuick extends Component
{
    public bool $open = false;
    public ?int $userId = null;
    public string $userName = '';
    public string $userEmail = '';
    public string $subject = '';
    public string $body = '';

    #[On('admin-send-message')]
    public function show(int $userId, ?string $userName = null, ?string $userEmail = null): void
    {
        $actor = Auth::user();
        if (! $actor || ! in_array($actor->role ?? 'member', ['admin', 'owner', 'developer'], true)) {
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            session()->flash('error', 'Member not found.');
            return;
        }

        $this->userId = $user->id;
        $this->userName = $userName ?: ($user->name ?? '');
        $this->userEmail = $userEmail ?: ($user->email ?? '');
        $this->subject = '';
        $this->body = '';
        $this->resetErrorBag();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->userId = null;
        $this->userName = '';
        $this->userEmail = '';
        $this->subject = '';
        $this->body = '';
        $this->resetErrorBag();
    }

    public function send(): void
    {
        $actor = Auth::user();
        if (! $actor || ! in_array($actor->role ?? 'member', ['admin', 'owner', 'developer'], true)) {
            abort(403);
        }

        $this->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'userId' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($this->userId);

        $message = MemberMessage::create([
            'user_id' => $user->id,
            'sent_by_user_id' => $actor->id,
            'direction' => MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
            'subject' => trim($this->subject),
            'body' => trim($this->body),
        ]);

        if ($user->email) {
            try {
                Mail::to($user->email)->queue(new MemberMessageMail($message));
                $message->update(['email_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to queue quick member message email', [
                    'user_id' => $user->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'new_member',
                'Member Message Sent',
                $actor->name . " sent \"{$message->subject}\" to {$user->name} ({$user->email}).",
                'low',
            );
        } catch (\Throwable $e) {}

        session()->flash('success', "Message sent to {$user->name}.");
        $this->close();
    }

    public function render()
    {
        return view('livewire.admin.member-message-quick');
    }
}
