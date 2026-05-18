<?php

namespace App\Console\Commands;

use App\Models\Membership;
use Illuminate\Console\Command;

/**
 * Audit memberships that originated from an Excel import.
 *
 * Surfaces anomalies in expires_at / status combinations so the admin can
 * decide which records (if any) need a one-off correction. Does NOT modify
 * any data — read-only by design.
 *
 * Common anomalies it flags:
 *  - Status "expired" but expires_at is null or still in the future.
 *  - Status "active" but expires_at has already passed.
 *  - Status "active"/"expired" but expires_at is missing on a non-lifetime type.
 *  - expires_at duration from approved_at doesn't match the type's expected
 *    duration (off by more than 30 days) — common symptom of a bad import.
 */
class AuditImportedMemberships extends Command
{
    protected $signature = 'nrapa:audit-imported-memberships
                            {--source=import : Source value to audit (default: "import"; use "all" for every membership)}
                            {--only-issues : Only output rows with at least one flagged anomaly}
                            {--limit=0 : Cap the number of rows shown (0 = no cap)}';

    protected $description = 'Audit imported memberships for expiry/status anomalies (read-only).';

    public function handle(): int
    {
        $source = $this->option('source');
        $onlyIssues = (bool) $this->option('only-issues');
        $limit = (int) $this->option('limit');

        $query = Membership::query()
            ->with(['user:id,name,email', 'type:id,name,slug,duration_months,duration_type,expiry_rule,requires_renewal']);

        if ($source !== 'all') {
            $query->where('source', $source);
        }

        $memberships = $query->orderBy('id')->get();

        if ($memberships->isEmpty()) {
            $this->warn("No memberships found for source='{$source}'.");

            return Command::SUCCESS;
        }

        $this->info("Scanned {$memberships->count()} membership(s) with source='{$source}'.");
        $this->newLine();

        $rows = [];
        $stats = [
            'total' => $memberships->count(),
            'with_issues' => 0,
            'status_expired_future' => 0,
            'status_active_past' => 0,
            'missing_expiry' => 0,
            'duration_mismatch' => 0,
        ];

        foreach ($memberships as $m) {
            $issues = $this->detectIssues($m);

            if ($onlyIssues && empty($issues)) {
                continue;
            }

            if (!empty($issues)) {
                $stats['with_issues']++;
                foreach ($issues as $code) {
                    if (isset($stats[$code])) {
                        $stats[$code]++;
                    }
                }
            }

            $rows[] = [
                'id' => $m->id,
                'user' => $m->user ? ($m->user->name . ' <' . $m->user->email . '>') : '— (deleted user) —',
                'type' => $m->type?->name ?? '—',
                'status' => $m->status,
                'applied' => $m->applied_at?->format('Y-m-d') ?? '—',
                'approved' => $m->approved_at?->format('Y-m-d') ?? '—',
                'expires' => $m->expires_at?->format('Y-m-d') ?? ($m->type?->isLifetime() ? 'Lifetime' : '—'),
                'flags' => empty($issues) ? '' : implode(', ', $issues),
            ];

            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        if (empty($rows)) {
            $this->info('No anomalies detected.');

            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'User', 'Type', 'Status', 'Applied', 'Approved', 'Expires', 'Flags'],
            $rows
        );

        $this->newLine();
        $this->line("Total scanned:           <comment>{$stats['total']}</comment>");
        $this->line("With at least 1 issue:   <comment>{$stats['with_issues']}</comment>");
        $this->line("status_expired_future:   <comment>{$stats['status_expired_future']}</comment>");
        $this->line("status_active_past:      <comment>{$stats['status_active_past']}</comment>");
        $this->line("missing_expiry:          <comment>{$stats['missing_expiry']}</comment>");
        $this->line("duration_mismatch:       <comment>{$stats['duration_mismatch']}</comment>");

        return Command::SUCCESS;
    }

    /**
     * @return array<int,string> Issue codes detected for this membership.
     */
    protected function detectIssues(Membership $m): array
    {
        $issues = [];

        if (!$m->type) {
            $issues[] = 'no_type';

            return $issues;
        }

        $isLifetime = $m->type->isLifetime();

        // 1. status="expired" but expires_at missing or still in the future.
        if ($m->status === 'expired') {
            if ($isLifetime) {
                $issues[] = 'lifetime_marked_expired';
            } elseif (!$m->expires_at) {
                $issues[] = 'status_expired_no_date';
            } elseif (!$m->expires_at->isPast()) {
                $issues[] = 'status_expired_future';
            }
        }

        // 2. status="active" but expires_at has passed.
        if ($m->status === 'active' && !$isLifetime && $m->expires_at && $m->expires_at->isPast()) {
            $issues[] = 'status_active_past';
        }

        // 3. Missing expires_at on a non-lifetime active/expired record.
        if (in_array($m->status, ['active', 'expired']) && !$isLifetime && !$m->expires_at) {
            $issues[] = 'missing_expiry';
        }

        // 4. Duration mismatch — only flag *strongly* anomalous spans:
        //    - Actual span is negative (expires before approval), OR
        //    - Actual span is more than 1.5x the type's expected duration, OR
        //    - Actual span is less than 30 days when expected >= 6 months.
        //
        // We deliberately do NOT flag "off by a few months" mismatches because
        // imports legitimately carry over the previous system's renewal_date,
        // which won't align with the type's nominal duration_months.
        if (
            !$isLifetime
            && $m->approved_at
            && $m->expires_at
            && $m->type->duration_months
            && $m->type->expiry_rule === 'rolling'
        ) {
            $expectedDays = $m->type->duration_months * 30;
            $actualDays = $m->approved_at->diffInDays($m->expires_at, false);

            $stronglyAnomalous = $actualDays < 0
                || $actualDays > $expectedDays * 1.5
                || ($expectedDays >= 180 && $actualDays < 30);

            if ($stronglyAnomalous) {
                $issues[] = 'duration_mismatch';
            }
        }

        return $issues;
    }
}
