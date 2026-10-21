<?php

namespace App\Livewire\Concerns;

use App\Helpers\StorageHelper;
use App\Models\Membership;
use App\Support\Membership\ProofOfPaymentUpload;
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

        $resubmit = $membership->awaitsPopResubmit();

        app(ProofOfPaymentUpload::class)->store($membership, $this->proofOfPayment, auth()->user());
        $this->proofOfPayment = null;

        session()->flash('success', $resubmit
            ? 'Proof of payment resubmitted. An administrator will review it.'
            : 'Proof of payment uploaded successfully. It will be reviewed by an administrator.');
        $this->refreshPaymentUploadState();
    }

    public function removeProofOfPayment(int $membershipId): void
    {
        $membership = $this->resolveMembershipForPaymentUpload($membershipId);

        if ($membership->proof_of_payment_path) {
            Storage::disk(StorageHelper::getPrivateDisk())->delete($membership->proof_of_payment_path);
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
