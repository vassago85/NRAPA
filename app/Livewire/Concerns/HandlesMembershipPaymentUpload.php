<?php

namespace App\Livewire\Concerns;

use App\Models\Membership;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

trait HandlesMembershipPaymentUpload
{
    use WithFileUploads;

    public $proofOfPayment = null;

    public function uploadProofOfPayment(int $membershipId): void
    {
        $membership = $this->resolveMembershipForPaymentUpload($membershipId);

        $this->validate([
            'proofOfPayment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $this->proofOfPayment->store(
            "proof-of-payment/{$membership->user_id}",
            'r2',
        );

        $membership->update(['proof_of_payment_path' => $path]);
        $this->proofOfPayment = null;

        try {
            $user = auth()->user();
            app(\App\Services\NtfyService::class)->notifyAdmins(
                'payment_received',
                'Proof of Payment Uploaded',
                "{$user->name} ({$membership->membership_number}) uploaded proof of payment. Pending review.",
            );
        } catch (\Exception $e) {
            // Non-critical
        }

        session()->flash('success', 'Proof of payment uploaded successfully. It will be reviewed by an administrator.');
        $this->refreshPaymentUploadState();
    }

    public function removeProofOfPayment(int $membershipId): void
    {
        $membership = $this->resolveMembershipForPaymentUpload($membershipId);

        if ($membership->proof_of_payment_path) {
            Storage::disk('r2')->delete($membership->proof_of_payment_path);
            $membership->update(['proof_of_payment_path' => null]);
            session()->flash('success', 'Proof of payment removed.');
            $this->refreshPaymentUploadState();
        }
    }

    protected function awaitingPaymentMembershipQuery()
    {
        return auth()->user()->memberships()
            ->whereNull('payment_confirmed_at')
            ->where(function ($query) {
                $query->where(function ($applied) {
                    $applied->where('status', 'applied')
                        ->whereNotNull('payment_reference');
                })->orWhere('status', 'pending_payment');
            })
            ->with(['type', 'previousMembership.type']);
    }

    protected function resolveMembershipForPaymentUpload(int $membershipId): Membership
    {
        $membership = auth()->user()->memberships()->find($membershipId);

        if (! $membership) {
            abort(403);
        }

        if (! in_array($membership->status, ['applied', 'pending_payment'], true)) {
            abort(403);
        }

        return $membership;
    }

    protected function refreshPaymentUploadState(): void
    {
        unset($this->awaitingPaymentMembership);

        if (method_exists($this, 'user')) {
            unset($this->user);
        }
    }
}
