<?php

namespace App\Console\Commands;

use App\Services\InvoiceNinja\InvoiceNinjaException;
use App\Services\InvoiceNinja\IssueRanyatiInvoice;
use App\Services\InvoiceNinja\RanyatiInvoiceSettings;
use Illuminate\Console\Command;

class InvoiceRanyati extends Command
{
    protected $signature = 'nrapa:invoice-ranyati
        {period? : Month to invoice, as YYYY-MM. Defaults to the previous month.}
        {--dry-run : Print the invoice without sending it}
        {--no-email : Create the invoice in Invoice Ninja without emailing it}';

    protected $description = 'Invoice Ranyati in Invoice Ninja for NRAPA hosting and that month\'s new members and renewals';

    public function handle(IssueRanyatiInvoice $issuer, RanyatiInvoiceSettings $settings): int
    {
        $period = $this->argument('period') ?: now()->timezone('Africa/Johannesburg')->subMonth()->format('Y-m');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $period)) {
            $this->error('Period must be YYYY-MM.');

            return self::FAILURE;
        }

        [$year, $month] = array_map('intval', explode('-', (string) $period));
        $preview = $issuer->preview($year, $month);
        $members = $preview['new_members'] + $preview['renewals'];

        $this->info(sprintf(
            '%s: hosting R%s + %d members × R%s = R%s',
            $preview['label'],
            number_format($preview['hosting_fee'], 2),
            $members,
            number_format($preview['member_fee'], 2),
            number_format($preview['total'], 2),
        ));

        if ($this->option('dry-run')) {
            $this->comment('Dry run. Nothing was sent to Invoice Ninja.');

            return self::SUCCESS;
        }

        if (! $settings->enabled()) {
            $this->comment('Ranyati invoicing is turned off. Enable it in Owner Settings → Invoice Ninja.');

            return self::SUCCESS;
        }

        try {
            if (! $this->option('no-email')) {
                $retried = $issuer->retryUnsent();
                if ($retried > 0) {
                    $this->info("Emailed {$retried} invoice(s) that had been created but not sent.");
                }
            }

            $invoice = $issuer->issue($year, $month, sendEmail: ! $this->option('no-email'));
        } catch (InvoiceNinjaException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $number = $invoice->invoice_number ?: $invoice->invoice_ninja_id;
        $this->info("Invoice {$number} is {$invoice->status} for {$invoice->period} (R".number_format((float) $invoice->total, 2).').');

        return self::SUCCESS;
    }
}
