<?php

namespace App\Jobs;

use App\Models\EndorsementRequest;
use App\Services\NtfyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends NTFY push notifications to admins/owners/developers when a member
 * submits an endorsement request. Runs out-of-band so the member's submit
 * action returns immediately instead of waiting on remote HTTP calls.
 */
class NotifyAdminsOfEndorsementSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public EndorsementRequest $request,
    ) {}

    public function handle(NtfyService $ntfy): void
    {
        $request = $this->request->fresh(['user', 'firearm', 'components']);

        if (! $request || ! $request->user) {
            Log::warning('Endorsement notification job: request or user not found', [
                'endorsement_request_id' => $this->request->id,
            ]);

            return;
        }

        $title = 'New Endorsement Request Submitted';
        $subject = $request->firearm
            ? (trim(($request->firearm->make ?? '').' '.($request->firearm->model ?? '')) ?: 'a firearm')
            : ($request->components->first()?->summary ?? 'a component');
        $message = sprintf(
            '%s has submitted an endorsement request for %s. Review and approve at: %s',
            $request->user->name,
            $subject,
            route('admin.endorsements.show', $request->uuid)
        );

        $ntfy->notifyAdmins(
            'endorsement_request',
            $title,
            $message,
            'high',
            [
                'endorsement_request_id' => $request->id,
                'endorsement_request_uuid' => $request->uuid,
                'user_id' => $request->user_id,
                'user_name' => $request->user->name,
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Endorsement submission notification job failed permanently', [
            'endorsement_request_id' => $this->request->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
