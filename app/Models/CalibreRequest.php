<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibreRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'ignition_type',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'calibre_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user who submitted the request.
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
     * Get the calibre created from this request.
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class);
    }

    // ===== Scopes =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // ===== Accessors =====

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => ['color' => 'amber', 'text' => 'Pending'],
            self::STATUS_APPROVED => ['color' => 'green', 'text' => 'Approved'],
            self::STATUS_REJECTED => ['color' => 'red', 'text' => 'Rejected'],
            default => ['color' => 'zinc', 'text' => ucfirst($this->status)],
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return Calibre::getCategoryOptions()[$this->category] ?? ucfirst($this->category);
    }

    public function getIgnitionTypeLabelAttribute(): string
    {
        return Calibre::getIgnitionTypeOptions()[$this->ignition_type] ?? ucfirst($this->ignition_type);
    }
}
