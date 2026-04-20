<?php

namespace App\Mail;

use App\Models\MemberMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MemberMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NRAPA: ' . $this->message->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-message',
            with: [
                'memberMessage' => $this->message,
                'user' => $this->message->user,
                'sender' => $this->message->sender,
                'inboxUrl' => url('/messages'),
            ],
        );
    }
}
