<?php

namespace App\Support\Membership;

use App\Helpers\StorageHelper;
use App\Models\Membership;
use App\Models\User;
use App\Services\NtfyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProofOfPaymentUpload
{
    public function store(Membership $membership, UploadedFile|TemporaryUploadedFile $file, User $uploader): void
    {
        $wasResubmit = $membership->awaitsPopResubmit();
        $disk = StorageHelper::getPrivateDisk();
        $previous = $membership->proof_of_payment_path;

        $path = $file->store("proof-of-payment/{$membership->user_id}", $disk);

        $membership->update([
            'proof_of_payment_path' => $path,
            'pop_resubmit_requested_at' => null,
            'pop_resubmit_message' => null,
        ]);

        if ($previous && $previous !== $path) {
            try {
                Storage::disk($disk)->delete($previous);
            } catch (\Throwable $e) {
            }
        }

        try {
            $verb = $wasResubmit ? 'resubmitted' : 'uploaded';
            app(NtfyService::class)->notifyAdmins(
                'payment_received',
                $wasResubmit ? 'Proof of Payment Resubmitted' : 'Proof of Payment Uploaded',
                "{$uploader->name} ({$membership->membership_number}) {$verb} proof of payment. Pending review.",
            );
        } catch (\Throwable $e) {
        }
    }
}
