<?php

namespace App\Mail;

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent by the approval queue when the approved/declined/changes-requested
 * record has no mail of its own for that outcome.
 */
class ApprovalDecisionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $items
     */
    public function __construct(
        public Approval $approval,
        public ApprovalDecision $decision,
        public ?string $note = null,
        public array $items = [],
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->approval->type->label();

        return new Envelope(
            subject: match ($this->decision) {
                ApprovalDecision::Approve => "NRAPA: your {$label} has been approved",
                ApprovalDecision::Decline => "NRAPA: your {$label} was not approved",
                ApprovalDecision::RequestChanges => "NRAPA: changes needed on your {$label}",
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.approval-decision');
    }
}
