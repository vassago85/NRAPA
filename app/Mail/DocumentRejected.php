<?php

namespace App\Mail;

use App\Models\MemberDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemberDocument $document,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Document Requires Attention',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
