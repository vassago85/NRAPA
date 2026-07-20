<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the member when an admin deletes their endorsement.
 *
 * The mailable takes an already-serializable snapshot of the endorsement details
 * (name, letter reference, dates, firearm summary) rather than the model itself,
 * because we send this email while the endorsement is being soft-deleted and the
 * row may be reloaded/discarded by the caller. Snapshotting the primitives is safer.
 */
class EndorsementDeleted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $memberName,
        public string $memberEmail,
        public ?string $letterReference,
        public ?string $endorsementTypeLabel,
        public ?string $firearmSummary,
        public ?string $issuedAtDisplay,
        public ?string $expiresAtDisplay,
        public bool $wasIssued,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->wasIssued && $this->letterReference
                ? "NRAPA Endorsement Letter {$this->letterReference} No Longer Valid"
                : 'NRAPA Endorsement Removed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.endorsement-deleted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
