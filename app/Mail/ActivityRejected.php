<?php

namespace App\Mail;

use App\Models\ShootingActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivityRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShootingActivity $activity,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Activity Requires Attention',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
