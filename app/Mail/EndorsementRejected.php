<?php

namespace App\Mail;

use App\Models\EndorsementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EndorsementRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EndorsementRequest $endorsement,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Endorsement Request Update',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.endorsement-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
