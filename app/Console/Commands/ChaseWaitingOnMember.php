<?php

namespace App\Console\Commands;

use App\Enums\ApprovalType;
use App\Mail\ApprovalChaseMail;
use App\Models\Approval;
use App\Models\ApprovalChase;
use App\Models\EmailLog;
use App\Queries\ApprovalQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reminds members about approvals waiting on them at 7, 14 and 21 days.
 * Each stage goes out once per waiting spell; a member reached late (say at
 * day 15) gets only the latest due stage, not a burst of catch-up emails.
 */
class ChaseWaitingOnMember extends Command
{
    protected $signature = 'nrapa:chase-waiting-on-member {--dry-run : Report what would be sent without sending}';

    protected $description = 'Email members whose approvals have been waiting on them for 7, 14 or 21 days.';

    /** A recent manual POP reminder stands in for the automatic chase. */
    private const MANUAL_REMINDER_GRACE_DAYS = 3;

    public function handle(ApprovalQueue $queue): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;
        $skipped = 0;

        $queue->waitingOnMember()
            ->reorder()
            ->with(['user', 'approvable'])
            ->chunkById(200, function ($approvals) use ($dryRun, &$sent, &$skipped) {
                foreach ($approvals as $approval) {
                    match ($this->chase($approval, $dryRun)) {
                        'sent' => $sent++,
                        'skipped' => $skipped++,
                        default => null,
                    };
                }
            });

        $this->info(($dryRun ? '[dry run] ' : '')."Chased {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return 'sent'|'skipped'|'not_due'
     */
    private function chase(Approval $approval, bool $dryRun): string
    {
        if (! $approval->approvable) {
            return 'not_due';
        }

        if (! $approval->metaValue('waiting_since')) {
            $approval->withMeta(['waiting_since' => ($approval->updated_at ?? now())->toIso8601String()])->saveQuietly();
        }

        $since = $approval->waitingSince();
        $stage = ApprovalChase::dueStage((int) floor($since->diffInDays(now(), true)));

        if ($stage === null) {
            return 'not_due';
        }

        $alreadyChased = ApprovalChase::query()
            ->where('approval_id', $approval->id)
            ->where('cycle_started_at', $since)
            ->where('stage', '>=', $stage)
            ->exists();

        if ($alreadyChased) {
            return 'not_due';
        }

        $member = $approval->user;

        if (! $member?->canReceiveEmail()) {
            $this->line("  skip approval {$approval->id}: member has no deliverable address");

            return 'skipped';
        }

        if ($approval->type === ApprovalType::Payment
            && $approval->approvable->membership?->pop_reminder_sent_at?->gt(now()->subDays(self::MANUAL_REMINDER_GRACE_DAYS))) {
            $this->line("  skip approval {$approval->id}: manual payment reminder sent recently");

            return 'skipped';
        }

        if ($dryRun) {
            $this->line("  would send day-{$stage} reminder to {$member->email} ({$approval->type->label()})");

            return 'sent';
        }

        $log = EmailLog::create([
            'user_id' => $member->id,
            'to_email' => $member->email,
            'to_name' => $member->name,
            'subject' => ApprovalChaseMail::subjectFor($approval),
            'mailable_class' => ApprovalChaseMail::class,
            'status' => 'queued',
            'metadata' => ['approval_id' => $approval->id, 'stage' => $stage],
        ]);

        try {
            Mail::to($member->email)->send(new ApprovalChaseMail($approval, $stage));
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::warning('Approval chase failed', ['approval_id' => $approval->id, 'stage' => $stage, 'error' => $e->getMessage()]);

            return 'skipped';
        }

        ApprovalChase::create([
            'approval_id' => $approval->id,
            'stage' => $stage,
            'cycle_started_at' => $since,
            'sent_at' => now(),
            'email_log_id' => $log->id,
        ]);

        $this->line("  sent day-{$stage} reminder to {$member->email} ({$approval->type->label()})");

        return 'sent';
    }
}
