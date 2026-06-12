<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Correction to 2026_06_12_110000, which re-queued every stale "queued"
 * expiry-reminder row for resending.
 *
 * Many of those rows came from the pre-fix ordering bug: the mail was sent
 * SYNCHRONOUSLY (and delivered), the listener wrote its "sent" row a second
 * or two BEFORE the command wrote the queued audit row. The earlier repair
 * (2026_06_12_100000) only looked for delivery proof AT-OR-AFTER the queued
 * row's timestamp, so it missed these — and the follow-up migration then
 * re-queued reminders that members had already received, which would produce
 * duplicate emails on the next scheduled run.
 *
 * This migration looks for delivery proof within ±2 hours of each re-queued
 * row. Where proof exists:
 *   - restore the membership_renewal_reminders idempotency row (so the
 *     nightly run does NOT resend),
 *   - promote the marked-failed row back to "sent" with the real timestamp,
 *   - remove the stray duplicate "sent" row.
 * Rows with no delivery proof are left re-queued — those genuinely never
 * went out and SHOULD be resent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $markers = DB::table('email_logs')
            ->where('status', 'failed')
            ->where('error_message', 'like', 'Lost in queue (no failed job to retry)%')
            ->get(['id', 'to_email', 'subject', 'created_at', 'metadata']);

        $restored = 0;
        $leftForResend = 0;

        foreach ($markers as $row) {
            $windowStart = date('Y-m-d H:i:s', strtotime($row->created_at . ' -2 hours'));
            $windowEnd = date('Y-m-d H:i:s', strtotime($row->created_at . ' +2 hours'));

            // Delivery proof: a sent row for the same recipient + subject within
            // ±2h. Covers both orderings (stray before queued row = sync sends,
            // stray after = queued sends the earlier repair already handled).
            $stray = DB::table('email_logs')
                ->where('status', 'sent')
                ->where('to_email', $row->to_email)
                ->where('subject', $row->subject)
                ->where('id', '!=', $row->id)
                ->whereBetween('created_at', [$windowStart, $windowEnd])
                ->orderBy('created_at')
                ->first(['id', 'sent_at', 'created_at', 'from_email', 'from_name']);

            if (! $stray) {
                $leftForResend++;

                continue; // genuinely lost — let the nightly run resend it
            }

            $deliveredAt = $stray->sent_at ?? $stray->created_at;

            // Put the idempotency row back so tomorrow's run skips this member.
            $meta = json_decode($row->metadata ?? '', true) ?: [];
            if (
                isset($meta['membership_id'], $meta['kind'])
                && Schema::hasTable('membership_renewal_reminders')
                && DB::table('memberships')->where('id', $meta['membership_id'])->exists()
            ) {
                $exists = DB::table('membership_renewal_reminders')
                    ->where('membership_id', $meta['membership_id'])
                    ->where('kind', $meta['kind'])
                    ->exists();

                if (! $exists) {
                    DB::table('membership_renewal_reminders')->insert([
                        'membership_id' => $meta['membership_id'],
                        'kind' => $meta['kind'],
                        'sent_at' => $deliveredAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Restore the audit row to the truth: it was delivered.
            DB::table('email_logs')->where('id', $row->id)->update([
                'status' => 'sent',
                'sent_at' => $deliveredAt,
                'from_email' => $stray->from_email,
                'from_name' => $stray->from_name,
                'error_message' => null,
                'updated_at' => now(),
            ]);
            DB::table('email_logs')->where('id', $stray->id)->delete();

            $restored++;
        }

        Log::info('[email_logs repair] undid re-queue for already-delivered reminders', [
            'restored_as_sent' => $restored,
            'left_for_resend' => $leftForResend,
        ]);
    }

    public function down(): void
    {
        // Data repair — cannot be meaningfully reversed.
    }
};
