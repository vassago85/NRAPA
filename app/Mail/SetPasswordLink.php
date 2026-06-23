<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SetPasswordLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->user->hasSetPassword()
                ? 'NRAPA – Reset Your Password'
                : 'NRAPA – Set Your Password',
        );
    }

    public function content(): Content
    {
        $setPasswordUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->user->email,
        ], false));

        return new Content(
            view: 'emails.set-password-link',
            with: [
                'setPasswordUrl' => $setPasswordUrl,
                'loginUrl' => url('/login'),
                'isReset' => $this->user->hasSetPassword(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
