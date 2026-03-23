<?php

namespace App\Console\Commands;

use App\Models\MemberDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeVerifiedDocumentFiles extends Command
{
    protected $signature = 'nrapa:purge-verified-document-files {--days=7 : Days after verification to purge}';

    protected $description = 'Delete document files from storage 7 days after verification (POPIA compliance). Metadata is retained.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $documents = MemberDocument::where('status', 'verified')
            ->whereNull('file_purged_at')
            ->whereNotNull('verified_at')
            ->where('verified_at', '<=', now()->subDays($days))
            ->whereNotNull('file_path')
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
