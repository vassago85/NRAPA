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
        $subject = $this->message->parent_id ? 'Re: ' . $this->message->subject : $this->message->subject;

        return new Envelope(
            subject: 'NRAPA: ' . $subject,
        );
    }

    public function content(): Content
    {
        $isFromAdmin = $this->message->isFromAdmin();
        // Thread root id (use this message if it's a root, otherwise its parent)
        $rootId = $this->message->parent_id ?? $this->message->id;

        // Where the recipient should go to read the thread
        $threadUrl = $isFromAdmin
            ? url('/messages/' . $rootId)
            : url('/admin/messages/' . $rootId);

        return new Content(
            view: 'emails.member-message',
            with: [
                'memberMessage' => $this->message,
                'user' => $this->message->user,
                'sender' => $this->message->sender,
                'isFromAdmin' => $isFromAdmin,
                'threadUrl' => $threadUrl,
            ],
        );
    }
}
