<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marks that a given activity/compliance year has been submitted to SAPS.
 *
 * Once an active lock exists for a year:
 * - Activities whose verified_at is after submitted_at do not count toward
 *   that year's SAPS compliance total (see MemberCompliance + the CSV export).
 * - Members and admins can still submit / approve / reject — the lock only
 *   affects reporting. UI banners communicate the "post-SAPS informational"
 *   nature to both audiences.
 *
 * Unlock is a soft delete so the audit trail stays intact.
 */
class SapsYearSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'year',
        'submitted_at',
        'submitted_by',
        'note',
        'locked_count',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'submitted_at' => 'datetime',
            'locked_count' => 'integer',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Scope to a single compliance year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /**
     * Return the active (not soft-deleted) lock for a year, or null.
     *
     * Memoised per request to keep compliance count / blade banner lookups
     * from re-querying for every row.
     *
     * @var array<int, self|null>
     */
    protected static array $cache = [];

    public static function activeLockFor(int $year): ?self
    {
        if (! array_key_exists($year, self::$cache)) {
            self::$cache[$year] = self::query()
                ->forYear($year)
                ->orderByDesc('submitted_at')
                ->first();
        }

        return self::$cache[$year];
    }

    public static function isLocked(int $year): bool
    {
        return self::activeLockFor($year) !== null;
    }

    /**
     * Clear the per-request cache. Call after creating or restoring a lock.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
        static::restored(fn () => self::clearCache());
    }
}
