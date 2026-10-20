<?php

namespace App\Mail;

use App\Mail\Concerns\NonEssentialMail;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Chase email: proof-of-payment still outstanding on a membership. Members
 * can opt out via the footer link.
 */
class PopFollowupReminder extends Mailable
{
    use Queueable, SerializesModels, NonEssentialMail;

    public function __construct(
        public Membership $membership,
    ) {}

    public function recipient(): User
    {
        return $this->membership->user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Membership — Proof of Payment Still Outstanding',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pop-followup-reminder',
            with: $this->nonEssentialMailViewData(),
        );
    }

    public function headers(): Headers
    {
        return $this->nonEssentialMailHeaders();
    }

    public function attachments(): array
    {
        return [];
    }

    protected function unsubscribeReason(): string
    {
        return 'pop_followup';
    }
}
