<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipApprovalRevoked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Membership $membership,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Membership — Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-approval-revoked',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
