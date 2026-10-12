<?php

declare(strict_types=1);

namespace App\Support\Members;

/**
 * A single member compliance requirement, as returned by the resolver.
 *
 * Shape per the Phase 1 brief: "Each requirement carries key, label, state and reason."
 *
 * `meta` is an open-ended associative array used by the dashboard / endorsements
 * pages to render context (counts, deadlines, document names, etc.). It is NOT
 * part of the brief's shape — views may read it but must not depend on specific
 * keys existing. When in doubt, display `reason` instead.
 */
final readonly class Requirement
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $key,
        public string $label,
        public RequirementState $state,
        public string $reason,
        public array $meta = [],
    ) {}

    public function isSatisfied(): bool
    {
        return $this->state->isSatisfied();
    }

    public function isBlocking(): bool
    {
        return $this->state->isBlocking();
    }

    public function isPending(): bool
    {
        return $this->state === RequirementState::Pending;
    }

    public function isNotApplicable(): bool
    {
        return $this->state === RequirementState::NotApplicable;
    }

    public function isExempt(): bool
    {
        return $this->state === RequirementState::Exempt;
    }
}
