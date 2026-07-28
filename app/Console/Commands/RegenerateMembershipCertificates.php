<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\CertificateIssueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Bulk-regenerate the stored PDF for every non-revoked membership (good-standing)
 * certificate. Needed after changes to the good-standing template (e.g. adding
 * the "Joined On" / "Membership Expires On" rows) because existing certificates
 * serve a stored PDF and are only re-rendered when the file goes missing.
 *
 * Targets the three slugs PdfDocumentRenderer::renderCertificate() routes to the
 * good-standing template: `membership-certificate`, `paid-up-certificate`,
 * `good-standing-certificate`. Dedicated-status and endorsement letters are
 * intentionally left alone.
 */
class RegenerateMembershipCertificates extends Command
{
    protected $signature = 'nrapa:regenerate-membership-certificates
                            {--dry-run : List certificates that would be regenerated without touching them}
                            {--sleep=0 : Seconds to sleep between certificates (helps avoid hammering the PDF renderer)}';

    protected $description = 'Regenerate stored PDFs for all non-revoked membership certificates so they pick up template changes.';

    private const SLUGS = [
        'membership-certificate',
        'paid-up-certificate',
        'good-standing-certificate',
    ];

    public function handle(CertificateIssueService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sleep = max(0, (int) $this->option('sleep'));

        $query = Certificate::query()
            ->whereNull('revoked_at')
            ->whereHas('certificateType', fn ($q) => $q->whereIn('slug', self::SLUGS))
            ->with(['certificateType', 'user', 'membership.type']);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No membership certificates found to regenerate.');

            return self::SUCCESS;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Found {$total} membership certificate(s) to regenerate.");

        $regenerated = 0;
        $failed = 0;
        $skipped = 0;

        $query->chunkById(50, function ($certificates) use ($service, $dryRun, $sleep, &$regenerated, &$failed, &$skipped) {
            foreach ($certificates as $certificate) {
                $label = "  #{$certificate->id} ({$certificate->certificate_number}) — "
                    . ($certificate->user?->name ?? 'unknown user');

                if ($dryRun) {
                    $this->line("{$label} [dry-run]");
                    $skipped++;

                    continue;
                }

                try {
                    $newPath = $service->regenerateDocument($certificate);

                    if ($newPath) {
                        $regenerated++;
                        $this->line("{$label} regenerated");
                    } else {
                        $failed++;
                        $this->error("{$label} FAILED (renderer returned null — see logs)");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("{$label} FAILED — {$e->getMessage()}");
                    Log::error('Membership certificate regeneration failed', [
                        'certificate_id' => $certificate->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($sleep > 0) {
                    sleep($sleep);
                }
            }
        });

        $this->newLine();
        $this->info("Done. Regenerated: {$regenerated}, Failed: {$failed}, Skipped (dry-run): {$skipped}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
