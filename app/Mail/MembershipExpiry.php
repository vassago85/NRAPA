<?php

namespace App\Mail;

use App\Models\Membership;
use App\Models\MembershipRenewalReminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// NOTE: Intentionally NOT implementing ShouldQueue on the Mailable itself.
// Whether to queue is decided by the dispatcher (Mail::send vs Mail::later vs Mail::queue),
// so the command can choose synchronous-send (--throttle=0) vs staggered-queue (--throttle>0)
// without the Mailable forcing one path. Implementing ShouldQueue here causes Mail::send()
// to silently dispatch to the queue, which made --throttle=0 not actually send synchronously
// and hid Mailgun delivery failures behind the queue worker.
class MembershipExpiry extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public int $daysUntilExpiry;

    /**
     * @param  string  $kind  one of MembershipRenewalReminder::KIND_* constants
     */
    public function __construct(
        public User $user,
        public Membership $membership,
        public string $kind,
    ) {
        // Negative when already expired.
        $this->daysUntilExpiry = (int) now()->startOfDay()->diffInDays($this->membership->expires_at, false);

        $expiryDate = $this->membership->expires_at?->format('d M Y') ?? 'soon';

        $this->subject = match ($this->kind) {
            MembershipRenewalReminder::KIND_EXPIRED => "Your NRAPA membership has expired ({$expiryDate})",
            MembershipRenewalReminder::KIND_SEVEN_DAYS => "URGENT: NRAPA membership expires in {$this->daysUntilExpiry} day".($this->daysUntilExpiry === 1 ? '' : 's'),
            MembershipRenewalReminder::KIND_THIRTY_DAYS => "NRAPA membership renewal due — expires {$expiryDate}",
            default => "NRAPA membership renewal reminder",
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-expiry',
        );
    }
}
