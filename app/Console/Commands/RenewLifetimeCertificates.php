<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CertificateIssueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewLifetimeCertificates extends Command
{
    protected $signature = 'nrapa:renew-lifetime-certificates';

    protected $description = 'Auto-renew membership certificates for lifetime members whose certificates are expiring within 30 days or have expired';

    public function handle(): int
    {
        $this->info('Checking lifetime members for certificate renewal...');

        $renewed = 0;
        $skippedMissingDocs = 0;
        $errors = 0;
        $skippedMissingDocsUserIds = [];

        $lifetimeMembers = User::whereHas('activeMembership', function ($q) {
            $q->whereHas('type', fn ($t) => $t->where('duration_type', 'lifetime'));
        })->get();

        $this->info("Found {$lifetimeMembers->count()} lifetime member(s).");

        $service = app(CertificateIssueService::class);

        foreach ($lifetimeMembers as $user) {
            $currentCert = $user->certificates()
                ->whereHas('certificateType', fn ($q) => $q->where('slug', 'membership-certificate'))
                ->whereNull('revoked_at')
                ->latest('issued_at')
                ->first();

            $needsRenewal = ! $currentCert
                || ($currentCert->valid_until && $currentCert->valid_until->lte(now()->addDays(30)));

            if (! $needsRenewal) {
                continue;
            }

            // Pre-skip members who can't be auto-renewed yet (e.g. ID document
            // missing). Without this check the job would attempt the renewal
            // every day and emit an ERROR log line per member — turning the
            // daily error log into noise. We log a single INFO summary at the
            // end instead.
            $missingDocs = $service->getMissingRequiredDocumentsForMembership($user);
            if (count($missingDocs) > 0) {
                $skippedMissingDocs++;
                $skippedMissingDocsUserIds[] = $user->id;
                $this->line("  Skipped: {$user->name} (#{$user->id}) — missing: ".implode(', ', $missingDocs));

                continue;
            }

            try {
                $certificate = $service->issueMembershipCertificate($user, $user, skipChecks: true);

                if ($certificate) {
                    $renewed++;
                    $this->line("  Renewed: {$user->name} (#{$user->id})");
                    Log::info('Lifetime certificate auto-renewed', [
                        'user_id' => $user->id,
                        'certificate_id' => $certificate->id,
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Failed: {$user->name} — {$e->getMessage()}");
                // Genuinely unexpected — we already pre-skipped the known
                // "missing required document" cases above, so anything that
                // throws here is worth a real error log.
                Log::error('Lifetime certificate auto-renewal failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($skippedMissingDocs > 0) {
            Log::info('Lifetime certificate renewal: members skipped because of missing required documents', [
                'count' => $skippedMissingDocs,
                'user_ids' => $skippedMissingDocsUserIds,
            ]);
        }

        $this->info("Done. Renewed: {$renewed}, Skipped (missing docs): {$skippedMissingDocs}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
