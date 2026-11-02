<?php

declare(strict_types=1);

namespace App\Support\Members;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the fixed activity-year deadline (SAPS cut-off).
 *
 * Everything that used to say `Carbon::create($year, 10, 31)` goes through
 * here instead so {@see config('compliance.deadline')} is the only place the
 * month / day live. If NRAPA ever has to shift the cut-off (e.g. to Nov 30)
 * one env var change does it — no code hunt required.
 */
final class ActivityDeadline
{
    public static function month(): int
    {
        return (int) config('compliance.deadline.month', 10);
    }

    public static function day(): int
    {
        return (int) config('compliance.deadline.day', 31);
    }

    /**
     * The deadline date for the given year, at 00:00.
     */
    public static function date(int $year): Carbon
    {
        return Carbon::create($year, self::month(), self::day())->startOfDay();
    }

    /**
     * The deadline date for the given year, at 23:59:59 — convenient as the
     * upper bound of a `whereBetween(activity_date, [start, end])` clause.
     */
    public static function endOfDay(int $year): Carbon
    {
        return Carbon::create($year, self::month(), self::day())->endOfDay();
    }

    /**
     * Has the deadline for the given year (defaults to the current year)
     * already elapsed? Used to decide whether a submission is "late".
     */
    public static function hasPassed(?int $year = null, ?CarbonInterface $now = null): bool
    {
        $now ??= Carbon::now();
        $year ??= (int) $now->year;

        return $now->isAfter(self::endOfDay($year));
    }

    /**
     * Human-friendly label e.g. "31 October 2026".
     */
    public static function label(int $year): string
    {
        return self::date($year)->format('d F Y');
    }

    /**
     * Raw SQL predicate that evaluates to true for a shooting_activities row
     * whose `created_at` is after the deadline for the activity_date's year.
     *
     * Dispatches on the current connection driver so the same filter works
     * on MySQL (production) and SQLite (tests). The deadline month/day are
     * sourced from config and interpolated directly — safe because both are
     * typed as `int` and never originate from user input.
     *
     * Pass this to `->whereRaw(ActivityDeadline::lateSqlPredicate())`.
     */
    public static function lateSqlPredicate(?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();
        $suffix = sprintf('-%02d-%02d 23:59:59', self::month(), self::day());

        return match ($driver) {
            'sqlite' => "shooting_activities.created_at > (strftime('%Y', shooting_activities.activity_date) || '{$suffix}')",
            // MySQL, MariaDB, and anything else SQL-standard enough to use CONCAT + YEAR().
            default => "shooting_activities.created_at > CONCAT(YEAR(shooting_activities.activity_date), '{$suffix}')",
        };
    }
}
