<?php

namespace App\Console\Commands;

use App\Models\Membership;
use Illuminate\Console\Command;

/**
 * Flip memberships marked status=active to status=expired when their expires_at
 * is in the past (and they're not lifetime).
 *
 * Most useful for cleaning up data after a bulk import where the importer
 * carried over expired members but left their status as "active". With the
 * renewal flow now keying off `User::activeMembership()` (which is
 * `where('status', 'active')`), these records appear as active to the system
 * even though their date has lapsed — which is fine for the renewal CTA but
 * inconsistent in admin views and reporting.
 *
 * Read-only by default; use --apply to actually write changes.
 */
class FixExpiredStatuses extends Command
{
    protected $signature = 'nrapa:fix-expired-statuses
                            {--apply : Actually update the records (default is dry-run)}
                            {--force : Skip the interactive confirmation (required for scheduled runs)}
                            {--source= : Only fix records with this source value (e.g. "import")}';

    protected $description = 'Flip status=active to status=expired when expires_at is past (lifetime types excluded).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $source = $this->option('source');

        $query = Membership::query()
            ->with(['user:id,name,email', 'type:id,name,duration_type'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        if ($source) {
            $query->where('source', $source);
        }

        $candidates = $query->get()->reject(fn ($m) => $m->type?->isLifetime() ?? false);

        if ($candidates->isEmpty()) {
            $this->info('No status=active records with past expires_at found. Nothing to fix.');

            return Command::SUCCESS;
        }

        $prefix = $apply ? '' : '[DRY RUN] ';
        $this->info("{$prefix}Found {$candidates->count()} membership(s) to flip active -> expired.");
        $this->newLine();

        $rows = $candidates->map(fn ($m) => [
            'id' => $m->id,
            'user' => $m->user?->name ?? '— (deleted) —',
            'type' => $m->type?->name ?? '—',
            'expires' => $m->expires_at->format('Y-m-d'),
            'days_past' => (int) $m->expires_at->diffInDays(now()) . ' days ago',
        ])->values()->toArray();

        $this->table(['ID', 'User', 'Type', 'Expires', 'How long ago'], $rows);

        if (!$apply) {
            $this->newLine();
            $this->warn("Dry run only. Re-run with --apply to update {$candidates->count()} record(s).");

            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Apply status=expired to all {$candidates->count()} records?", false)) {
            $this->warn('Aborted by user.');

            return Command::FAILURE;
        }

        $updated = 0;
        foreach ($candidates as $m) {
            $m->update(['status' => 'expired']);
            $updated++;
        }

        $this->newLine();
        $this->info("Done. {$updated} membership(s) updated to status=expired.");

        return Command::SUCCESS;
    }
}
