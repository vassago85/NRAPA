<?php

namespace App\Enums;

use App\Models\Approval;
use App\Models\User;

/**
 * The result of asking {@see \App\Services\Approvals\ChaseRunner} to chase an
 * approval. Every outcome maps to a sentence an admin can read without needing
 * to know how the chase pipeline works.
 */
enum ChaseOutcome: string
{
    case Sent = 'sent';
    case AlreadyChasedRecently = 'already_chased_recently';
    case MemberCannotReceiveEmail = 'member_cannot_receive_email';
    case NotWaitingOnMember = 'not_waiting_on_member';
    case AlreadyChasedForStage = 'already_chased_for_stage';
    case SendFailed = 'send_failed';

    /** Did the chase email actually leave? */
    public function sent(): bool
    {
        return $this === self::Sent;
    }

    /** Short message to flash to the admin after a UI chase. */
    public function describe(Approval $approval, ?User $member, ?string $detail = null): string
    {
        $name = $member?->name ?? 'the member';

        return match ($this) {
            self::Sent => 'Chased '.$name.'. They will get the reminder shortly.',
            self::AlreadyChasedRecently => $name.' was already chased in the last 24 hours. Try again tomorrow.',
            self::MemberCannotReceiveEmail => 'Not chased: '.$name.'\'s email has hard-bounced or complained. Fix the address first.',
            self::NotWaitingOnMember => 'Nothing to chase: this item is not waiting on the member any more.',
            self::AlreadyChasedForStage => 'Already chased for this stage in the current waiting spell.',
            self::SendFailed => 'The chase email could not be sent: '.($detail ?? 'unknown error').'.',
        };
    }
}
