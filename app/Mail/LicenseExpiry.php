<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserFirearm;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// NOTE: Not implementing ShouldQueue. Whether to queue is decided by the
// dispatcher (Mail::send vs Mail::later vs Mail::queue) — keeping the Mailable
// neutral lets --throttle=0 actually send synchronously when we want immediate
// delivery, instead of silently going to the queue.
class LicenseExpiry extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public UserFirearm $firearm,
        public int $daysUntilExpiry,
    ) {
        $this->subject = match (true) {
            $this->daysUntilExpiry <= 7 => "URGENT: Firearm License Expires in {$this->daysUntilExpiry} Days",
            $this->daysUntilExpiry <= 30 => "Firearm License Expiring Soon - {$this->daysUntilExpiry} Days",
            default => "Reminder: Firearm License Expiring in {$this->daysUntilExpiry} Days",
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.license-expiry',
        );
    }
}
