<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One-off data repair for the admin Email Logs page.
 *
 * Bug 1 — duplicates: the LogSentEmail listener was registered twice (manual
 * Event::listen + Laravel's automatic listener discovery), so every email got
 * two identical "sent" rows with the same created_at. We keep the lowest id
 * of each exact (to_email, subject, status, created_at) group.
 *
 * Bug 2 — stuck "queued" rows: the expiry command wrote its queued audit row
 * AFTER Mail::send(), so for synchronous sends the MessageSent listener had
 * already fired (creating a stray "Unknown" sent row) and nothing ever
 * promoted the queued row. Where we can prove delivery (a sent row for the
 * same recipient + subject within 2 hours after the queued row), we promote
 * the queued row to "sent" and remove the stray duplicate.
 *
 * Genuinely undelivered queued rows are left as "queued" on purpose so admins
 * can see which reminders never went out and chase them manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        // ---- Bug 1: exact duplicates from the double-registered listener ----
        // Portable (MySQL + SQLite): delete every sent row whose id is not the
        // minimum of its (to_email, subject, created_at) group.
        $deleted = DB::delete("
            DELETE FROM email_logs
            WHERE status = 'sent'
              AND id NOT IN (
                  SELECT keep_id FROM (
                      SELECT MIN(id) AS keep_id
                      FROM email_logs
                      WHERE status = 'sent'
                      GROUP BY to_email, subject, created_at
                  ) AS keepers
              )
        ");

        Log::info('[email_logs repair] removed duplicate sent rows', ['deleted' => $deleted]);

        // ---- Bug 2: stuck queued rows with proof of delivery ----
        $queuedRows = DB::table('email_logs')
            ->where('status', 'queued')
            ->orderBy('created_at')
            ->get(['id', 'to_email', 'subject', 'created_at']);

        $promoted = 0;
        $removedStrays = 0;

        foreach ($queuedRows as $queued) {
            // Find the stray "sent" row the listener created because the queued
            // audit row didn't exist yet at send time. Window: 2 hours covers
            // the throttle stagger of even very large bulk runs.
            $stray = DB::table('email_logs')
                ->where('status', 'sent')
                ->where('to_email', $queued->to_email)
                ->where('subject', $queued->subject)
                ->where('created_at', '>=', $queued->created_at)
                ->where('created_at', '<=', date('Y-m-d H:i:s', strtotime($queued->created_at . ' +2 hours')))
                ->orderBy('created_at')
                ->first(['id', 'sent_at', 'from_email', 'from_name']);

            if (! $stray) {
                continue; // genuinely undelivered — leave visible as queued
            }

            DB::table('email_logs')->where('id', $queued->id)->update([
                'status' => 'sent',
                'sent_at' => $stray->sent_at,
                'from_email' => $stray->from_email,
                'from_name' => $stray->from_name,
                'updated_at' => now(),
            ]);
            DB::table('email_logs')->where('id', $stray->id)->delete();

            $promoted++;
            $removedStrays++;
        }

        Log::info('[email_logs repair] promoted stuck queued rows with delivery proof', [
            'promoted' => $promoted,
            'stray_rows_removed' => $removedStrays,
            'still_queued' => $queuedRows->count() - $promoted,
        ]);
    }

    public function down(): void
    {
        // Data repair — cannot be meaningfully reversed.
    }
};
