<?php

namespace App\Console\Commands;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Exceptions\Approvals\ApprovalAlreadyDecided;
use App\Exceptions\Approvals\ApprovalNotAllowed;
use App\Models\Approval;
use App\Services\Approvals\ApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The two-admin rule was dropped (see migration
 * 2026_10_12_120000_clear_pending_second_signatures). Any approval that was
 * already waiting at "1 of 2" now sits open with a first signature on it but
 * nobody to give the second. This command walks those rows and finishes them
 * on behalf of the first signer so the letters issue and the members get
 * their decision emails.
 *
 * Run it once, after deploy. Idempotent: anything already decided is skipped.
 */
class PromoteFirstSignaturesToApproved extends Command
{
    protected $signature = 'nrapa:promote-first-signatures
        {--dry-run : Report what would be promoted without approving anything}
        {--type= : Only promote approvals of this type (e.g. endorsement)}';

    protected $description = 'Promote open approvals that already have a first signature to full approval, on behalf of the first signer.';

    public function handle(ApprovalService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $typeFilter = $this->option('type');

        $query = Approval::query()
            ->where('state', ApprovalState::WaitingOnAdmin->value)
            ->whereNotNull('first_approved_by')
            ->whereNull('decided_at')
            ->with(['firstApprover', 'approvable', 'user']);

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nothing to promote: no open approvals have a first signature pending.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry run] ' : '')."Found {$total} open approval(s) with a pending first signature.");

        $promoted = 0;
        $skipped = 0;

        $query->chunkById(200, function ($approvals) use ($service, $dryRun, &$promoted, &$skipped) {
            foreach ($approvals as $approval) {
                $signer = $approval->firstApprover;

                if (! $signer || ! $signer->isAdmin()) {
                    $this->line("  skip approval {$approval->id}: first signer missing or no longer an admin");
                    $skipped++;
                    continue;
                }

                if (! $approval->approvable) {
                    $this->line("  skip approval {$approval->id}: underlying record is gone");
                    $skipped++;
                    continue;
                }

                $summary = $approval->approvable->summaryLine();

                if ($dryRun) {
                    $this->line("  would promote approval {$approval->id} ({$approval->type->label()}) on behalf of {$signer->name}: {$summary}");
                    $promoted++;
                    continue;
                }

                try {
                    $service->decide(
                        $approval,
                        ApprovalDecision::Approve,
                        $signer,
                        'Auto-promoted from first signature after the two-admin rule was dropped.',
                    );
                    $this->line("  promoted approval {$approval->id} on behalf of {$signer->name}: {$summary}");
                    $promoted++;
                } catch (ApprovalAlreadyDecided $e) {
                    $this->line("  skip approval {$approval->id}: {$e->describe()}");
                    $skipped++;
                } catch (ApprovalNotAllowed $e) {
                    $this->line("  skip approval {$approval->id}: {$e->getMessage()}");
                    $skipped++;
                } catch (\Throwable $e) {
                    $this->error("  failed approval {$approval->id}: {$e->getMessage()}");
                    Log::warning('Failed to promote first signature', [
                        'approval_id' => $approval->id,
                        'first_approver_id' => $signer->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }
        }, 'approvals.id', 'id');

        $this->info(($dryRun ? '[dry run] ' : '')."Promoted {$promoted}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
