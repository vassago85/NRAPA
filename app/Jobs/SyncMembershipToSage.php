<?php

namespace App\Jobs;

use App\Models\Membership;
use App\Services\NtfyService;
use App\Services\SageNetworkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMembershipToSage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Membership $membership,
    ) {}

    public function handle(SageNetworkService $sage): void
    {
        if (! SageNetworkService::isEnabled()) {
            return;
        }

        $membership = $this->membership->fresh(['user', 'type']);

        if (! $membership || ! $membership->user) {
            Log::warning('Sage sync: membership or user not found', [
                'membership_id' => $this->membership->id,
            ]);

            return;
        }

        $user = $membership->user;

        $companyId = $sage->upsertCompany($user);
        if (! $companyId) {
            throw new \RuntimeException("Sage: failed to upsert company for user {$user->id}");
        }

        $invoice = $sage->createInvoice($membership);
        if (! $invoice) {
            throw new \RuntimeException("Sage: failed to create invoice for membership {$membership->id}");
        }

        $invoiceId = $invoice['invoiceId'] ?? null;
        if ($invoiceId) {
            $sage->sendInvoice($invoiceId);
        }

        Log::info('Sage: membership synced successfully', [
            'membership_id' => $membership->id,
            'sage_company_id' => $companyId,
            'sage_invoice_id' => $invoiceId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sage sync job failed permanently', [
            'membership_id' => $this->membership->id,
            'error' => $exception->getMessage(),
        ]);

        try {
            $ntfy = app(NtfyService::class);
            $ntfy->notifyAdmins(
                'notify_system_errors',
                'Sage Invoice Failed',
                "Failed to sync membership #{$this->membership->id} to Sage: {$exception->getMessage()}",
                'high',
            );
        } catch (\Exception $e) {
            Log::warning('Sage: could not send failure notification', ['error' => $e->getMessage()]);
        }
    }
}
