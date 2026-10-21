<?php

namespace App\Jobs;

use App\Enums\MembershipState;
use App\Mail\MemberMessageMail;
use App\Models\AuditLog;
use App\Models\MemberMessage;
use App\Models\MembershipType;
use App\Models\User;
use App\Queries\MemberDirectory;
use App\Services\NtfyService;
use App\Support\Members\MemberLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Applies one bulk action from the members index. Targets are either an
 * explicit id list or a whole segment (plus search), re-resolved here so the
 * job acts on the segment as it stands, not on a stale page of ids.
 */
class BulkMemberAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTION_MESSAGE = 'message';

    public const ACTION_CHANGE_TYPE = 'change_type';

    public const ACTIONS = [self::ACTION_MESSAGE, self::ACTION_CHANGE_TYPE];

    public int $tries = 1;

    public int $timeout = 1800;

    public string $batchId;

    /**
     * @param  array<string, mixed>  $payload  message: subject, body. change_type: membership_type_id.
     * @param  array<int, int>  $memberIds  Ignored when $segment is set.
     */
    public function __construct(
        public int $actorId,
        public string $action,
        public array $payload,
        public array $memberIds = [],
        public ?string $segment = null,
        public string $search = '',
    ) {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException("Unknown bulk member action [{$action}].");
        }

        $this->batchId = (string) Str::uuid();
    }

    public static function targets(?string $segment, string $search, array $memberIds): Builder
    {
        $query = MemberDirectory::query($segment ?? 'all', $segment !== null ? $search : '');

        return $segment !== null
            ? $query
            : $query->whereIn('users.id', array_map('intval', $memberIds));
    }

    public function handle(): void
    {
        $actor = User::find($this->actorId);
        if (! $actor || ! $actor->isAdmin()) {
            return;
        }

        $type = $this->action === self::ACTION_CHANGE_TYPE
            ? MembershipType::where('is_active', true)->find($this->payload['membership_type_id'] ?? null)
            : null;

        if ($this->action === self::ACTION_CHANGE_TYPE && ! $type) {
            return;
        }

        $tally = ['done' => 0, 'skipped' => 0, 'emailed' => 0, 'failed' => 0];

        self::targets($this->segment, $this->search, $this->memberIds)
            ->select('users.*')
            ->chunkById(200, function ($members) use ($actor, $type, &$tally) {
                $members->load(['memberships' => fn ($q) => $q->with('type')->latest('id')]);

                foreach ($members as $member) {
                    if (Gate::forUser($actor)->denies('bulkManage', $member)) {
                        $tally['skipped']++;

                        continue;
                    }

                    try {
                        match ($this->action) {
                            self::ACTION_MESSAGE => $this->sendMessage($actor, $member, $tally),
                            self::ACTION_CHANGE_TYPE => $this->changeType($actor, $member, $type, $tally),
                        };
                    } catch (\Throwable $e) {
                        $tally['failed']++;
                        Log::warning('Bulk member action failed for member', [
                            'action' => $this->action,
                            'batch' => $this->batchId,
                            'user_id' => $member->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }, 'users.id', 'id');

        try {
            app(NtfyService::class)->notifyAdmins(
                'new_member',
                'Bulk member action finished',
                "{$actor->name}: {$this->action} — {$tally['done']} done, {$tally['skipped']} skipped, {$tally['failed']} failed.",
                'low',
            );
        } catch (\Throwable) {
        }
    }

    private function sendMessage(User $actor, User $member, array &$tally): void
    {
        // Per-member token replacement happens here, inside the chunk loop,
        // so each member gets their own values ({first_name}, {expiry}, etc.)
        // in both the inbox copy and the email that is sent.
        $rawSubject = trim((string) ($this->payload['subject'] ?? ''));
        $rawBody = trim((string) ($this->payload['body'] ?? ''));

        $message = MemberMessage::create([
            'user_id' => $member->id,
            'sent_by_user_id' => $actor->id,
            'direction' => MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
            'subject' => MemberLetter::render($rawSubject, $member),
            'body' => MemberLetter::render($rawBody, $member),
        ]);

        $emailed = false;
        // Bulk admin → member broadcasts are non-essential: skip the email
        // when the member has unsubscribed (the inbox copy still writes).
        if ($member->canReceiveNonEssentialEmail()) {
            try {
                Mail::to($member->email)->send(new MemberMessageMail($message));
                $message->update(['email_sent_at' => now()]);
                $emailed = true;
                $tally['emailed']++;
            } catch (\Throwable $e) {
                Log::warning('Failed to send bulk member message email', [
                    'user_id' => $member->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AuditLog::log('member.bulk_message_sent', $member, null, [
            'member_message_id' => $message->id,
            'subject' => $message->subject,
        ], $actor, $this->meta(['emailed' => $emailed, 'email_status' => $member->email_status]));

        $tally['done']++;
    }

    private function changeType(User $actor, User $member, MembershipType $type, array &$tally): void
    {
        $today = now()->startOfDay();
        $membership = $member->memberships->first(fn ($m) => MembershipState::isCurrent($m, $today))
            ?? $member->memberships->first();

        if (! $membership || (int) $membership->membership_type_id === (int) $type->id) {
            $tally['skipped']++;

            return;
        }

        $oldTypeId = $membership->membership_type_id;
        $membership->update(['membership_type_id' => $type->id]);

        AuditLog::log('member.bulk_membership_type_changed', $member, [
            'membership_id' => $membership->id,
            'membership_type_id' => $oldTypeId,
        ], [
            'membership_id' => $membership->id,
            'membership_type_id' => $type->id,
        ], $actor, $this->meta());

        $tally['done']++;
    }

    private function meta(array $extra = []): array
    {
        return [
            'bulk_batch' => $this->batchId,
            'action' => $this->action,
            'segment' => $this->segment,
            'search' => $this->segment !== null && $this->search !== '' ? $this->search : null,
        ] + $extra;
    }
}
