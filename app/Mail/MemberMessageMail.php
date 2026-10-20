<?php

namespace App\Mail;

use App\Mail\Concerns\NonEssentialMail;
use App\Models\MemberMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Thread message email. Non-essential ONLY in the admin → member direction
 * (bulk broadcasts, admin replies). The member → admin direction (enquiries,
 * member replies) hits the operational admin inbox and must stay
 * transactional — admins cannot opt out of operational mail.
 */
class MemberMessageMail extends Mailable
{
    use Queueable, SerializesModels, NonEssentialMail;

    public function __construct(public MemberMessage $message) {}

    public function recipient(): User
    {
        // The thread's "owner" is always the member record, regardless of
        // which direction the message is going. For the admin → member path
        // the recipient IS this user; for the member → admin path we treat
        // the mail as transactional via shouldAttachUnsubscribe() below.
        return $this->message->user;
    }

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
            with: array_merge([
                'memberMessage' => $this->message,
                'user' => $this->message->user,
                'sender' => $this->message->sender,
                'isFromAdmin' => $isFromAdmin,
                'threadUrl' => $threadUrl,
            ], $this->nonEssentialMailViewData()),
        );
    }

    public function headers(): Headers
    {
        return $this->nonEssentialMailHeaders();
    }

    protected function shouldAttachUnsubscribe(): bool
    {
        // Only expose the unsubscribe affordance when an admin is reaching
        // out to a member. Member-originated mail is operational.
        return $this->message->isFromAdmin();
    }

    protected function unsubscribeReason(): string
    {
        return 'bulk_member_message';
    }
}
