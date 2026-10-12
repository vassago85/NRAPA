<?php

namespace App\Mail;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Automatic reminder about an approval that has been waiting on the member
 * for 7, 14 or 21 days.
 */
class ApprovalChaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Approval $approval,
        public int $stage,
    ) {}

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
        return new Content(view: 'emails.approval-chase');
    }
}
