<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentInstructions extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Membership $membership,
        public array $bankAccount,
        public string $reference
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $this->membership->loadMissing('previousMembership.type', 'type', 'user');

        return new Envelope(
            subject: $this->membership->isTypeChange()
                ? 'NRAPA Membership Upgrade Payment Instructions'
                : 'NRAPA Membership Payment Instructions',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->membership->loadMissing('previousMembership.type', 'type', 'user');

        return new Content(
            view: 'emails.payment-instructions',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
