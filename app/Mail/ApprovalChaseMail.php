<?php

namespace App\Mail;

use App\Mail\Concerns\NonEssentialMail;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Automatic reminder about an approval that has been waiting on the member
 * for 7, 14 or 21 days. Classified as non-essential — members can opt out via
 * the footer link; the approval decision itself is sent through
 * ApprovalDecisionMail which stays transactional.
 */
class ApprovalChaseMail extends Mailable
{
    use Queueable, SerializesModels, NonEssentialMail;

    public function __construct(
        public Approval $approval,
        public int $stage,
    ) {}

    public function recipient(): User
    {
        return $this->approval->user;
    }

    public static function subjectFor(Approval $approval): string
    {
        return 'NRAPA: your '.$approval->type->label().' is waiting on you';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::subjectFor($this->approval));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approval-chase',
            with: $this->nonEssentialMailViewData(),
        );
    }

    public function headers(): Headers
    {
        return $this->nonEssentialMailHeaders();
    }

    protected function unsubscribeReason(): string
    {
        return 'approval_chase';
    }
}
