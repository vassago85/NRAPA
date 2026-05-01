<?php

namespace App\Mail;

use App\Models\Membership;
use App\Models\MembershipRenewalReminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipExpiry extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $subject;

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
