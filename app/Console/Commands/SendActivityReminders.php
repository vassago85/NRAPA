<?php

namespace App\Console\Commands;

use App\Mail\ActivityReminderMail;
use App\Models\ActivityReminder;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\MemberMessage;
use App\Models\User;
use App\Support\Members\ActivityReminderRecipients;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send the year-end "upload your activities" reminder.
 *
 * Who gets it: {@see ActivityReminderRecipients::query()} — current members
 * below the activity minimum, excluding lifetime, honorary, and join-year.
 *
 * Delivery: an in-app `MemberMessage` row (so the member sees it in their
 * inbox) and an email when `User::canReceiveEmail()`. Hard-bounced /
 * complained addresses still get the inbox copy.
 *
 * Idempotency: one `activity_reminders` row per (user, year). The DB unique
 * index blocks a re-send inside the same calendar year. Pass `--year=2027` to
 * manually drive a different year, or `--force` to send even to members with
 * an existing row (useful if an admin needs to re-nag).
 *
 * This command is NOT scheduled — the admin triggers it from the admin
 * activities page, or runs it manually when they are ready to go out.
 */
class SendActivityReminders extends Command
{
    protected $signature = 'nrapa:send-activity-reminders
                            {--dry-run : Preview recipients without sending or writing reminder rows}
                            {--year= : Activity year to target (defaults to current)}
                            {--throttle=60 : Seconds to sleep between sends to keep Mailgun happy on bulk runs}
                            {--actor-id= : User ID credited as the sender (for audit log + inbox entry). Defaults to the first owner, else the first admin.}
                            {--force : Send even to members who already received the reminder this year}';

    protected $description = 'Email + inbox-notify active members who still owe shooting activities for the year.';

    protected int $sendIndex = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $throttle = max(0, (int) $this->option('throttle'));
        $year = (int) ($this->option('year') ?: ActivityReminderRecipients::defaultYear());
        $required = ActivityReminderRecipients::requiredCount();
        $actor = $this->resolveActor();

        if ($dryRun) {
            $this->warn('[dry-run] No emails, no inbox messages, no reminder rows.');
        }

        if (! $actor && ! $dryRun) {
            $this->error('No admin/owner user available to send on behalf of. Create one or pass --actor-id.');

            return self::FAILURE;
        }

        $total = ActivityReminderRecipients::count($year);
        $this->info("Target recipients for {$year}: {$total} (minimum {$required} approved activities).");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $tally = ['sent' => 0, 'emailed' => 0, 'inbox_only' => 0, 'skipped' => 0, 'failed' => 0];

        ActivityReminderRecipients::query($year)
            ->chunkById(200, function ($users) use ($year, $required, $actor, $dryRun, $force, $throttle, &$tally) {
                foreach ($users as $user) {
                    $result = $this->processUser($user, $year, $required, $actor, $dryRun, $force, $throttle);
                    $tally[$result]++;
                }
            }, 'users.id', 'id');

        $this->info(sprintf(
            'Done. %d sent (%d emailed, %d inbox only), %d skipped, %d failed.',
            $tally['sent'],
            $tally['emailed'],
            $tally['inbox_only'],
            $tally['skipped'],
            $tally['failed'],
        ));

        return self::SUCCESS;
    }

    /**
     * Process one recipient. Returns one of 'sent', 'skipped', 'failed'.
     */
    protected function processUser(User $user, int $year, int $required, ?User $actor, bool $dryRun, bool $force, int $throttle): string
    {
        // Short-circuit the idempotency check before any work.
        if (! $force) {
            $alreadySent = ActivityReminder::where('user_id', $user->id)
                ->where('year', $year)
                ->exists();
            if ($alreadySent) {
                $this->line("  - skip (already sent) {$user->email}");

                return 'skipped';
            }
        }

        $approved = \App\Support\Members\ActivityReminderRecipients::approvedCountFor($user, $year);

        $this->line(sprintf(
            '  - %s  approved=%d/%d  email=%s  %s',
            $user->email ?: '(no email)',
            $approved,
            $required,
            $user->canReceiveEmail() ? 'yes' : 'no',
            $dryRun ? '[would send]' : 'sending',
        ));

        if ($dryRun) {
            return 'sent';
        }

        // Sleep between real sends (not before the first) so Mailgun isn't burst.
        if ($this->sendIndex > 0 && $throttle > 0) {
            sleep($throttle);
        }

        try {
            $this->deliver($user, $year, $required, $approved, $actor);
            $this->sendIndex++;

            return 'sent';
        } catch (\Throwable $e) {
            Log::error('Failed to send activity reminder', [
                'user_id' => $user->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            $this->error("    Failed: {$e->getMessage()}");

            return 'failed';
        }
    }

    /**
     * Inbox + email + idempotency row for one recipient. Separate method so the
     * admin page can call the same path.
     *
     * @return array{reminder: ActivityReminder, emailed: bool}
     */
    public static function deliverFor(User $user, int $year, int $required, int $approved, User $actor): array
    {
        $body = self::composeBody($year, $required, $approved);

        $message = MemberMessage::create([
            'user_id' => $user->id,
            'sent_by_user_id' => $actor->id,
            'direction' => MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
            'subject' => "Upload your {$year} shooting activities",
            'body' => $body,
        ]);

        $emailed = false;
        if ($user->canReceiveEmail()) {
            $mail = new ActivityReminderMail($user, $year, $required, $approved);
            $renderedBody = null;
            try {
                $renderedBody = $mail->render();
            } catch (\Throwable) {
                // Fine — audit row just won't carry a body preview.
            }

            $auditRow = EmailLog::log(
                toEmail: $user->email,
                subject: $mail->envelope()->subject,
                mailableClass: ActivityReminderMail::class,
                toName: $user->name,
                userId: $user->id,
                body: $renderedBody,
                metadata: [
                    'year' => $year,
                    'required' => $required,
                    'approved_so_far' => $approved,
                ],
                status: 'queued',
            );

            try {
                Mail::to($user->email)->send($mail);
                $message->update(['email_sent_at' => now()]);
                $emailed = true;
            } catch (\Throwable $e) {
                $auditRow?->refresh();
                if ($auditRow && $auditRow->status === 'queued') {
                    $auditRow->update([
                        'status' => 'failed',
                        'error_message' => \Illuminate\Support\Str::limit($e->getMessage(), 500),
                    ]);
                }
                throw $e;
            }
        }

        try {
            $reminder = ActivityReminder::create([
                'user_id' => $user->id,
                'year' => $year,
                'emailed' => $emailed,
                'sent_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Unique index tripped (another worker beat us to it). Treat as sent.
            $reminder = ActivityReminder::where('user_id', $user->id)
                ->where('year', $year)
                ->firstOrFail();
        }

        AuditLog::log('activity_reminder.sent', $user, null, [
            'year' => $year,
            'required' => $required,
            'approved_so_far' => $approved,
            'emailed' => $emailed,
            'member_message_id' => $message->id,
        ], $actor);

        return ['reminder' => $reminder, 'emailed' => $emailed];
    }

    protected function deliver(User $user, int $year, int $required, int $approved, User $actor): array
    {
        return self::deliverFor($user, $year, $required, $approved, $actor);
    }

    public static function composeBody(int $year, int $required, int $approved): string
    {
        $outstanding = max(0, $required - $approved);
        $deadline = \Carbon\Carbon::create($year, 10, 31)->format('d F Y');

        $lines = [
            "To stay compliant for {$year}, NRAPA members need at least {$required} approved shooting activities dated between 1 January and 31 October {$year}.",
            '',
            "Your record so far: {$approved} approved / {$required} required ({$outstanding} outstanding).",
            '',
            "Please log in and submit any outstanding activities before {$deadline}. Only approved submissions count toward your compliance record.",
            '',
            'Submit at: ' . url('/activities/submit'),
        ];

        return implode("\n", $lines);
    }

    protected function resolveActor(): ?User
    {
        $id = $this->option('actor-id');
        if ($id) {
            return User::find((int) $id);
        }

        // Pick an owner first, then developer, then admin. Portable across
        // MySQL / SQLite / PostgreSQL — MySQL's FIELD() is not supported in
        // the SQLite test fixture or in Postgres.
        foreach ([User::ROLE_OWNER, User::ROLE_DEVELOPER, User::ROLE_ADMIN] as $role) {
            $actor = User::query()->where('role', $role)->orderBy('id')->first();
            if ($actor) {
                return $actor;
            }
        }

        return null;
    }
}
