<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PopResubmitRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Membership $membership,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Please resubmit your NRAPA proof of payment',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pop-resubmit-requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
