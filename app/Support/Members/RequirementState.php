<?php

declare(strict_types=1);

namespace App\Support\Members;

/**
 * The five states a member compliance requirement can be in, per the Phase 1 brief.
 *
 * - `met`             the member has satisfied the requirement (e.g. 2/2 activities)
 * - `not_met`         the member has NOT satisfied the requirement and must act
 * - `not_applicable`  the requirement does not apply to this member type at all
 *                     (e.g. lifetime members and activity quotas)
 * - `exempt`          the requirement normally applies but has been waived for a reason
 *                     (e.g. join-year members get an activity exemption)
 * - `pending`         the member has submitted something and is waiting on NRAPA
 *                     (e.g. ID doc uploaded, awaiting admin verification)
 *
 * Brief: "not_applicable and exempt are never displayed as 'complete', '0/0' or a tick.
 *         They show their reason."
 */
enum RequirementState: string
{
    case Met = 'met';
    case NotMet = 'not_met';
    case NotApplicable = 'not_applicable';
    case Exempt = 'exempt';
    case Pending = 'pending';

    /**
     * Whether this state counts as "the member has nothing to do right now".
     *
     * Met, NotApplicable and Exempt all count as satisfied.
     * NotMet and Pending do not — the first requires member action, the second
     * requires admin action (so the member is told "waiting on NRAPA" rather
     * than being marked complete).
     */
    public function isSatisfied(): bool
    {
        return match ($this) {
            self::Met, self::NotApplicable, self::Exempt => true,
            self::NotMet, self::Pending => false,
        };
    }

    /**
     * Whether this state blocks the member — i.e. they need to act.
     */
    public function isBlocking(): bool
    {
        return $this === self::NotMet;
    }

    /**
     * Whether a ratio (e.g. "0 / 2") or a success tick may be rendered for this state.
     *
     * Explicitly false for NotApplicable and Exempt per the Phase 1 brief:
     *   "not_applicable and exempt are never displayed as 'complete', '0/0' or a tick."
     */
    public function allowsRatioOrTick(): bool
    {
        return match ($this) {
            self::Met, self::NotMet => true,
            self::NotApplicable, self::Exempt, self::Pending => false,
        };
    }
}
