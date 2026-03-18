<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TermsAcceptanceRequiredMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Accept NRAPA Terms & Conditions',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $acceptUrl = route('terms.accept');

        return new Content(
            view: 'emails.terms-acceptance-required',
            with: [
                'user' => $this->user,
                'acceptUrl' => $acceptUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
