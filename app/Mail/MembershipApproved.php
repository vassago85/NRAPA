<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Membership $membership,
        public string $cardUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to NRAPA – Your Membership is Active!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
