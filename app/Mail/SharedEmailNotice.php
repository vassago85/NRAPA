<?php

namespace App\Mail;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SharedEmailNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Membership $membership,
        public string $originalEmail,
        public string $defaultPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA – Your Login Details (' . $this->user->name . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shared-email-notice',
            with: [
                'loginUrl' => url('/login'),
                'settingsUrl' => url('/member/settings'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
