<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConfigurationChangeRequest extends Model
{
    protected $fillable = [
        'uuid',
        'requested_by',
        'configuration_type',
        'target_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Configuration types
     */
    public const TYPE_MEMBERSHIP_TYPE = 'membership_type';
    public const TYPE_DOCUMENT_TYPE = 'document_type';
    public const TYPE_DOCUMENT_REQUIREMENTS = 'document_requirements';

    /**
     * Actions
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    /**
     * Statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (ConfigurationChangeRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
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
     * Get the user who requested the change.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who reviewed the change.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get human-readable configuration type.
     */
    public function getConfigurationTypeLabelAttribute(): string
    {
        return match ($this->configuration_type) {
            self::TYPE_MEMBERSHIP_TYPE => 'Membership Type',
            self::TYPE_DOCUMENT_TYPE => 'Document Type',
            self::TYPE_DOCUMENT_REQUIREMENTS => 'Document Requirements',
            default => ucfirst(str_replace('_', ' ', $this->configuration_type)),
        };
    }

    /**
     * Get human-readable action.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Create',
            self::ACTION_UPDATE => 'Update',
            self::ACTION_DELETE => 'Delete',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get the target model name for display.
     */
    public function getTargetNameAttribute(): ?string
    {
        if ($this->action === self::ACTION_CREATE) {
            return $this->new_values['name'] ?? 'New Record';
        }

        return match ($this->configuration_type) {
            self::TYPE_MEMBERSHIP_TYPE => MembershipType::find($this->target_id)?->name,
            self::TYPE_DOCUMENT_TYPE => DocumentType::find($this->target_id)?->name,
            default => null,
        };
    }

    /**
     * Approve the change request.
     */
    public function approve(User $reviewer, ?string $notes = null): bool
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return $this->applyChange();
    }

    /**
     * Reject the change request.
     */
    public function reject(User $reviewer, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Apply the approved change to the database.
     */
    protected function applyChange(): bool
    {
        return match ($this->configuration_type) {
            self::TYPE_MEMBERSHIP_TYPE => $this->applyMembershipTypeChange(),
            self::TYPE_DOCUMENT_TYPE => $this->applyDocumentTypeChange(),
            self::TYPE_DOCUMENT_REQUIREMENTS => $this->applyDocumentRequirementsChange(),
            default => false,
        };
    }

    /**
     * Apply membership type change.
     */
    protected function applyMembershipTypeChange(): bool
    {
        return match ($this->action) {
            self::ACTION_CREATE => (bool) MembershipType::create($this->new_values),
            self::ACTION_UPDATE => (bool) MembershipType::find($this->target_id)?->update($this->new_values),
            self::ACTION_DELETE => (bool) MembershipType::find($this->target_id)?->delete(),
            default => false,
        };
    }

    /**
     * Apply document type change.
     */
    protected function applyDocumentTypeChange(): bool
    {
        return match ($this->action) {
            self::ACTION_CREATE => (bool) DocumentType::create($this->new_values),
            self::ACTION_UPDATE => (bool) DocumentType::find($this->target_id)?->update($this->new_values),
            self::ACTION_DELETE => (bool) DocumentType::find($this->target_id)?->delete(),
            default => false,
        };
    }

    /**
     * Apply document requirements change.
     */
    protected function applyDocumentRequirementsChange(): bool
    {
        $membershipType = MembershipType::find($this->target_id);
        if (! $membershipType) {
            return false;
        }

        // Sync the document types
        $documentTypes = collect($this->new_values['document_types'] ?? [])
            ->mapWithKeys(fn ($doc) => [$doc['id'] => ['is_required' => $doc['is_required'] ?? true]]);

        $membershipType->documentTypes()->sync($documentTypes);

        return true;
    }
}
