<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShootingActivity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'activity_type_id',
        'event_category_id',
        'event_type_id',
        'activity_date',
        'activity_year_month_start',
        'description',
        'firearm_type_id',
        'calibre_id',
        'user_firearm_id',
        'load_data_id',
        'location',
        'country_id',
        'province_id',
        'closest_town_city',
        'evidence_document_id',
        'additional_document_id',
        'status',
        'rejection_reason',
        'verified_at',
        'verified_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ShootingActivity $activity) {
            if (empty($activity->uuid)) {
                $activity->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ===== Relationships =====

    /**
     * Get the user that owns the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity type.
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the event category.
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the event type.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the firearm type.
     */
    public function firearmType(): BelongsTo
    {
        return $this->belongsTo(FirearmType::class);
    }

    /**
     * Get the calibre.
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class);
    }

    /**
     * Get the user's firearm (from their armoury).
     */
    public function userFirearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class);
    }

    /**
     * Get the load data used for this activity.
     */
    public function loadData(): BelongsTo
    {
        return $this->belongsTo(LoadData::class);
    }

    /**
     * Get the country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the province.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the evidence document.
     */
    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(MemberDocument::class, 'evidence_document_id');
    }

    /**
     * Get the additional document.
     */
    public function additionalDocument(): BelongsTo
    {
        return $this->belongsTo(MemberDocument::class, 'additional_document_id');
    }

    /**
     * Get the admin who verified the activity.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ===== Status Checks =====

    /**
     * Check if the activity is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the activity is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the activity is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the activity is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    // ===== Actions =====

    /**
     * Approve the activity.
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);
    }

    /**
     * Reject the activity.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);
    }

    /**
     * Verify the activity (legacy method).
     */
    public function verify(User $admin): void
    {
        $this->approve($admin);
    }

    // ===== Scopes =====

    /**
     * Scope to only pending activities.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to only approved activities.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to only rejected activities.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to only verified activities.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope to only unverified activities.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Scope to activities within a date range.
     */
    public function scopeWithinPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    /**
     * Scope to activities within a user's activity year.
     */
    public function scopeWithinActivityYear($query, User $user, ?int $year = null)
    {
        $startMonth = $user->activity_period_start_month ?? 10; // Default October
        $year = $year ?? now()->year;

        // If we're before the start month, use previous year as the start
        if (now()->month < $startMonth) {
            $year = $year - 1;
        }

        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addYear()->subDay()->endOfDay();

        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    // ===== Helpers =====

    /**
     * Get the activity period boundaries for a user.
     */
    public static function getActivityPeriod(User $user, ?int $year = null): array
    {
        $startMonth = $user->activity_period_start_month ?? 10; // Default October
        $year = $year ?? now()->year;

        // If we're before the start month, use previous year as the start
        if (now()->month < $startMonth) {
            $year = $year - 1;
        }

        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->addYear()->subDay()->endOfDay();

        return [
            'start' => $startDate,
            'end' => $endDate,
            'label' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
        ];
    }

    /**
     * Get the full location string.
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->location,
            $this->closest_town_city,
            $this->province?->name,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }
}
