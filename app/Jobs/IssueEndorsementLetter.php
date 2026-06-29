<?php

namespace App\Jobs;

use App\Mail\EndorsementApproved;
use App\Models\EndorsementRequest;
use App\Models\User;
use App\Services\EndorsementLetterIssuer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Generates the endorsement PDF (Gotenberg), uploads to storage, marks the
 * request as issued, and emails the member. Runs off the HTTP request so
 * admin approve/issue actions return immediately.
 */
class IssueEndorsementLetter implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    /**
     * How long (seconds) the uniqueness lock is held before it auto-expires.
     *
     * Without a TTL, a worker that is killed mid-job (e.g. during a deploy /
     * container recreate) leaves an orphaned lock in the cache forever, which
     * silently drops every subsequent dispatch — including the admin
     * "Retry Auto-Generate" button — leaving the endorsement stuck on
     * "Approved - Letter Pending". A short TTL lets the lock self-heal well
     * after the job's own timeout/backoff window has elapsed.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public int $endorsementRequestId,
        public int $adminUserId,
        public string $dedicatedCategory,
        public bool $dedicatedStatusCompliant = true,
        public bool $autoIssued = false,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $issuedVia = null,
    ) {}

    public function uniqueId(): string
    {
        return 'issue-endorsement-'.$this->endorsementRequestId;
    }

    public function handle(EndorsementLetterIssuer $issuer): void
    {
        $request = EndorsementRequest::find($this->endorsementRequestId);
        $admin = User::find($this->adminUserId);

        if (! $request || ! $admin) {
            Log::warning('Issue endorsement letter job: request or admin not found', [
                'endorsement_request_id' => $this->endorsementRequestId,
                'admin_user_id' => $this->adminUserId,
            ]);

            return;
        }

        $issuer->issueApprovedLetter(
            $request,
            $admin,
            $this->dedicatedCategory,
            $this->dedicatedStatusCompliant,
            $this->ipAddress,
            $this->userAgent,
            $this->autoIssued,
            $this->issuedVia,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Issue endorsement letter job failed permanently', [
            'endorsement_request_id' => $this->endorsementRequestId,
            'error' => $exception->getMessage(),
        ]);

        $request = EndorsementRequest::with('user')->find($this->endorsementRequestId);

        if (! $request || ! $request->isApproved() || $request->isIssued() || ! $request->user?->email) {
            return;
        }

        try {
            Mail::to($request->user->email)->queue(new EndorsementApproved(
                endorsement: $request->load('firearm', 'user'),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send endorsement approval fallback email after job failure', [
                'endorsement_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
