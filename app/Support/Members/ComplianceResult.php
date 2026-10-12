<?php

declare(strict_types=1);

namespace App\Support\Members;

/**
 * The DTO returned by {@see MemberCompliance::for()}.
 *
 * Shape per the Phase 1 brief: "a readonly DTO: a list of requirements and an
 * overall result".
 *
 * The `dedicated_*` flags are kept here rather than as a requirement because
 * they drive *display* (whether the Dedicated Status surface renders at all),
 * not blocking behaviour. They encode Paul's Q4 answer under the hybrid
 * interpretation (4b):
 *
 * - `isDedicatedEligible`     AND of `allows_dedicated_status` and
 *                             `dedicated_type IN (hunter, sport, both)` —
 *                             governs whether endorsement letters are actionable.
 * - `showsDedicatedSurfaces`  OR of the same two attributes — governs whether
 *                             the Dedicated Status tab/card is visible at all.
 *                             Mirrors today's behaviour so the `transfer` type
 *                             (allows_dedicated_status=true, dedicated_type=null)
 *                             keeps its tab.
 */
final readonly class ComplianceResult
{
    /**
     * @param array<string, Requirement> $requirements  keyed by requirement key
     */
    public function __construct(
        public array $requirements,
        public bool $hasActiveMembership,
        public bool $isLifetime,
        public bool $isHonorary,
        public bool $isDedicatedEligible,
        public bool $showsDedicatedSurfaces,
    ) {}

    /**
     * Fetch a requirement by key, or null if the resolver did not produce one.
     */
    public function requirement(string $key): ?Requirement
    {
        return $this->requirements[$key] ?? null;
    }

    /**
     * Shortcut: is this requirement satisfied (met / not_applicable / exempt)?
     *
     * Returns false if the requirement is missing entirely — callers that need
     * to distinguish "missing" from "not met" should use requirement()->state.
     */
    public function isSatisfied(string $key): bool
    {
        return (bool) $this->requirement($key)?->isSatisfied();
    }

    /**
     * Every requirement is satisfied (met / not_applicable / exempt).
     * Pending requirements do NOT count as satisfied — those are "waiting on NRAPA".
     */
    public function isCompliant(): bool
    {
        if (! $this->hasActiveMembership) {
            return false;
        }
        foreach ($this->requirements as $req) {
            if (! $req->isSatisfied()) {
                return false;
            }
        }
        return true;
    }

    /**
     * At least one requirement is blocking (NotMet).
     */
    public function hasBlocking(): bool
    {
        foreach ($this->requirements as $req) {
            if ($req->isBlocking()) {
                return true;
            }
        }
        return false;
    }

    /**
     * At least one requirement is pending admin review.
     */
    public function hasPending(): bool
    {
        foreach ($this->requirements as $req) {
            if ($req->isPending()) {
                return true;
            }
        }
        return false;
    }

    /**
     * The requirements that are blocking (member must act), in insertion order.
     *
     * @return array<string, Requirement>
     */
    public function blocking(): array
    {
        return array_filter(
            $this->requirements,
            fn (Requirement $r) => $r->isBlocking(),
        );
    }

    /**
     * Overall summary string. Phase 1 keeps this intentionally simple; Phase 2
     * (the next-step card) will reach into the requirement list directly and
     * does not need another aggregate.
     *
     * - `no_membership`     the member has no active membership
     * - `compliant`         everything satisfied
     * - `awaiting_review`   nothing blocking, but at least one requirement
     *                       is pending admin verification
     * - `incomplete`        at least one requirement is NotMet
     */
    public function overall(): string
    {
        if (! $this->hasActiveMembership) {
            return 'no_membership';
        }
        if ($this->hasBlocking()) {
            return 'incomplete';
        }
        if ($this->hasPending()) {
            return 'awaiting_review';
        }
        return 'compliant';
    }
}
