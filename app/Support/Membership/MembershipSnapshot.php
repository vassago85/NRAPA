<?php

namespace App\Support\Membership;

use Carbon\CarbonImmutable;

/**
 * Point-in-time membership counts. Every admin page reads these numbers from
 * here so the dashboard, members index and reports always agree.
 *
 * The five states (active, suspended, expired, pending, none) are mutually
 * exclusive and add up to total. expiring30 and awaitingPayment overlap them.
 */
final readonly class MembershipSnapshot
{
    public function __construct(
        public int $total,
        public int $active,
        public int $suspended,
        public int $expired,
        public int $pending,
        public int $none,
        public int $expiring30,
        public int $awaitingPayment,
        public CarbonImmutable $computedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            total: (int) ($data['total'] ?? 0),
            active: (int) ($data['active'] ?? 0),
            suspended: (int) ($data['suspended'] ?? 0),
            expired: (int) ($data['expired'] ?? 0),
            pending: (int) ($data['pending'] ?? 0),
            none: (int) ($data['none'] ?? 0),
            expiring30: (int) ($data['expiring_30'] ?? 0),
            awaitingPayment: (int) ($data['awaiting_payment'] ?? 0),
            computedAt: CarbonImmutable::parse($data['computed_at'] ?? now()),
        );
    }

    public function forSegment(string $segment): int
    {
        return match ($segment) {
            'all' => $this->total,
            'active' => $this->active,
            'suspended' => $this->suspended,
            'expired' => $this->expired,
            'pending' => $this->pending,
            'none' => $this->none,
            'expiring_30' => $this->expiring30,
            'awaiting_payment' => $this->awaitingPayment,
            default => 0,
        };
    }
}
