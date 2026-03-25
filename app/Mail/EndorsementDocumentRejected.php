<?php

namespace App\Mail;

use App\Models\EndorsementDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EndorsementDocumentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EndorsementDocument $document,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Endorsement Document Requires Attention',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.endorsement-document-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
