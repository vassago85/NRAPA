<?php

namespace App\Console\Commands;

use App\Enums\ApprovalType;
use App\Enums\ChaseOutcome;
use App\Models\Approval;
use App\Models\ApprovalChase;
use App\Queries\ApprovalQueue;
use App\Services\Approvals\ChaseRunner;
use Illuminate\Console\Command;

/**
 * Reminds members about approvals waiting on them at 7, 14 and 21 days.
 * Each stage goes out once per waiting spell; a member reached late (say at
 * day 15) gets only the latest due stage, not a burst of catch-up emails.
 * Every send goes through {@see ChaseRunner} so it stays in step with the
 * manual chase that an admin triggers from the UI.
 */
class ChaseWaitingOnMember extends Command
{
    protected $signature = 'nrapa:chase-waiting-on-member {--dry-run : Report what would be sent without sending}';

    protected $description = 'Email members whose approvals have been waiting on them for 7, 14 or 21 days.';

    /** A recent manual POP reminder stands in for the automatic chase. */
    private const MANUAL_REMINDER_GRACE_DAYS = 3;

    public function handle(ApprovalQueue $queue, ChaseRunner $runner): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;
        $skipped = 0;

        $queue->waitingOnMember()
            ->reorder()
            ->with(['user', 'approvable'])
            ->chunkById(200, function ($approvals) use ($runner, $dryRun, &$sent, &$skipped) {
                foreach ($approvals as $approval) {
                    $outcome = $this->chase($approval, $runner, $dryRun);

                    if ($outcome === null) {
                        continue;
                    }

                    match ($outcome) {
                        ChaseOutcome::Sent => $sent++,
                        ChaseOutcome::AlreadyChasedForStage => null,
                        default => $skipped++,
                    };
                }
            });

        $this->info(($dryRun ? '[dry run] ' : '')."Chased {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function chase(Approval $approval, ChaseRunner $runner, bool $dryRun): ?ChaseOutcome
    {
        if (! $approval->approvable) {
            return null;
        }

        if (! $approval->metaValue('waiting_since')) {
            $approval->withMeta(['waiting_since' => ($approval->updated_at ?? now())->toIso8601String()])->saveQuietly();
        }

        $since = $approval->waitingSince();
        $stage = ApprovalChase::dueStage((int) floor($since->diffInDays(now(), true)));

        if ($stage === null) {
            return null;
        }

        // If an admin manually chased a payment inside the grace window, treat
        // that as the automatic chase for this stage so we don't double up.
        if ($approval->type === ApprovalType::Payment
            && $approval->approvable->membership?->pop_reminder_sent_at?->gt(now()->subDays(self::MANUAL_REMINDER_GRACE_DAYS))) {
            $this->line("  skip approval {$approval->id}: manual payment reminder sent recently");

            return ChaseOutcome::AlreadyChasedForStage;
        }

        if ($dryRun) {
            $member = $approval->user;

            if (! $member?->canReceiveEmail()) {
                $this->line("  skip approval {$approval->id}: member has no deliverable address");

                return ChaseOutcome::MemberCannotReceiveEmail;
            }

            $this->line("  would send day-{$stage} reminder to {$member->email} ({$approval->type->label()})");

            return ChaseOutcome::Sent;
        }

        $outcome = $runner->chase($approval, actor: null, source: 'auto', stage: $stage);

        match ($outcome) {
            ChaseOutcome::Sent => $this->line("  sent day-{$stage} reminder to {$approval->user->email} ({$approval->type->label()})"),
            ChaseOutcome::MemberCannotReceiveEmail => $this->line("  skip approval {$approval->id}: member has no deliverable address"),
            ChaseOutcome::SendFailed => $this->line("  failed to send approval {$approval->id} (see log)"),
            default => null,
        };

        return $outcome;
    }
}
