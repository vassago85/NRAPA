<?php

namespace App\Models;

use App\Concerns\SyncsApproval;
use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Services\Approvals\ApprovalSync;
use App\Support\Approvals\Checks\ActivityWithinMembershipPeriod;
use App\Support\Approvals\Checks\MembershipActive;
use App\Support\Approvals\Checks\NoDisciplinaryHold;
use App\Support\Approvals\EvidenceBlock;
use App\Support\Members\ActivityDeadline;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ShootingActivity extends Model implements Approvable
{
    use SyncsApproval;

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
        'is_late_approved',
        'late_decided_at',
        'late_decided_by',
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
            'is_late_approved' => 'boolean',
            'late_decided_at' => 'datetime',
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
            }

            if ($activity->wasRecentlyCreated || $activity->wasChanged(['evidence_document_id', 'additional_document_id'])) {
                $sync = app(ApprovalSync::class);
                foreach (array_filter([$activity->evidence_document_id, $activity->additional_document_id]) as $documentId) {
                    if ($document = MemberDocument::find($documentId)) {
                        $sync->discard($document);
                    }
                }
            }
        });
    }

    // ===== Approvable =====

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Activity;
    }

    public function approvalStateFromModel(): ?ApprovalState
    {
        return $this->status === 'pending' && $this->user_id ? ApprovalState::WaitingOnAdmin : null;
    }

    public function approvalOutcomeFromModel(): ApprovalState
    {
        return match ($this->status) {
            'approved' => ApprovalState::Approved,
            'rejected' => ApprovalState::Declined,
            default => ApprovalState::Withdrawn,
        };
    }

    public function approvalSubmittedAt(): ?CarbonInterface
    {
        return $this->created_at;
    }

    public function summaryLine(): string
    {
        return ($this->user?->name ?? 'Unknown member').' — '.($this->activityType?->name ?? 'Activity').' on '.$this->activity_date?->format('d M Y');
    }

    public function detailLine(): string
    {
        return implode(' · ', array_filter([
            $this->track ? ucfirst($this->track) : null,
            $this->calibre_name,
            $this->closest_town_city ?: $this->location,
            $this->evidence_document_id ? 'evidence attached' : 'no evidence',
        ]));
    }

    public function detailWarning(): ?string
    {
        return $this->activity_date && ! ActivityWithinMembershipPeriod::covered($this)
            ? 'Falls outside the membership period'
            : null;
    }

    public function evidence(): EvidenceBlock
    {
        $this->loadMissing(['activityType', 'evidenceDocument', 'additionalDocument', 'userFirearm', 'firearmCalibre', 'country', 'province']);

        $recent = self::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->latest('activity_date')
            ->with('activityType')
            ->limit(5)
            ->get();

        return new EvidenceBlock(
            view: 'components.admin.evidence.activity',
            title: 'Logged activity',
            data: [
                'activity' => $this,
                'withinPeriod' => $this->activity_date ? ActivityWithinMembershipPeriod::covered($this) : false,
                'recent' => $recent,
                'compliance' => $this->user ? self::complianceSummary($this->user) : null,
            ],
        );
    }

    public function preflightChecks(): array
    {
        // Activity-outside-membership-period is NOT a preflight check: a member
        // might log a historical range day during a lapse, and an admin needs
        // to be able to approve it inline from the queue so dedicated-status
        // compliance stays truthful. The amber warning on the row and the date
        // tint in the evidence block already flag it as context.
        return [
            MembershipActive::check($this->user),
            NoDisciplinaryHold::check($this->user),
        ];
    }

    public function canBeApprovedInline(): bool
    {
        return true;
    }

    public function approvalConsequence(): string
    {
        return 'Approving counts this activity towards dedicated status, verifies its evidence, emails the member, and writes an audit entry against your name.';
    }

    /**
     * Per-request override set by the admin UI when it needs to tell this
     * approval whether a LATE submission should count toward the member's
     * 2/2. The default (true) matches the historical behaviour: approving
     * means "yes, this counts".
     */
    public static ?bool $nextLateApprovalCountsToward = null;

    public function approveFromQueue(User $actor, ?string $note = null): void
    {
        $counts = static::$nextLateApprovalCountsToward ?? true;
        static::$nextLateApprovalCountsToward = null;
        $this->approve($actor, $counts);
    }

    public function declineFromQueue(User $actor, string $reason): void
    {
        $this->reject($actor, $reason);
    }

    public function requestChangesFromQueue(User $actor, array $items, ?string $note = null): void
    {
        $this->reject($actor, trim(implode('; ', $items).($note ? " — {$note}" : '')));
    }

    public function notifiesMemberOnDecision(ApprovalDecision $decision): bool
    {
        return true;
    }

    public function supersedesApprovableOf(Model $older): bool
    {
        return $older instanceof self;
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

    /**
     * Get the calibre reference recorded directly on the activity
     * (used for manual submissions and armoury firearms without a calibre).
     */
    public function firearmCalibre(): BelongsTo
    {
        return $this->belongsTo(FirearmCalibre::class, 'calibre_id');
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
     * Approve the activity and auto-verify its linked evidence documents.
     *
     * For a late submission (submitted after the activity-year deadline),
     * `$countTowardCompliance = true` records the admin's explicit decision
     * that the activity counts toward the member's 2/2. Setting it to false
     * approves the activity for the record but excludes it from compliance
     * (same shape as a post-SAPS-lock approval). The flag is ignored for
     * non-late submissions — those count unconditionally.
     */
    public function approve(User $admin, bool $countTowardCompliance = true): void
    {
        $isLate = $this->is_late;

        $payload = [
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ];

        if ($isLate) {
            $payload['is_late_approved'] = $countTowardCompliance;
            $payload['late_decided_at'] = now();
            $payload['late_decided_by'] = $admin->id;
        }

        $this->update($payload);

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
        $isLate = $this->is_late;

        $payload = [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ];

        if ($isLate) {
            $payload['is_late_approved'] = false;
            $payload['late_decided_at'] = now();
            $payload['late_decided_by'] = $admin->id;
        }

        $this->update($payload);

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
     * Scope to activities within the current activity year.
     * Activity period is fixed: 1 January to the configured deadline
     * (see config/compliance.php — SAPS cut-off, default 31 October).
     */
    public function scopeWithinActivityYear($query, User $user, ?int $year = null)
    {
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = ActivityDeadline::endOfDay($year);

        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    // ===== Helpers =====

    /**
     * Get the activity period boundaries.
     * Fixed period: 1 January to the configured deadline (SAPS cut-off).
     */
    public static function getActivityPeriod(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = ActivityDeadline::endOfDay($year);
        $deadline = ActivityDeadline::date($year);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'deadline' => $deadline,
            'label' => $startDate->format('d M Y').' - '.$endDate->format('d M Y'),
            'deadline_label' => 'Submissions due by '.$deadline->format('d M Y'),
        ];
    }

    /**
     * Was this activity submitted after the deadline for its activity year?
     * A late submission still goes through normal approval, but does NOT
     * count toward dedicated-status compliance unless an admin explicitly
     * ticks `is_late_approved` on approval (see MemberCompliance).
     */
    public function getIsLateAttribute(): bool
    {
        if (! $this->activity_date || ! $this->created_at) {
            return false;
        }

        $deadline = ActivityDeadline::endOfDay((int) $this->activity_date->year);

        return $this->created_at->greaterThan($deadline);
    }

    /**
     * Canonical compliance summary for a user.
     *
     * @deprecated since Phase 1 — the compliance *decision* now comes from
     *             {@see \App\Support\Members\MemberCompliance}; this method is
     *             retained as a shim so admin / cert / API / PDF code that
     *             still consumes the legacy array shape keeps working while
     *             the admin brief is drafted.
     *
     * The hunting/sport breakdown is a reporting concern (admin members page
     * reads it) rather than a compliance input — we still compute it here from
     * the DB, but the `is_compliant_now` / `is_compliant_next_year` flags
     * reflect the resolver's answer so lifetime and honorary members are no
     * longer rendered as "0/2 not compliant".
     *
     * `$required` is retained for signature compatibility but ignored — the
     * resolver sources it from `SystemSetting::get('endorsement_min_activities', 2)`
     * per Paul's Q2 answer. No live caller overrides the default anyway.
     */
    public static function complianceSummary(User $user, int $required = 2): array
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $countForYear = function (int $year) use ($user) {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = ActivityDeadline::endOfDay($year);

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

        $result = \App\Support\Members\MemberCompliance::for($user);
        $activities = $result->requirement('activities');
        $meta = $activities->meta ?? [];

        $resolverRequired = (int) ($meta['required'] ?? $required);
        $qualifies = (bool) $activities?->isSatisfied();
        $nextYearQualified = $current['total'] >= $resolverRequired;
        $exempt = (bool) ($meta['is_join_year_exempt'] ?? false);
        $explainer = $activities?->reason ?? "You need at least {$resolverRequired} approved activities per year.";

        return [
            'required' => $resolverRequired,
            'qualifying_year' => $previous,
            'banking_year' => $current,
            'is_compliant_now' => $qualifies,
            'is_compliant_next_year' => $nextYearQualified,
            'is_join_year_exempt' => $exempt,
            'explainer' => $explainer,
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

        if ($this->calibre_id) {
            return $this->firearmCalibre?->name;
        }

        return null;
    }
}
