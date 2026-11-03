<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Membership;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\InvoiceNotIssuableException;
use Illuminate\Console\Command;

/**
 * One-shot (and idempotent) backfill: issue an I-NRAPA invoice for every
 * billable membership whose payment is confirmed but which has never been
 * invoiced.
 *
 * Memberships are processed strictly in payment_confirmed_at order so the
 * resulting sequence reads chronologically. Re-running the command after
 * approvals have added more rows is safe — the InvoiceIssuer short-circuits
 * for already-invoiced memberships.
 */
class BackfillInvoices extends Command
{
    protected $signature = 'nrapa:backfill-invoices
        {--dry-run : Report what would be issued without writing anything}
        {--chunk=200 : Memberships per chunk when iterating}';

    protected $description = 'Issue invoices for all historical confirmed billable memberships (payment_confirmed_at order).';

    public function handle(InvoiceIssuer $issuer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(10, (int) $this->option('chunk'));

        $query = Membership::query()
            ->billable()
            ->whereNotNull('payment_confirmed_at')
            ->with(['user', 'type', 'previousMembership.type', 'affiliatedClub'])
            ->orderBy('payment_confirmed_at')
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Scanning {$total} confirmed billable memberships...");

        $issued = 0;
        $skippedExisting = 0;
        $failed = 0;

        $query->chunkById($chunk, function ($memberships) use ($issuer, $dryRun, &$issued, &$skippedExisting, &$failed) {
            foreach ($memberships as $membership) {
                $alreadyInvoiced = Invoice::where('source_type', Membership::class)
                    ->where('source_id', $membership->id)
                    ->exists();

                if ($alreadyInvoiced) {
                    $skippedExisting++;

                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '  would issue for membership #%d (%s, R%s, paid %s)',
                        $membership->id,
                        $membership->user?->name ?? 'unknown',
                        number_format((float) $membership->amount_due, 2),
                        optional($membership->payment_confirmed_at)->format('Y-m-d') ?? '—',
                    ));
                    $issued++;

                    continue;
                }

                try {
                    $invoice = $issuer->forMembership($membership);
                    $this->line("  issued {$invoice->number} for membership #{$membership->id}");
                    $issued++;
                } catch (InvoiceNotIssuableException $e) {
                    $failed++;
                    $this->warn("  skipped membership #{$membership->id}: ".$e->getMessage());
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("  failed membership #{$membership->id}: ".$e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info("Done. Issued: {$issued}, already-invoiced: {$skippedExisting}, failed: {$failed}.");

        return self::SUCCESS;
    }
}
