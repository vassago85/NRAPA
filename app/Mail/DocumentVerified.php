<?php

namespace App\Mail;

use App\Models\MemberDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentVerified extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemberDocument $document,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Document Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-verified',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
