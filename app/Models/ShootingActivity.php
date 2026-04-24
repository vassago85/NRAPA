<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'track',
        'event_category_id', // Kept for historical data
        'event_type_id', // Kept for historical data
        'activity_date',
        'activity_year_month_start',
        'description',
        'firearm_type_id',
        'calibre_id',
        'user_firearm_id',
        'load_data_id',
        'rounds_fired',
        'location',
        'country_id',
        'country_name',
        'province_id',
        'province_name',
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
            'rounds_fired' => 'integer',
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

            // Auto-set track from activity type if not set
            if (! $activity->track && $activity->activity_type_id) {
                $activityType = ActivityType::find($activity->activity_type_id);
                if ($activityType && $activityType->track) {
                    $activity->track = $activityType->track;
                }
            }
        });

        static::updating(function (ShootingActivity $activity) {
            // Auto-update track from activity type if activity type changed
            if ($activity->isDirty('activity_type_id') && ! $activity->track) {
                $activityType = ActivityType::find($activity->activity_type_id);
                if ($activityType && $activityType->track) {
                    $activity->track = $activityType->track;
                }
            }
        });

        static::saved(function (ShootingActivity $activity) {
            if ($activity->wasChanged('status')) {
                \Illuminate\Support\Facades\Cache::forget('sidebar_pending_total');
                \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
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
     * Get the activity tags for this activity.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ActivityTag::class, 'activity_tag_shooting_activity')
            ->withTimestamps();
    }

    /**
     * Get the event category (legacy - kept for historical data).
     *
     * @deprecated Use activityType() instead
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the event type (legacy - kept for historical data).
     *
     * @deprecated Use tags() instead
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

    // Legacy calibre relationship removed - ShootingActivity uses calibre_id for legacy data only

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
     * Approve the activity and auto-verify its linked evidence documents.
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $this->load(['evidenceDocument', 'additionalDocument']);

        if ($this->evidenceDocument && $this->evidenceDocument->status === 'pending') {
            $this->evidenceDocument->verify($admin);
        }
        if ($this->additionalDocument && $this->additionalDocument->status === 'pending') {
            $this->additionalDocument->verify($admin);
        }

        $this->notifyApproval();
    }

    /**
     * Reject the activity and auto-reject its linked evidence documents.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $this->load(['evidenceDocument', 'additionalDocument']);

        if ($this->evidenceDocument && $this->evidenceDocument->status === 'pending') {
            $this->evidenceDocument->reject($admin, "Activity rejected: {$reason}");
        }
        if ($this->additionalDocument && $this->additionalDocument->status === 'pending') {
            $this->additionalDocument->reject($admin, "Activity rejected: {$reason}");
        }

        $this->notifyRejection($reason);
    }

    protected function notifyApproval(): void
    {
        $user = $this->user;
        if (! $user?->email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\ActivityApproved($this)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send activity approved email', [
                'activity_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyRejection(string $reason): void
    {
        $user = $this->user;
        if (! $user) {
            return;
        }

        if ($user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new \App\Mail\ActivityRejected($this, $reason)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send activity rejected email', [
                    'activity_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $activityTypeName = $this->activityType?->name ?? 'Activity';
        $activityDate = $this->activity_date->format('d M Y');
        $title = "Activity Rejected: {$activityTypeName}";
        $message = "Your activity submission for {$activityDate} ({$activityTypeName}) has been rejected.\n\nReason: {$reason}\n\nPlease review and resubmit if needed.";

        if (class_exists(\App\Services\NtfyService::class)) {
            $ntfyService = app(\App\Services\NtfyService::class);
            $ntfyService->notifyUser(
                $user,
                'activity_rejected',
                $title,
                $message,
                'high',
                [
                    'activity_id' => $this->id,
                    'activity_type' => $activityTypeName,
                    'activity_date' => $activityDate,
                    'rejection_reason' => $reason,
                ]
            );
        }
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
     * Scope to activities within the current activity year.
     * Activity period is fixed: 1 January to 31 October (deadline 31 October).
     */
    public function scopeWithinActivityYear($query, User $user, ?int $year = null)
    {
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 10, 31)->endOfDay();

        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    // ===== Helpers =====

    /**
     * Get the activity period boundaries.
     * Fixed period: 1 January to 31 October (submissions due by 31 October).
     */
    public static function getActivityPeriod(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 10, 31)->endOfDay();
        $deadline = Carbon::create($year, 10, 31);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'deadline' => $deadline,
            'label' => $startDate->format('d M Y').' - '.$endDate->format('d M Y'),
            'deadline_label' => 'Submissions due by '.$deadline->format('d M Y'),
        ];
    }

    /**
     * Canonical compliance summary for a user.
     *
     * NRAPA rule: the previous activity year's approvals qualify the member
     * for the current year. While the current window is open (Jan 1 - Oct 31)
     * the activities being banked count toward NEXT year's compliance.
     *
     * Returns totals for:
     *   - qualifying_year: the year whose activities qualify the member RIGHT NOW
     *     (previous year by default, but if current year already has enough it can
     *     be used instead so the UI shows "compliant").
     *   - banking_year: the current activity year (what's being built for next year).
     */
    public static function complianceSummary(User $user, int $required = 2): array
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $countForYear = function (int $year) use ($user) {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 10, 31)->endOfDay();

            $counts = self::where('user_id', $user->id)
                ->approved()
                ->whereBetween('activity_date', [$start, $end])
                ->selectRaw("count(*) as total, sum(track = 'hunting') as hunting, sum(track = 'sport') as sport")
                ->first();

            return [
                'total' => (int) $counts->total,
                'hunting' => (int) $counts->hunting,
                'sport' => (int) $counts->sport,
                'start' => $start,
                'end' => $end,
                'label' => $start->format('d M Y').' - '.$end->format('d M Y'),
                'year' => $year,
            ];
        };

        $current = $countForYear($currentYear);
        $previous = $countForYear($prevYear);

        $qualifies = $previous['total'] >= $required || $current['total'] >= $required;
        $nextYearQualified = $current['total'] >= $required;

        return [
            'required' => $required,
            'qualifying_year' => $previous,
            'banking_year' => $current,
            'is_compliant_now' => $qualifies,
            'is_compliant_next_year' => $nextYearQualified,
            'explainer' => $qualifies
                ? "Compliant for {$currentYear} on the strength of {$prevYear}'s activities."
                : "Not compliant for {$currentYear}: only {$previous['total']} approved activities in {$prevYear} (need {$required}).",
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

    /**
     * Get calibre name from user firearm if available, otherwise null.
     */
    public function getCalibreNameAttribute(): ?string
    {
        if ($this->userFirearm && $this->userFirearm->calibre_display) {
            return $this->userFirearm->calibre_display;
        }

        return null;
    }
}
