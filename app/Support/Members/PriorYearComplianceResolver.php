<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\Membership;
use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;

/**
 * Decides whether a dedicated renewal is "prior-year compliant" so the
 * admin can approve it and the new Dedicated Status Certificate can be
 * issued.
 *
 * Compliance can come from three sources, in priority order:
 *   1. Admin manual verification stamped on the renewal row
 *      (`prior_year_compliance_verified_at`).
 *   2. First-cycle auto-pass: the previous membership was imported
 *      (`source = 'import'`), so the imported record may not include the
 *      prior-year 2/2 even though the member actually completed it.
 *   3. Real activity count: the member has at least the required number of
 *      approved shooting activities in the previous activity year
 *      (1 Jan to the configured deadline).
 *
 * The DTO returned by {@see self::for()} tells the UI exactly which of these
 * applied (so the admin sees "imported first cycle" rather than a bare tick).
 */
final class PriorYearComplianceResolver
{
    public static function for(Membership $renewal): PriorYearComplianceStatus
    {
        $renewal->loadMissing(['type', 'previousMembership', 'user']);

        $type = $renewal->type;
        $previous = $renewal->previousMembership;

        // Only renewals of dedicated-status-capable types carry the rule.
        if (! $type || ! $type->allows_dedicated_status || ! $renewal->previous_membership_id) {
            return PriorYearComplianceStatus::notApplicable();
        }

        $required = (int) SystemSetting::get('endorsement_min_activities', 2);
        $priorYear = self::priorActivityYearFor($renewal);
        $priorCount = $renewal->user_id
            ? self::approvedActivityCount($renewal->user_id, $priorYear)
            : 0;

        $isManuallyVerified = $renewal->prior_year_compliance_verified_at !== null;
        $isImportedFirstCycle = ($previous?->source === 'import') && ! $isManuallyVerified;
        $isActivityCompliant = $priorCount >= $required;

        return new PriorYearComplianceStatus(
            applicable: true,
            priorYear: $priorYear,
            required: $required,
            priorCount: $priorCount,
            isManuallyVerified: $isManuallyVerified,
            isImportedFirstCycle: $isImportedFirstCycle,
            isActivityCompliant: $isActivityCompliant,
            verifiedAt: $renewal->prior_year_compliance_verified_at,
            verifiedBy: $renewal->prior_year_compliance_verified_by
                ? User::find($renewal->prior_year_compliance_verified_by)
                : null,
            verifiedNote: $renewal->prior_year_compliance_verified_note,
        );
    }

    /**
     * Stamp the auto-verification on a renewal whose previous membership was
     * imported. Idempotent: a second call is a no-op. Returns true when the
     * stamp was applied by this call.
     */
    public static function autoStampForImportedPredecessor(Membership $renewal): bool
    {
        $status = self::for($renewal);
        if (! $status->applicable || ! $status->isImportedFirstCycle) {
            return false;
        }

        $renewal->forceFill([
            'prior_year_compliance_verified_at' => now(),
            'prior_year_compliance_verified_note' => 'Auto-verified: first renewal after bulk import — prior-year 2/2 taken on trust.',
        ])->save();

        return true;
    }

    /**
     * Admin manually ticks "prior year verified" from the approval screen or
     * the member show page. Writes an audit log entry at the call site.
     */
    public static function markVerified(Membership $renewal, User $admin, ?string $note = null): void
    {
        $renewal->forceFill([
            'prior_year_compliance_verified_at' => now(),
            'prior_year_compliance_verified_by' => $admin->id,
            'prior_year_compliance_verified_note' => $note,
        ])->save();
    }

    /**
     * Admin unticks the manual verification.
     */
    public static function clearVerification(Membership $renewal): void
    {
        $renewal->forceFill([
            'prior_year_compliance_verified_at' => null,
            'prior_year_compliance_verified_by' => null,
            'prior_year_compliance_verified_note' => null,
        ])->save();
    }

    /**
     * The activity year whose 2/2 the renewal is being checked against.
     *
     * Rule: for a renewal applied for in year Y, prove the year Y-1 record.
     * A renewal applied for in late October 2026 therefore needs the member's
     * 2025 activities on file; one applied for in January 2027 still needs 2026.
     */
    private static function priorActivityYearFor(Membership $renewal): int
    {
        $applied = $renewal->applied_at ?? $renewal->created_at ?? now();
        return (int) Carbon::parse($applied)->year - 1;
    }

    private static function approvedActivityCount(int $userId, int $year): int
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = ActivityDeadline::endOfDay($year);

        return (int) ShootingActivity::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('activity_date', [$start, $end])
            ->count();
    }
}
