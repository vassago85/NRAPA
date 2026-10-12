<?php

namespace App\Services\Approvals;

use App\Models\Membership;
use App\Models\Payment;
use App\Models\SystemSetting;

/**
 * Keeps one Payment row per payment attempt in step with the membership it
 * pays for. Proof of payment and confirmation still live on the membership
 * (member pages and billing read them there); the Payment row is what the
 * approval queue decides on.
 */
class PaymentLedger
{
    public function syncForMembership(Membership $membership): ?Payment
    {
        if (! $membership->exists || $membership->trashed()) {
            return null;
        }

        $needsPayment = $this->needsPayment($membership);
        $payment = Payment::where('membership_id', $membership->id)->latest('id')->first();

        if (! $payment || in_array($payment->status, ['failed', 'cancelled'], true)) {
            if (! $needsPayment || $membership->payment_confirmed_at) {
                return $payment;
            }

            return $this->open($membership, $payment ? Payment::where('membership_id', $membership->id)->count() + 1 : 1);
        }

        if ($payment->status !== 'pending') {
            return $payment;
        }

        if ($membership->payment_confirmed_at) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => $membership->payment_confirmed_at,
                'processed_at' => now(),
            ]);

            return $payment;
        }

        if (! $needsPayment) {
            $payment->update(['status' => 'cancelled']);

            return $payment;
        }

        $updates = [];
        if ($membership->proof_of_payment_path !== $payment->proof_of_payment_path) {
            $updates['proof_of_payment_path'] = $membership->proof_of_payment_path;
            $updates['submitted_at'] = $membership->proof_of_payment_path ? now() : null;
        }

        $amount = round((float) $membership->amount_due, 2);
        if ($amount > 0 && (float) $payment->amount !== $amount) {
            $updates['amount'] = $amount;
        }

        if ($updates) {
            $payment->update($updates);
        }

        return $payment;
    }

    public function needsPayment(Membership $membership): bool
    {
        if ($membership->status === 'pending_payment') {
            return true;
        }

        return $membership->status === 'applied' && $membership->isBillable();
    }

    public static function referencePattern(): string
    {
        $prefix = preg_quote((string) SystemSetting::get('bank_reference_prefix', 'NRAPA'), '/');

        return '/^'.$prefix.'-[A-Z]{1,10}-\d{4}$/';
    }

    public static function referenceMatches(?string $reference): bool
    {
        return $reference !== null && preg_match(self::referencePattern(), $reference) === 1;
    }

    private function open(Membership $membership, int $attempt): Payment
    {
        $base = $membership->payment_reference ?: 'PAY-'.strtoupper(substr((string) $membership->uuid, 0, 8));
        $reference = $attempt > 1 ? "{$base}-{$attempt}" : $base;

        while (Payment::where('reference', $reference)->exists()) {
            $attempt++;
            $reference = "{$base}-{$attempt}";
        }

        return Payment::create([
            'user_id' => $membership->user_id,
            'membership_id' => $membership->id,
            'amount' => round((float) $membership->amount_due, 2),
            'currency' => 'ZAR',
            'reference' => $reference,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'proof_of_payment_path' => $membership->proof_of_payment_path,
            'submitted_at' => $membership->proof_of_payment_path ? now() : null,
        ]);
    }
}
