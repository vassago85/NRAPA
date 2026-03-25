<?php

namespace App\Mail;

use App\Models\ShootingActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivityApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShootingActivity $activity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA Activity Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
