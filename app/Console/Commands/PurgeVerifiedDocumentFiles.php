<?php

namespace App\Console\Commands;

use App\Models\MemberDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeVerifiedDocumentFiles extends Command
{
    protected $signature = 'nrapa:purge-verified-document-files {--days=7 : Days after verification to purge}';

    protected $description = 'Delete document files from storage 7 days after verification (POPIA compliance). Metadata is retained. Two retention exceptions: (1) activity evidence is retained until the end of the year following the activity date; (2) members can opt-in to "until expiry + 1 year" retention on personally-uploaded documents that have an expiry date.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        // Activity evidence retention: keep files linked to a shooting activity
        // through the entire year *after* the activity year, because that is the
        // year the activity qualifies the member as dedicated for and the
        // evidence may need to be produced for SAPS during that year.
        //
        // Equivalently: while activity_date >= start-of-year(today.year - 1),
        // do not purge the linked file. Once the activity is two-or-more
        // calendar years old, normal POPIA purge resumes.
        $activityRetentionCutoff = now()->subYear()->startOfYear();

        // Member-chosen "expiry + 1 year" retention: a document is protected
        // while expires_at > now()-1y (equivalent to expires_at + 1y > now).
        // Once expires_at + 1y has passed, fall back to the standard purge.
        $extendedRetentionCutoff = now()->subYear();

        $documents = MemberDocument::where('status', 'verified')
            ->whereNull('file_purged_at')
            ->whereNotNull('verified_at')
            ->where('verified_at', '<=', now()->subDays($days))
            ->whereNotNull('file_path')
            ->whereDoesntHave('shootingActivityAsEvidence', function ($q) use ($activityRetentionCutoff) {
                $q->where('activity_date', '>=', $activityRetentionCutoff);
            })
            ->whereDoesntHave('shootingActivityAsAdditional', function ($q) use ($activityRetentionCutoff) {
                $q->where('activity_date', '>=', $activityRetentionCutoff);
            })
            ->where(function ($q) use ($extendedRetentionCutoff) {
                // Eligible for purge unless the member chose extended
                // retention AND we're still inside expires_at + 1y.
                $q->where('retention_choice', '!=', MemberDocument::RETENTION_EXPIRY_PLUS_1Y)
                    ->orWhereNull('retention_choice')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '<=', $extendedRetentionCutoff);
            })
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No document files to purge.');

            return self::SUCCESS;
        }

        $disk = config('filesystems.disks.r2.key') ? 'r2' : config('filesystems.default');
        $purged = 0;
        $failed = 0;

        foreach ($documents as $document) {
            try {
                if (Storage::disk($disk)->exists($document->file_path)) {
                    Storage::disk($disk)->delete($document->file_path);
                }

                $document->update(['file_purged_at' => now()]);
                $purged++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to purge document file', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Purged {$purged} document files. Failed: {$failed}.");
        Log::info("Document file purge completed", ['purged' => $purged, 'failed' => $failed]);

        return self::SUCCESS;
    }
}
