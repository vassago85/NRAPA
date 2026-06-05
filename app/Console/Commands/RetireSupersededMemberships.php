<?php

namespace App\Console\Commands;

use App\Models\Membership;
use Illuminate\Console\Command;

/**
 * One-off / repeatable data-hygiene command for the membership state machine.
 *
 * Cleans up two related data-integrity issues that crept in before approvals
 * started retiring previous active rows and capping renewal expiries:
 *
 *   1. Users with more than one row at `status = 'active'` simultaneously.
 *      Only the latest (highest id) active row stays active; older actives
 *      become `expired` with `expires_at = now()`.
 *
 *   2. Active rows whose `expires_at` is more than the type's full
 *      `duration_months` past `activated_at`. These are clamped back to
 *      `activated_at + duration_months` so non-lifetime members can never be
 *      active for longer than one full cycle.
 *
 * Read-only by default; use --apply to write changes. The cap step is the
 * one that can actually shorten a member's expiry, so it asks separately.
 */
class RetireSupersededMemberships extends Command
{
    protected $signature = 'nrapa:retire-superseded-memberships
                            {--apply : Actually write the changes (default is dry-run)}
                            {--skip-retire : Skip retiring superseded active rows}
                            {--skip-cap : Skip capping over-long active rows}';

    protected $description = 'Retire superseded active memberships and clamp active rows to at most one full type duration from activation.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $prefix = $apply ? '' : '[DRY RUN] ';

        $retired = 0;
        $capped = 0;

        if (!$this->option('skip-retire')) {
            $retired = $this->processSupersededActives($apply, $prefix);
        }

        if (!$this->option('skip-cap')) {
            $this->newLine();
            $capped = $this->processOverlongActives($apply, $prefix);
        }

        $this->newLine();

        if ($apply) {
            $this->info("Done. Retired {$retired} superseded active row(s); capped {$capped} over-long active row(s).");
        } else {
            $this->warn("Dry run only. Re-run with --apply to write the changes.");
        }

        return Command::SUCCESS;
    }

    private function processSupersededActives(bool $apply, string $prefix): int
    {
        $allActive = Membership::query()
            ->where('status', 'active')
            ->with(['user:id,name,email', 'type:id,name'])
            ->orderBy('user_id')
            ->orderByDesc('id')
            ->get();

        $grouped = $allActive->groupBy('user_id')
            ->filter(fn ($rows) => $rows->count() > 1);

        if ($grouped->isEmpty()) {
            $this->info('No users have more than one active membership. Nothing to retire.');
            return 0;
        }

        $toRetire = collect();
        foreach ($grouped as $userRows) {
            // Highest id stays active; the rest get retired.
            foreach ($userRows->slice(1) as $row) {
                $toRetire->push($row);
            }
        }

        $this->info("{$prefix}Found {$grouped->count()} user(s) with multiple active memberships ({$toRetire->count()} row(s) to retire).");
        $this->newLine();

        $this->table(
            ['User', 'Keeping', 'Retiring (ID — Expires)'],
            $grouped->map(function ($rows) {
                $keep = $rows->first();
                $keepExpiry = $keep->expires_at?->format('Y-m-d') ?: 'no expiry';
                $retire = $rows->slice(1)
                    ->map(function ($r) {
                        $exp = $r->expires_at?->format('Y-m-d') ?: 'no expiry';
                        return "#{$r->id} ({$exp})";
                    })
                    ->implode(', ');

                $name = $keep->user?->name ?: "user#{$keep->user_id}";

                return [
                    $name,
                    "#{$keep->id} ({$keepExpiry})",
                    $retire,
                ];
            })->values()->toArray()
        );

        if (!$apply) {
            return 0;
        }

        if (!$this->confirm("Retire {$toRetire->count()} superseded active row(s)?", false)) {
            $this->warn('Skipped retiring superseded actives.');
            return 0;
        }

        $count = 0;
        foreach ($toRetire as $m) {
            $m->update([
                'status' => 'expired',
                'expires_at' => $m->expires_at && $m->expires_at->isPast() ? $m->expires_at : now(),
            ]);
            $count++;
        }

        $this->info("Retired {$count} superseded active row(s).");
        return $count;
    }

    private function processOverlongActives(bool $apply, string $prefix): int
    {
        $candidates = Membership::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereNotNull('activated_at')
            ->with(['user:id,name,email', 'type'])
            ->get()
            ->filter(function ($m) {
                $type = $m->type;
                if (!$type || $type->isLifetime() || !$type->duration_months) {
                    return false;
                }

                $cap = $m->activated_at->copy()->addMonths($type->duration_months);
                return $m->expires_at->gt($cap);
            });

        if ($candidates->isEmpty()) {
            $this->info('No active memberships exceed activated_at + duration_months. Nothing to cap.');
            return 0;
        }

        $this->info("{$prefix}Found {$candidates->count()} active membership(s) that exceed one full duration cycle from activation.");
        $this->newLine();

        $rows = $candidates->map(function ($m) {
            $cap = $m->activated_at->copy()->addMonths($m->type->duration_months);
            return [
                'id' => $m->id,
                'user' => $m->user?->name ?? "user#{$m->user_id}",
                'type' => $m->type?->name ?? '—',
                'activated' => $m->activated_at->format('Y-m-d'),
                'old_expires' => $m->expires_at->format('Y-m-d'),
                'new_expires' => $cap->format('Y-m-d'),
            ];
        })->values()->toArray();

        $this->table(['ID', 'User', 'Type', 'Activated', 'Old Expiry', 'New Expiry'], $rows);

        if (!$apply) {
            return 0;
        }

        if (!$this->confirm("Cap {$candidates->count()} active membership expiry date(s)?", false)) {
            $this->warn('Skipped capping over-long actives.');
            return 0;
        }

        $count = 0;
        foreach ($candidates as $m) {
            $cap = $m->activated_at->copy()->addMonths($m->type->duration_months);
            $m->update(['expires_at' => $cap]);
            $count++;
        }

        $this->info("Capped {$count} active membership expiry date(s).");
        return $count;
    }
}
