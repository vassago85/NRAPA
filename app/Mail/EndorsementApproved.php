<?php

namespace App\Mail;

use App\Models\EndorsementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EndorsementApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EndorsementRequest $endorsement,
    ) {}

    public function envelope(): Envelope
    {
        $isIssued = $this->endorsement->status === EndorsementRequest::STATUS_ISSUED;

        return new Envelope(
            subject: $isIssued
                ? 'NRAPA Endorsement Letter Issued'
                : 'NRAPA Endorsement Request Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.endorsement-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
