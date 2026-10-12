<?php

namespace App\Actions\Memberships;

use App\Mail\MembershipRejected;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Rejects an application or a type change. Uses the revoked_* columns so a
 * rejection never stamps approved_at (the billing report keys off it).
 */
class RejectMembership
{
    public function handle(Membership $membership, User $admin, string $reason): void
    {
        if (! in_array($membership->status, ['applied', 'pending_change', 'pending_payment'], true)) {
            throw new \DomainException('This request has already been processed.');
        }

        $membership->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'revocation_reason' => $reason,
            'suspension_reason' => $reason,
        ]);

        if ($membership->proof_of_payment_path) {
            try {
                Storage::disk(\App\Helpers\StorageHelper::getPrivateDisk())->delete($membership->proof_of_payment_path);
            } catch (\Throwable $e) {
            }
            $membership->update(['proof_of_payment_path' => null]);
        }

        try {
            if ($membership->user?->email) {
                Mail::to($membership->user->email)->send(new MembershipRejected(
                    membership: $membership->load('type', 'user'),
                    reason: $reason,
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send membership rejection email', ['membership_id' => $membership->id, 'error' => $e->getMessage()]);
        }
    }
}
