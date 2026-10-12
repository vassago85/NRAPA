<?php

namespace App\Services\Approvals;

use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Enums\ChaseOutcome;
use App\Mail\ApprovalChaseMail;
use App\Models\Approval;
use App\Models\ApprovalChase;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the "your {thing} is waiting on you" email on behalf of either the
 * daily scheduler or an admin clicking a button. Both callers go through the
 * same place so the throttle, the bounce guard, and the audit entry can never
 * disagree.
 */
class ChaseRunner
{
    /**
     * How recently a chase must have gone out to block a new one. The scheduler
     * stops a 2nd 7-day reminder from firing on top of a manual chase; the UI
     * stops a 2nd admin from doubling up on the first.
     */
    public const THROTTLE_HOURS = 24;

    /**
     * If the item has been waiting less than 7 days, a manual chase still uses
     * the 7-day stage so the auto-chaser skips it when the time comes.
     */
    public const DEFAULT_MANUAL_STAGE = 7;

    public function __construct(private readonly ApprovalAuditor $auditor) {}

    /**
     * Send one chase email. Delegates all the policy choices to the caller
     * (stage, source, force), so the UI and the scheduler stay honest about
     * which code made the decision.
     */
    public function chase(
        Approval $approval,
        ?User $actor,
        string $source,
        ?int $stage = null,
        bool $force = false,
    ): ChaseOutcome {
        if ($approval->state !== ApprovalState::WaitingOnMember || $approval->approvable === null) {
            return ChaseOutcome::NotWaitingOnMember;
        }

        $member = $approval->user;

        if (! $member?->canReceiveEmail()) {
            return ChaseOutcome::MemberCannotReceiveEmail;
        }

        // Make sure every waiting-on-member row has a "when did this start" marker
        // so chase cycles and age labels line up.
        if (! $approval->metaValue('waiting_since')) {
            $approval->withMeta(['waiting_since' => ($approval->updated_at ?? now())->toIso8601String()])->saveQuietly();
        }

        $since = $approval->waitingSince() ?? now();
        $days = (int) floor($since->diffInDays(now(), true));
        $stage = $stage ?? (ApprovalChase::dueStage($days) ?? self::DEFAULT_MANUAL_STAGE);

        if ($source === 'auto') {
            // The scheduler must not fire the same stage twice in one spell.
            $alreadyForStage = ApprovalChase::query()
                ->where('approval_id', $approval->id)
                ->where('cycle_started_at', $since)
                ->where('stage', '>=', $stage)
                ->exists();

            if ($alreadyForStage) {
                return ChaseOutcome::AlreadyChasedForStage;
            }
        } elseif (! $force) {
            // Manual chases are time-throttled instead: any chase in the last
            // THROTTLE_HOURS blocks a new one, auto or manual.
            $lastSent = ApprovalChase::query()
                ->where('approval_id', $approval->id)
                ->max('sent_at');

            if ($lastSent && now()->diffInHours($lastSent, true) < self::THROTTLE_HOURS) {
                return ChaseOutcome::AlreadyChasedRecently;
            }
        }

        return DB::transaction(function () use ($approval, $member, $actor, $source, $stage, $since): ChaseOutcome {
            $log = EmailLog::create([
                'user_id' => $member->id,
                'to_email' => $member->email,
                'to_name' => $member->name,
                'subject' => ApprovalChaseMail::subjectFor($approval),
                'mailable_class' => ApprovalChaseMail::class,
                'status' => 'queued',
                'metadata' => [
                    'approval_id' => $approval->id,
                    'stage' => $stage,
                    'source' => $source,
                    'actor_id' => $actor?->id,
                ],
            ]);

            try {
                Mail::to($member->email)->send(new ApprovalChaseMail($approval, $stage));
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                Log::warning('Approval chase failed', [
                    'approval_id' => $approval->id,
                    'stage' => $stage,
                    'source' => $source,
                    'error' => $e->getMessage(),
                ]);

                return ChaseOutcome::SendFailed;
            }

            // updateOrCreate so a manual chase after an auto one at the same stage
            // bumps the row forward instead of hitting the uniqueness constraint.
            ApprovalChase::updateOrCreate(
                ['approval_id' => $approval->id, 'cycle_started_at' => $since, 'stage' => $stage],
                ['sent_at' => now(), 'email_log_id' => $log->id],
            );

            $this->auditor->record($approval, 'approval_chased', $actor, [
                'source' => $source,
                'stage' => $stage,
                'email_log_id' => $log->id,
            ]);

            // The payment page uses pop_reminder_sent_at as its "do I need to
            // show Chase or Resend?" flag. Keep it in step so we do not look
            // like two systems to the same admin.
            if ($approval->type === ApprovalType::Payment
                && ($payment = $approval->approvable)
                && $payment->membership) {
                $payment->membership->recordPopReminderSent();
            }

            return ChaseOutcome::Sent;
        });
    }
}
