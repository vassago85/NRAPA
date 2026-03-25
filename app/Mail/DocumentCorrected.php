<?php

namespace App\Mail;

use App\Models\MemberDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentCorrected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemberDocument $document,
        public array $changes,
        public string $correctedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA — Your Document Details Were Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-corrected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
