<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\CertificateIssueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

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
 *
 * DomPDF leaks memory across renders in the same PHP process (fonts/images stay
 * referenced) so long batches OOM around 40-50 certificates. To sidestep that,
 * batch mode spawns a fresh PHP subprocess per certificate via
 * `--certificate-id=<id>`, giving each render its own memory space.
 */
class RegenerateMembershipCertificates extends Command
{
    protected $signature = 'nrapa:regenerate-membership-certificates
                            {--dry-run : List certificates that would be regenerated without touching them}
                            {--sleep=0 : Seconds to sleep between certificates}
                            {--start-from= : Resume from this certificate id (inclusive) after a partial run}
                            {--limit= : Only process at most this many certificates}
                            {--certificate-id= : Internal: render a single certificate by id and exit (used by the subprocess-per-render batch loop)}
                            {--in-process : Render inline instead of spawning subprocesses (leaks memory across renders; use only for small runs or debugging)}
                            {--timeout=120 : Per-certificate subprocess timeout, in seconds}';

    protected $description = 'Regenerate stored PDFs for all non-revoked membership certificates so they pick up template changes.';

    private const SLUGS = [
        'membership-certificate',
        'paid-up-certificate',
        'good-standing-certificate',
    ];

    public function handle(CertificateIssueService $service): int
    {
        // Single-certificate mode: render one and exit. This branch is what the
        // batch loop spawns for each certificate so DomPDF's per-render memory
        // leak is bounded to a fresh process.
        if ($this->option('certificate-id')) {
            return $this->renderOne($service, (int) $this->option('certificate-id'));
        }

        return $this->runBatch($service);
    }

    private function renderOne(CertificateIssueService $service, int $certificateId): int
    {
        // Give the single render plenty of headroom; DomPDF can spike well past
        // Laravel's default limit for larger certificates with images.
        @ini_set('memory_limit', '512M');

        $certificate = Certificate::with(['certificateType', 'user', 'membership.type'])->find($certificateId);
        if (! $certificate) {
            $this->error("Certificate #{$certificateId} not found.");

            return self::FAILURE;
        }

        try {
            $path = $service->regenerateDocument($certificate);

            if (! $path) {
                $this->error("Renderer returned null for certificate #{$certificateId}.");

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Certificate #{$certificateId} render failed: {$e->getMessage()}");
            Log::error('Membership certificate regeneration (single) failed', [
                'certificate_id' => $certificateId,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    private function runBatch(CertificateIssueService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sleep = max(0, (int) $this->option('sleep'));
        $startFrom = $this->option('start-from') !== null ? (int) $this->option('start-from') : null;
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $inProcess = (bool) $this->option('in-process');
        $timeout = max(10, (int) $this->option('timeout'));

        $query = Certificate::query()
            ->whereNull('revoked_at')
            ->whereHas('certificateType', fn ($q) => $q->whereIn('slug', self::SLUGS))
            ->with(['certificateType', 'user', 'membership.type'])
            ->orderBy('id');

        if ($startFrom !== null) {
            $query->where('id', '>=', $startFrom);
        }

        $total = (clone $query)->count();
        $toProcess = $limit !== null ? min($total, $limit) : $total;

        if ($toProcess === 0) {
            $this->info('No membership certificates found to regenerate.');

            return self::SUCCESS;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $mode = $dryRun ? 'dry-run' : ($inProcess ? 'in-process' : 'subprocess-per-render');
        $this->info("{$prefix}Found {$total} membership certificate(s) to regenerate (processing {$toProcess}, mode: {$mode}).");
        if ($startFrom !== null) {
            $this->line("  Resuming from certificate #{$startFrom}.");
        }

        $regenerated = 0;
        $failed = 0;
        $skipped = 0;
        $seen = 0;
        $stop = false;

        $query->chunkById(50, function ($certificates) use ($service, $dryRun, $sleep, $inProcess, $timeout, $limit, &$regenerated, &$failed, &$skipped, &$seen, &$stop) {
            foreach ($certificates as $certificate) {
                if ($limit !== null && $seen >= $limit) {
                    $stop = true;

                    return false;
                }
                $seen++;

                $label = "  #{$certificate->id} ({$certificate->certificate_number}) — "
                    . ($certificate->user?->name ?? 'unknown user');

                if ($dryRun) {
                    $this->line("{$label} [dry-run]");
                    $skipped++;

                    continue;
                }

                $ok = $inProcess
                    ? $this->regenerateInProcess($service, $certificate, $label)
                    : $this->regenerateInSubprocess($certificate, $label, $timeout);

                if ($ok) {
                    $regenerated++;
                } else {
                    $failed++;
                }

                if ($sleep > 0) {
                    sleep($sleep);
                }

                gc_collect_cycles();
            }

            return $stop ? false : null;
        });

        $this->newLine();
        $this->info("Done. Regenerated: {$regenerated}, Failed: {$failed}, Skipped (dry-run): {$skipped}");
        if ($failed > 0) {
            $lastSuccessLabel = 'the last successful ID printed above';
            $this->warn("To retry the failed / remaining ones later, re-run with --start-from={$lastSuccessLabel}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function regenerateInProcess(CertificateIssueService $service, Certificate $certificate, string $label): bool
    {
        try {
            $newPath = $service->regenerateDocument($certificate);

            if ($newPath) {
                $this->line("{$label} regenerated");

                return true;
            }

            $this->error("{$label} FAILED (renderer returned null — see logs)");

            return false;
        } catch (\Throwable $e) {
            $this->error("{$label} FAILED — {$e->getMessage()}");
            Log::error('Membership certificate regeneration failed', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function regenerateInSubprocess(Certificate $certificate, string $label, int $timeout): bool
    {
        $process = new Process([
            PHP_BINARY,
            base_path('artisan'),
            'nrapa:regenerate-membership-certificates',
            '--certificate-id=' . $certificate->id,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->error("{$label} FAILED — subprocess exception: {$e->getMessage()}");

            return false;
        }

        if ($process->isSuccessful()) {
            $this->line("{$label} regenerated");

            return true;
        }

        $stderr = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        $summary = $stderr !== '' ? " — {$stderr}" : '';
        $this->error("{$label} FAILED (exit " . $process->getExitCode() . "){$summary}");
        Log::error('Membership certificate regeneration subprocess failed', [
            'certificate_id' => $certificate->id,
            'exit_code' => $process->getExitCode(),
            'stderr' => $stderr,
        ]);

        return false;
    }
}
