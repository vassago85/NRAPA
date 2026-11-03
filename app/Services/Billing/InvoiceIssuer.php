<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Membership;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single mint-point for NRAPA invoices.
 *
 * This class is idempotent on purpose: `forMembership` returns the existing
 * invoice if one has already been issued for that membership, so admin
 * clicks, the ApproveMembership hook, and the backfill command can all
 * safely race each other. Only the first caller wins and spends a number.
 *
 * Invoices are IMMUTABLE once minted. Line items and the payload snapshot
 * are stored on the row so later re-renders reprint exactly what was
 * invoiced, even if the member's address, the bank account, or the
 * membership-type pricing changes afterwards.
 */
class InvoiceIssuer
{
    public const SETTING_PHYSICAL_ADDRESS = 'invoice_business_physical_address';
    public const SETTING_POSTAL_ADDRESS = 'invoice_business_postal_address';
    public const SETTING_BUSINESS_NAME = 'invoice_business_name';
    public const SETTING_BUSINESS_VAT = 'invoice_business_vat_number';

    public const DEFAULT_BUSINESS_NAME = 'NRAPA: NATIONAL RIFLE AND PISTOL ASSOCIATION';
    public const DEFAULT_ADDRESS = "702 Esther Street, Garsfontein\nPretoria\n0042";

    public function __construct(private readonly InvoiceNumberSequence $sequence) {}

    /**
     * Ensure an invoice exists for the given membership and return it.
     *
     * @throws InvoiceNotIssuableException when the membership is not in a
     *                                      state where NRAPA can bill for it.
     */
    public function forMembership(Membership $membership, ?User $actor = null): Invoice
    {
        if (! $membership->exists) {
            throw new InvoiceNotIssuableException('Cannot invoice an unsaved membership.');
        }

        // Fast path: already invoiced. Keep the first invoice; never re-mint.
        $existing = Invoice::where('source_type', Membership::class)
            ->where('source_id', $membership->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $this->assertBillable($membership);

        $membership->loadMissing(['user', 'type', 'previousMembership.type', 'affiliatedClub']);

        if (! $membership->user) {
            throw new InvoiceNotIssuableException('Cannot invoice a membership without a member.');
        }

        return DB::transaction(function () use ($membership, $actor): Invoice {
            // Re-check inside the transaction in case another request raced us.
            $existing = Invoice::where('source_type', Membership::class)
                ->where('source_id', $membership->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $amount = round((float) $membership->amount_due, 2);
            $line = $this->buildMembershipLineItem($membership, $amount);

            $invoice = Invoice::create([
                'number' => $this->sequence->next(),
                'user_id' => $membership->user_id,
                'source_type' => Membership::class,
                'source_id' => $membership->id,
                'issued_at' => $membership->payment_confirmed_at,
                'currency' => 'ZAR',
                'subtotal' => $amount,
                'vat_rate' => 0,
                'vat_amount' => 0,
                'total' => $amount,
                'reference' => $membership->payment_reference,
                'line_items' => [$line],
                'payload' => $this->buildPayload($membership),
                'issued_by' => $actor?->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Build the single line item for a membership invoice.
     *
     * Mirrors the Sage sample layout: a SKU-ish code followed by a human
     * description. New members get NEWMEM; renewals (same type) get
     * RENEWMEM; type changes get UPGRADE so the ledger shows the
     * difference without an extra schema column.
     *
     * @return array{sku: string, description: string, qty: int, unit_price: float, discount_pct: float, vat_pct: float, excl_total: float, incl_total: float}
     */
    private function buildMembershipLineItem(Membership $membership, float $amount): array
    {
        $typeName = $membership->type?->name ?? 'Membership';

        if ($membership->status === 'pending_payment' || $membership->isTypeChange()) {
            $sku = 'UPGRADE';
            $description = 'Membership upgrade to '.$typeName;
        } elseif ($membership->isRenewal()) {
            $sku = 'RENEWMEM';
            $description = 'Annual Membership Renewal — '.$typeName;
        } else {
            $sku = 'NEWMEM';
            $description = 'New Membership — '.$typeName;
        }

        return [
            'sku' => $sku,
            'description' => $sku.' - '.$description,
            'qty' => 1,
            'unit_price' => $amount,
            'discount_pct' => 0.0,
            'vat_pct' => 0.0,
            'excl_total' => $amount,
            'incl_total' => $amount,
        ];
    }

    /**
     * Snapshot of everything the PDF needs to re-render identically later.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(Membership $membership): array
    {
        $user = $membership->user;
        $bank = SystemSetting::getBankAccount();

        return [
            'from' => [
                'name' => (string) SystemSetting::get(self::SETTING_BUSINESS_NAME, self::DEFAULT_BUSINESS_NAME),
                'vat_number' => (string) SystemSetting::get(self::SETTING_BUSINESS_VAT, ''),
                'physical_address' => (string) SystemSetting::get(self::SETTING_PHYSICAL_ADDRESS, self::DEFAULT_ADDRESS),
                'postal_address' => (string) SystemSetting::get(self::SETTING_POSTAL_ADDRESS, self::DEFAULT_ADDRESS),
            ],
            'to' => [
                'id_number' => (string) ($user->id_number ?? ''),
                'name' => strtoupper((string) ($user->name ?? '')),
                'customer_vat_number' => '',
                'joining_date' => optional($user->joinedOn())->format('d/m/Y') ?? '',
                'physical_address' => (string) ($user->physical_address ?? ''),
                'postal_address' => (string) ($user->postal_address ?? ''),
                'email' => (string) ($user->email ?? ''),
            ],
            'bank' => [
                'bank_name' => (string) ($bank['bank_name'] ?? ''),
                'account_name' => (string) ($bank['account_name'] ?? ''),
                'account_number' => (string) ($bank['account_number'] ?? ''),
                'branch_code' => (string) ($bank['branch_code'] ?? ''),
                'account_type' => (string) ($bank['account_type'] ?? ''),
            ],
            'membership' => [
                'number' => (string) ($membership->membership_number ?? ''),
                'type' => (string) ($membership->type?->name ?? ''),
                'is_renewal' => (bool) $membership->isRenewal(),
            ],
        ];
    }

    private function assertBillable(Membership $membership): void
    {
        if (! $membership->isBillable()) {
            throw new InvoiceNotIssuableException(
                'Membership '.$membership->id.' is not billable (source: '.$membership->source.').'
            );
        }

        if (! $membership->payment_confirmed_at) {
            throw new InvoiceNotIssuableException(
                'Membership '.$membership->id.' has no payment_confirmed_at — no invoice to issue.'
            );
        }
    }
}
