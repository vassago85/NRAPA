<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Membership $membership) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Transfer Application Received',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transfer-application-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
