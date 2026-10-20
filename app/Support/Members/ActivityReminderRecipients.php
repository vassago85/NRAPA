<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\ShootingActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared recipient query for the year-end "upload your activities" reminder.
 *
 * Mirrors the rules baked into {@see MemberCompliance::buildActivitiesRequirement()}
 * so an admin sending the reminder and the compliance resolver agree on who
 * still needs activities for the current year:
 *
 * - Current membership (status = active, not past expires_at — lifetime
 *   duration types never lapse). Matches `User::activeMembership()`.
 * - Membership type is NOT lifetime and NOT honorary (both are "always
 *   compliant" for activities — Phase 0 Q1 and Q6).
 * - User is NOT in their first join-year (exempt for the current year —
 *   Phase 0 Q3).
 * - Fewer than `SystemSetting::get('endorsement_min_activities', 2)`
 *   APPROVED shooting activities with `activity_date` inside
 *   1 January – 31 October of the current year.
 *
 * Only approved activities count — pending and rejected do not. This matches
 * the compliance window used in `ShootingActivity::scopeWithinActivityYear()`
 * and the `countFor()` closure inside the resolver.
 */
class ActivityReminderRecipients
{
    /**
     * Minimum approved activities required to be compliant for the year.
     * Pulled from the same SystemSetting the resolver uses.
     */
    public static function requiredCount(): int
    {
        return (int) SystemSetting::get('endorsement_min_activities', 2);
    }

    /**
     * Default to the current calendar year — the activity window is 1 Jan–31 Oct
     * of that year.
     */
    public static function defaultYear(): int
    {
        return (int) now()->year;
    }

    /**
     * Approved-activity count for a user in a specific year's Jan–Oct window.
     */
    public static function approvedCountFor(User $user, ?int $year = null): int
    {
        $year = $year ?? self::defaultYear();
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 10, 31)->endOfDay();

        return (int) ShootingActivity::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('activity_date', [$start, $end])
            ->count();
    }

    /**
     * Users who should get the reminder for the given year.
     *
     * Returns a Builder so callers can `->chunkById()` (bulk send) or
     * `->count()` (preview) or `->get()` (small tests). The query excludes
     * lifetime / honorary / join-year members at the SQL level, then filters
     * the activity count with a correlated subquery so a user with the
     * minimum number of approved activities drops out.
     */
    public static function query(?int $year = null): Builder
    {
        $year = $year ?? self::defaultYear();
        $required = self::requiredCount();
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 10, 31)->endOfDay();
        $joinYearStart = Carbon::create($year, 1, 1)->startOfDay();

        return User::query()
            // Must be a member role (not admin / owner / developer).
            ->where('users.role', User::ROLE_MEMBER)
            // Current, in-force membership whose type is NOT lifetime and NOT
            // honorary. Mirrors Membership::scopeCurrent() inlined so we can
            // filter the type in the same whereHas.
            ->whereHas('memberships', function (Builder $q) {
                $q->where('memberships.status', 'active')
                    ->where(function (Builder $q2) {
                        $q2->whereNull('memberships.expires_at')
                            ->orWhere('memberships.expires_at', '>=', now()->startOfDay())
                            ->orWhereHas('type', fn (Builder $t) => $t->where('duration_type', 'lifetime'));
                    })
                    ->whereHas('type', function (Builder $t) {
                        $t->where(function (Builder $tt) {
                            $tt->whereNull('duration_type')
                                ->orWhere('duration_type', '!=', 'lifetime');
                        })->where(function (Builder $tt) {
                            $tt->whereNull('is_honorary')
                                ->orWhere('is_honorary', false);
                        });
                    });
            })
            // Exclude join-year members. "In join-year" matches
            // User::firstJoinedYear() === $year, which is the earliest
            // applied_at (fallback created_at). So a non-join-year member is
            // one whose earliest applied_at is strictly before 1 Jan of the
            // activity year, OR whose created_at is strictly before 1 Jan of
            // the activity year when they have no applied_at at all.
            ->where(function (Builder $q) use ($joinYearStart) {
                $q->whereHas('memberships', function (Builder $m) use ($joinYearStart) {
                    $m->where('applied_at', '<', $joinYearStart);
                })->orWhere(function (Builder $q2) use ($joinYearStart) {
                    $q2->whereDoesntHave('memberships', fn (Builder $m) => $m->whereNotNull('applied_at'))
                        ->where('users.created_at', '<', $joinYearStart);
                });
            })
            // Still short of the minimum approved activities this year.
            ->whereRaw(
                '(SELECT COUNT(*) FROM shooting_activities sa'
                .' WHERE sa.user_id = users.id'
                .'   AND sa.status = ?'
                .'   AND sa.activity_date BETWEEN ? AND ?) < ?',
                ['approved', $start->toDateTimeString(), $end->toDateTimeString(), $required]
            );
    }

    /**
     * How many users would receive the reminder for the given year.
     */
    public static function count(?int $year = null): int
    {
        return self::query($year)->count();
    }
}
