<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DedicatedStatusApplication extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'membership_id',
        'dedicated_type',
        'status',
        'applied_at',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
        'valid_from',
        'valid_until',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DedicatedStatusApplication $application) {
            if (empty($application->uuid)) {
                $application->uuid = (string) Str::uuid();
            }
            if (empty($application->applied_at)) {
                $application->applied_at = now();
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

    /**
     * Get the user that owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership associated with the application.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * Get the admin who reviewed the application.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the admin who approved the application.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ===== Dedicated Type =====

    /**
     * Get the dedicated type display name.
     */
    public function getDedicatedTypeDisplayAttribute(): string
    {
        return match($this->dedicated_type) {
            'hunter' => 'Dedicated Hunter',
            'sport_shooter' => 'Dedicated Sport Shooter',
            default => 'Unknown',
        };
    }

    /**
     * Check if the user has passed the required knowledge test for this dedicated type.
     */
    public function hasPassedRequiredTest(): bool
    {
        return KnowledgeTestAttempt::where('user_id', $this->user_id)
            ->whereHas('knowledgeTest', function ($q) {
                $q->forDedicatedType($this->dedicated_type);
            })
            ->where('passed', true)
            ->exists();
    }

    // ===== Status Checks =====

    /**
     * Check if the application is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['applied', 'under_review']);
    }

    /**
     * Check if the application is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the dedicated status is currently valid.
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    // ===== Actions =====

    /**
     * Mark the application as under review.
     */
    public function markUnderReview(User $admin): void
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);
    }

    /**
     * Approve the application.
     */
    public function approve(User $admin, ?string $validFrom = null, ?string $validUntil = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'valid_from' => $validFrom ?? now()->toDateString(),
            'valid_until' => $validUntil,
        ]);
    }

    /**
     * Reject the application.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    // ===== Scopes =====

    /**
     * Scope to only pending applications.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['applied', 'under_review']);
    }

    /**
     * Scope to only approved applications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to only currently valid dedicated status.
     */
    public function scopeCurrentlyValid($query)
    {
        return $query->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }
}
