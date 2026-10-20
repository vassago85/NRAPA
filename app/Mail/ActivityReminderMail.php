<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Year-end "upload your {{ year }} activities" reminder. Addressed to members
 * who are still short of the minimum approved activities for the current
 * activity year. The deadline on the body is 31 October of that year.
 */
class ActivityReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $year,
        public int $required,
        public int $approvedSoFar,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "NRAPA: Upload your {$this->year} shooting activities",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-reminder',
            with: [
                'user' => $this->user,
                'year' => $this->year,
                'required' => $this->required,
                'approvedSoFar' => $this->approvedSoFar,
                'outstanding' => max(0, $this->required - $this->approvedSoFar),
                'deadline' => \Carbon\Carbon::create($this->year, 10, 31),
                'submitUrl' => url('/activities/submit'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
