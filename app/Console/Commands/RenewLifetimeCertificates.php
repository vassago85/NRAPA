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
        $errors = 0;

        $lifetimeMembers = User::whereHas('activeMembership', function ($q) {
            $q->whereHas('type', fn ($t) => $t->where('duration_type', 'lifetime'));
        })->get();

        $this->info("Found {$lifetimeMembers->count()} lifetime member(s).");

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

            try {
                $service = app(CertificateIssueService::class);
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
                Log::error('Lifetime certificate auto-renewal failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Renewed: {$renewed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
