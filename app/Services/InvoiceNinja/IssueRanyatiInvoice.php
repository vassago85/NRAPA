<?php

namespace App\Services\InvoiceNinja;

use App\Models\Membership;
use App\Models\RanyatiInvoice;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IssueRanyatiInvoice
{
    public function __construct(
        private InvoiceNinjaClient $client,
        private RanyatiInvoiceSettings $settings,
    ) {}

    /**
     * @return array{
     *     period: string,
     *     label: string,
     *     year: int,
     *     month: int,
     *     new_members: int,
     *     renewals: int,
     *     hosting_fee: float,
     *     member_fee: float,
     *     total: float
     * }
     */
    public function preview(int $year, int $month): array
    {
        $newMembers = Membership::billable()->newInMonth($year, $month)->count();
        $renewals = Membership::billable()->renewalsInMonth($year, $month)->count();
        $hosting = $this->settings->hostingFee();
        $memberFee = $this->settings->memberFee();
        $periodStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Africa/Johannesburg');

        return [
            'period' => sprintf('%04d-%02d', $year, $month),
            'label' => $periodStart->format('F Y'),
            'year' => $year,
            'month' => $month,
            'new_members' => $newMembers,
            'renewals' => $renewals,
            'hosting_fee' => $hosting,
            'member_fee' => $memberFee,
            'total' => round($hosting + (($newMembers + $renewals) * $memberFee), 2),
        ];
    }

    public function issue(int $year, int $month, bool $sendEmail = true): RanyatiInvoice
    {
        if (! $this->settings->isConfigured()) {
            throw new InvoiceNinjaException('Invoice Ninja is not configured. Add the API URL, token, and Ranyati client ID in Owner Settings.');
        }

        $preview = $this->preview($year, $month);
        $lock = Cache::lock('ranyati-invoice:'.$preview['period'], 90);

        if (! $lock->get()) {
            throw new InvoiceNinjaException('Another invoice for '.$preview['label'].' is already being created.');
        }

        try {
            return $this->issueLocked($preview, $sendEmail);
        } finally {
            $lock->release();
        }
    }

    public function retryUnsent(): int
    {
        if (! $this->settings->isConfigured()) {
            return 0;
        }

        $sent = 0;

        RanyatiInvoice::query()
            ->where('status', RanyatiInvoice::STATUS_CREATED)
            ->whereNotNull('invoice_ninja_id')
            ->orderBy('period')
            ->each(function (RanyatiInvoice $invoice) use (&$sent) {
                try {
                    $this->email($invoice);
                    $sent++;
                } catch (InvoiceNinjaException) {
                    // email() already stored the error. Keep going so a stuck
                    // older invoice does not block this month.
                }
            });

        return $sent;
    }

    /**
     * @param  array{
     *     period: string,
     *     label: string,
     *     year: int,
     *     month: int,
     *     new_members: int,
     *     renewals: int,
     *     hosting_fee: float,
     *     member_fee: float,
     *     total: float
     * }  $preview
     */
    private function issueLocked(array $preview, bool $sendEmail): RanyatiInvoice
    {
        $invoice = $this->claim($preview);

        if ($invoice->status === RanyatiInvoice::STATUS_SENT) {
            return $invoice;
        }

        if ($invoice->invoice_ninja_id) {
            return $sendEmail ? $this->email($invoice) : $invoice;
        }

        try {
            $remote = $this->client->createInvoice($this->payload($preview));
        } catch (InvoiceNinjaException $e) {
            $invoice->update([
                'status' => RanyatiInvoice::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
            Log::warning('Ranyati Invoice Ninja invoice failed', [
                'period' => $preview['period'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $remoteId = isset($remote['id']) ? (string) $remote['id'] : '';

        if ($remoteId === '') {
            $invoice->update([
                'status' => RanyatiInvoice::STATUS_FAILED,
                'error' => 'Invoice Ninja did not return an invoice id.',
            ]);

            throw new InvoiceNinjaException('Invoice Ninja did not return an invoice id.');
        }

        $invoice->update([
            'invoice_ninja_id' => $remoteId,
            'invoice_number' => isset($remote['number']) ? (string) $remote['number'] : null,
            'status' => RanyatiInvoice::STATUS_CREATED,
            'error' => null,
        ]);

        $invoice = $invoice->refresh();

        return $sendEmail ? $this->email($invoice) : $invoice;
    }

    /**
     * @param  array{
     *     period: string,
     *     label: string,
     *     year: int,
     *     month: int,
     *     new_members: int,
     *     renewals: int,
     *     hosting_fee: float,
     *     member_fee: float,
     *     total: float
     * }  $preview
     */
    private function claim(array $preview): RanyatiInvoice
    {
        $existing = RanyatiInvoice::query()->where('period', $preview['period'])->first();

        if ($existing && ($existing->status === RanyatiInvoice::STATUS_SENT || $existing->invoice_ninja_id)) {
            return $existing;
        }

        $attributes = [
            'hosting_fee' => $preview['hosting_fee'],
            'member_fee' => $preview['member_fee'],
            'new_members' => $preview['new_members'],
            'renewals' => $preview['renewals'],
            'total' => $preview['total'],
            'status' => RanyatiInvoice::STATUS_PENDING,
            'error' => null,
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        try {
            return RanyatiInvoice::create(['period' => $preview['period']] + $attributes);
        } catch (UniqueConstraintViolationException) {
            return RanyatiInvoice::query()->where('period', $preview['period'])->firstOrFail();
        }
    }

    private function email(RanyatiInvoice $invoice): RanyatiInvoice
    {
        try {
            $this->client->emailInvoice((string) $invoice->invoice_ninja_id);
        } catch (InvoiceNinjaException $e) {
            $invoice->update(['error' => $e->getMessage()]);
            Log::warning('Ranyati Invoice Ninja email failed', [
                'period' => $invoice->period,
                'invoice_ninja_id' => $invoice->invoice_ninja_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $invoice->update([
            'status' => RanyatiInvoice::STATUS_SENT,
            'error' => null,
            'sent_at' => now(),
        ]);

        return $invoice->refresh();
    }

    /**
     * @param  array{
     *     period: string,
     *     label: string,
     *     year: int,
     *     month: int,
     *     new_members: int,
     *     renewals: int,
     *     hosting_fee: float,
     *     member_fee: float,
     *     total: float
     * }  $preview
     * @return array<string, mixed>
     */
    private function payload(array $preview): array
    {
        $invoiceDate = Carbon::create($preview['year'], $preview['month'], 1, 0, 0, 0, 'Africa/Johannesburg')
            ->addMonth()
            ->startOfDay();
        $today = now()->timezone('Africa/Johannesburg')->startOfDay();

        if ($invoiceDate->greaterThan($today)) {
            $invoiceDate = $today->copy();
        }

        $members = $preview['new_members'] + $preview['renewals'];
        $feeLabel = $this->money($preview['member_fee']);

        $lineItems = [
            $this->line(
                'NRAPA hosting',
                'Monthly hosting for '.$preview['label'],
                $preview['hosting_fee'],
                1,
            ),
        ];

        if ($members > 0) {
            $lineItems[] = $this->line(
                'NRAPA new members and renewals',
                $preview['label'].': '.$preview['new_members'].' new, '.$preview['renewals'].' renewals, at '.$feeLabel.' each. Counted when payment was confirmed.',
                $preview['member_fee'],
                $members,
            );
        }

        return [
            'client_id' => $this->settings->clientId(),
            'date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addDays(RanyatiInvoiceSettings::PAYMENT_TERMS_DAYS)->toDateString(),
            'po_number' => 'NRAPA-'.$preview['period'],
            'public_notes' => 'NRAPA platform for '.$preview['label'].'. Hosting plus '.$feeLabel.' for each new member and renewal paid in the month.',
            'line_items' => $lineItems,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function line(string $key, string $notes, float $cost, int $quantity): array
    {
        return [
            'product_key' => $key,
            'notes' => $notes,
            'cost' => $cost,
            'quantity' => $quantity,
            'tax_name1' => '',
            'tax_rate1' => 0,
            'tax_name2' => '',
            'tax_rate2' => 0,
            'tax_name3' => '',
            'tax_rate3' => 0,
        ];
    }

    private function money(float $amount): string
    {
        $decimals = abs($amount - round($amount)) < 0.001 ? 0 : 2;

        return 'R'.number_format($amount, $decimals, '.', '');
    }
}
