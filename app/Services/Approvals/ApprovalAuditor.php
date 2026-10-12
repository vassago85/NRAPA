<?php

namespace App\Services\Approvals;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\User;

/**
 * Writes the audit entry for an approval decision. The entry exists so a
 * decision can be reconstructed later; it is never aggregated per actor.
 */
class ApprovalAuditor
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(Approval $approval, string $event, ?User $actor, array $meta = []): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $approval->approvable_type,
            'auditable_id' => $approval->approvable_id,
            'old_values' => null,
            'new_values' => ['state' => $approval->state->value],
            'meta' => array_merge([
                'approval_id' => $approval->id,
                'type' => $approval->type->value,
                'member_id' => $approval->user_id,
            ], $meta),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
