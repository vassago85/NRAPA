<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FirearmMotivationRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'applicant_name',
        'applicant_email',
        'applicant_id_number',
        'applicant_phone',
        'firearm_type',
        'purpose',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
        'issued_at',
        'issued_by',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'applicant_id_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FirearmMotivationRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
            if (empty($request->submitted_at)) {
                $request->submitted_at = now();
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
     * Get the user that owns the request (if member).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the admin who approved the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who issued the hard copy.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the documents attached to the request.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(FirearmMotivationDocument::class, 'request_id');
    }

    // ===== Status Checks =====

    /**
     * Check if the request is from a member.
     */
    public function isMemberRequest(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request has been issued.
     */
    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    // ===== Actions =====

    /**
     * Mark the request as under review.
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
     * Approve the request.
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark the request as issued (hard copy delivered).
     */
    public function markIssued(User $admin): void
    {
        $this->update([
            'status' => 'issued',
            'issued_at' => now(),
            'issued_by' => $admin->id,
        ]);
    }

    // ===== Scopes =====

    /**
     * Scope to only pending requests.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * Scope to only approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to only member requests.
     */
    public function scopeMembers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope to only non-member requests.
     */
    public function scopeNonMembers($query)
    {
        return $query->whereNull('user_id');
    }
}
