<?php

namespace App\Mail;

use App\Models\EndorsementDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EndorsementDocumentVerified extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EndorsementDocument $document,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Endorsement Document Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.endorsement-document-verified',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
