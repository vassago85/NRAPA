<?php

namespace App\Enums;

enum ApprovalState: string
{
    case WaitingOnAdmin = 'waiting_on_admin';
    case WaitingOnMember = 'waiting_on_member';
    case Approved = 'approved';
    case Declined = 'declined';
    case ChangesRequested = 'changes_requested';
    case Withdrawn = 'withdrawn';

    public function isOpen(): bool
    {
        return in_array($this, [self::WaitingOnAdmin, self::WaitingOnMember], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::WaitingOnAdmin => 'Waiting on admin',
            self::WaitingOnMember => 'Waiting on member',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::ChangesRequested => 'Changes requested',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [self::WaitingOnAdmin->value, self::WaitingOnMember->value];
    }
}
