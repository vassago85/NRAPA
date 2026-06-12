<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up to 2026_06_12_100000 (email log repair).
 *
 * 1. Backfill `mailable_class` on historical rows logged as "Unknown": the
 *    LogSentEmail listener used to read a non-existent event-data key, so
 *    every directly-sent mailable was typed "Unknown". Each mailable has a
 *    known (mostly static) subject line, so we can recover the type from the
 *    subject retroactively.
 *
 * 2. Re-queue membership-expiry reminders that were lost in transit: rows
 *    still status='queued' hours after dispatch, with no failed job to retry
 *    (verified on prod 12 Jun — queue:failed is empty). We delete their
 *    membership_renewal_reminders idempotency rows so the nightly scheduled
 *    command re-sends them, and mark the stale log rows 'failed' so they
 *    can't confuse the listener's queued-row promotion matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $this->backfillMailableTypes();
        $this->requeueLostExpiryReminders();
    }

    protected function backfillMailableTypes(): void
    {
        // Exact subject -> mailable class. Subjects copied verbatim from each
        // mailable's envelope() (watch the em dashes and en dashes).
        $exact = [
            'Action Required: Accept NRAPA Terms & Conditions' => \App\Mail\TermsAcceptanceRequiredMail::class,
            'NRAPA Membership Application Update' => \App\Mail\MembershipRejected::class,
            'Welcome to NRAPA – Your Membership is Active!' => \App\Mail\MembershipApproved::class,
            'NRAPA Document Requires Attention' => \App\Mail\DocumentRejected::class,
            'NRAPA Transfer Application Received' => \App\Mail\TransferApplicationReceived::class,
            'NRAPA Membership — Action Required' => \App\Mail\MembershipApprovalRevoked::class,
            'NRAPA Activity Requires Attention' => \App\Mail\ActivityRejected::class,
            'NRAPA Endorsement Document Requires Attention' => \App\Mail\EndorsementDocumentRejected::class,
            'NRAPA Endorsement Request Update' => \App\Mail\EndorsementRejected::class,
            'NRAPA — Payment Received' => \App\Mail\PaymentReceived::class,
            'NRAPA Activity Approved' => \App\Mail\ActivityApproved::class,
            'NRAPA Membership — Proof of Payment Still Outstanding' => \App\Mail\PopFollowupReminder::class,
            'NRAPA Document Approved' => \App\Mail\DocumentVerified::class,
            'NRAPA — Your Document Details Were Updated' => \App\Mail\DocumentCorrected::class,
            'NRAPA Endorsement Document Approved' => \App\Mail\EndorsementDocumentVerified::class,
            'NRAPA Account Deleted' => \App\Mail\AccountDeleted::class,
            'Welcome to NRAPA – Set Up Your Account' => \App\Mail\ImportWelcome::class,
            'NRAPA Membership Payment Instructions' => \App\Mail\PaymentInstructions::class,
            'NRAPA Endorsement Letter Issued' => \App\Mail\EndorsementApproved::class,
            'NRAPA Endorsement Request Approved' => \App\Mail\EndorsementApproved::class,
            'NRAPA membership renewal reminder' => \App\Mail\MembershipExpiry::class,
        ];

        // Subjects with dynamic parts -> LIKE patterns. Applied after exact
        // matches; only rows still Unknown are touched, so a generic prefix
        // pattern (e.g. member messages) can't override a specific match.
        $patterns = [
            'Your NRAPA membership has expired (%' => \App\Mail\MembershipExpiry::class,
            'URGENT: NRAPA membership expires in %' => \App\Mail\MembershipExpiry::class,
            'NRAPA membership renewal due — expires %' => \App\Mail\MembershipExpiry::class,
            'URGENT: Firearm License Expires in %' => \App\Mail\LicenseExpiry::class,
            'Firearm License Expiring Soon - %' => \App\Mail\LicenseExpiry::class,
            'Reminder: Firearm License Expiring in %' => \App\Mail\LicenseExpiry::class,
            "You're invited to join NRAPA via %" => \App\Mail\AffiliatedClubInviteMail::class,
            'NRAPA – Your Login Details (%' => \App\Mail\SharedEmailNotice::class,
            'NRAPA: %' => \App\Mail\MemberMessageMail::class,
        ];

        $unknown = fn () => DB::table('email_logs')
            ->where(function ($q) {
                $q->where('mailable_class', 'Unknown')->orWhereNull('mailable_class');
            });

        $updated = 0;

        foreach ($exact as $subject => $class) {
            $updated += $unknown()->where('subject', $subject)->update(['mailable_class' => $class]);
        }

        foreach ($patterns as $pattern => $class) {
            $updated += $unknown()->where('subject', 'like', $pattern)->update(['mailable_class' => $class]);
        }

        $remaining = $unknown()->count();

        Log::info('[email_logs repair] backfilled mailable types from subjects', [
            'updated' => $updated,
            'still_unknown' => $remaining,
        ]);
    }

    protected function requeueLostExpiryReminders(): void
    {
        if (! Schema::hasTable('membership_renewal_reminders')) {
            return;
        }

        // Queued rows older than 2 hours were dispatched but never confirmed
        // delivered (stagger delays are seconds, not hours), and prod's
        // failed-jobs table is empty — the jobs are gone.
        $stuck = DB::table('email_logs')
            ->where('status', 'queued')
            ->where('mailable_class', \App\Mail\MembershipExpiry::class)
            ->where('created_at', '<', now()->subHours(2))
            ->get(['id', 'to_email', 'metadata']);

        $requeued = 0;

        foreach ($stuck as $row) {
            $meta = json_decode($row->metadata ?? '', true) ?: [];
            $membershipId = $meta['membership_id'] ?? null;
            $kind = $meta['kind'] ?? null;

            // Clear the idempotency row so the nightly command re-sends. If we
            // can't identify it, still mark the log row failed below — the
            // bucket logic will pick the member up again on its next transition.
            if ($membershipId && $kind) {
                DB::table('membership_renewal_reminders')
                    ->where('membership_id', $membershipId)
                    ->where('kind', $kind)
                    ->delete();
                $requeued++;
            }

            // Mark the stale row failed so the listener can't mistakenly promote
            // it when the re-sent mail (same recipient + subject) is delivered.
            DB::table('email_logs')->where('id', $row->id)->update([
                'status' => 'failed',
                'error_message' => 'Lost in queue (no failed job to retry) — reminder re-queued for the next scheduled run on ' . now()->toDateTimeString(),
                'updated_at' => now(),
            ]);
        }

        Log::info('[email_logs repair] re-queued lost expiry reminders', [
            'stale_queued_rows' => $stuck->count(),
            'reminders_cleared_for_resend' => $requeued,
        ]);
    }

    public function down(): void
    {
        // Data repair — cannot be meaningfully reversed.
    }
};
