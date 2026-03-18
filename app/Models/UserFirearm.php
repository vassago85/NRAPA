<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UserFirearm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'firearm_type_id', // Legacy FK, kept for backwards compatibility
        'firearm_type',    // SAPS 271 canonical: rifle|shotgun|handgun|hand_machine_carbine|combination
        'action',          // semi_automatic|automatic|bolt_action|pump_action|lever_action|manual|other
        'other_action_text', // When action = 'other'
        'calibre_code',    // SAPS calibre code
        'make',
        'model',
        'serial_number',  // Legacy, kept for backwards compatibility
        'nickname',
        'barrel_length',
        'barrel_twist',
        'barrel_profile',
        'stock_type',
        'stock_make',
        'scope_make',
        'scope_model',
        'scope_magnification',
        'license_number',
        'license_issue_date',
        'license_expiry_date',
        'license_type',
        'license_status',
        'expiry_notification_sent_90',
        'expiry_notification_sent_60',
        'expiry_notification_sent_30',
        'expiry_notification_sent_7',
        'expiry_notification_sent_18m',
        'expiry_notification_sent_12m',
        'expiry_notification_sent_6m',
        'notes',
        'image_path',
        'license_document_path',
        'is_active',
        // New reference fields
        'firearm_calibre_id',
        'firearm_make_id',
        'firearm_model_id',
        'calibre_text_override',
        'make_text_override',
        'model_text_override',
        // SAPS 271 serial fields
        'barrel_serial_number',
        'barrel_make_text',
        'frame_serial_number',
        'frame_make_text',
        'receiver_serial_number',
        'receiver_make_text',
        'engraved_text',
    ];

    protected $casts = [
        'license_issue_date' => 'date',
        'license_expiry_date' => 'date',
        'expiry_notification_sent_90' => 'boolean',
        'expiry_notification_sent_60' => 'boolean',
        'expiry_notification_sent_30' => 'boolean',
        'expiry_notification_sent_7' => 'boolean',
        'expiry_notification_sent_18m' => 'boolean',
        'expiry_notification_sent_12m' => 'boolean',
        'expiry_notification_sent_6m' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
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

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firearmType(): BelongsTo
    {
        return $this->belongsTo(FirearmType::class);
    }

    /**
     * Get the firearm calibre reference.
     */
    public function firearmCalibre(): BelongsTo
    {
        return $this->belongsTo(FirearmCalibre::class, 'firearm_calibre_id');
    }

    /**
     * Get the firearm make reference.
     */
    public function firearmMake(): BelongsTo
    {
        return $this->belongsTo(FirearmMake::class, 'firearm_make_id');
    }

    /**
     * Get the firearm model reference.
     */
    public function firearmModel(): BelongsTo
    {
        return $this->belongsTo(FirearmModel::class, 'firearm_model_id');
    }

    /**
     * Get the firearm components (barrel, frame, receiver) - SAPS 271 canonical.
     */
    public function components(): HasMany
    {
        return $this->hasMany(FirearmComponent::class, 'firearm_id');
    }

    /**
     * Get barrel component.
     */
    public function barrelComponent()
    {
        return $this->components()->where('type', 'barrel')->first();
    }

    /**
     * Get frame component.
     */
    public function frameComponent()
    {
        return $this->components()->where('type', 'frame')->first();
    }

    /**
     * Get receiver component.
     */
    public function receiverComponent()
    {
        return $this->components()->where('type', 'receiver')->first();
    }

    public function loadData(): HasMany
    {
        return $this->hasMany(LoadData::class);
    }

    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    // Accessors

    /**
     * Get SAPS 271 canonical firearm type label.
     */
    public function getFirearmTypeLabelAttribute(): ?string
    {
        return match ($this->firearm_type) {
            'rifle' => 'Rifle',
            'shotgun' => 'Shotgun',
            'handgun' => 'Handgun',
            'combination' => 'Combination',
            'other' => $this->firearm_type_other ? "Other ({$this->firearm_type_other})" : 'Other',
            default => null,
        };
    }

    /**
     * Get SAPS 271 action label.
     */
    public function getActionLabelAttribute(): ?string
    {
        return match ($this->action) {
            'bolt_action' => 'Bolt Action',
            'semi_automatic' => 'Semi-Automatic',
            'lever_action' => 'Lever Action',
            'pump_action' => 'Pump Action',
            'automatic' => 'Automatic',
            'manual' => 'Manual',
            'other' => $this->other_action_text ?? 'Other',
            default => null,
        };
    }

    /**
     * Get primary serial number (from components, prioritizing receiver > frame > barrel).
     */
    public function getPrimarySerialAttribute(): ?string
    {
        // Priority: receiver > frame > barrel
        $receiver = $this->receiverComponent();
        if ($receiver && $receiver->serial) {
            return $receiver->serial;
        }

        $frame = $this->frameComponent();
        if ($frame && $frame->serial) {
            return $frame->serial;
        }

        $barrel = $this->barrelComponent();
        if ($barrel && $barrel->serial) {
            return $barrel->serial;
        }

        // Fallback to legacy serial_number for backwards compatibility
        return $this->serial_number;
    }

    /**
     * Check if firearm has at least one serial number (SAPS 271 requirement).
     */
    public function hasSerialNumber(): bool
    {
        return $this->components()->withSerial()->exists() || ! empty($this->serial_number);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        $parts = array_filter([
            $this->make_display ?? $this->make,
            $this->model_display ?? $this->model,
            $this->calibre_display,
        ]);

        return implode(' ', $parts) ?: 'Unnamed Firearm';
    }

    public function getFullDescriptionAttribute(): string
    {
        $parts = array_filter([
            $this->make_display ?? $this->make,
            $this->model_display ?? $this->model,
            $this->calibre_display,
            $this->primary_serial ? "S/N: {$this->primary_serial}" : null,
        ]);

        return implode(' - ', $parts) ?: 'No details';
    }

    /**
     * Get the calibre display name.
     */
    public function getCalibreDisplayAttribute(): ?string
    {
        // Use new reference system
        if ($this->firearmCalibre) {
            return $this->firearmCalibre->name;
        }

        // Fallback to override
        return $this->calibre_text_override;
    }

    /**
     * Get the make display name.
     */
    public function getMakeDisplayAttribute(): ?string
    {
        if ($this->firearmMake) {
            return $this->firearmMake->name;
        }

        return $this->make_text_override ?? $this->make;
    }

    /**
     * Get the model display name.
     */
    public function getModelDisplayAttribute(): ?string
    {
        if ($this->firearmModel) {
            return $this->firearmModel->name;
        }

        return $this->model_text_override ?? $this->model;
    }

    /**
     * Get SAPS 271 canonical firearm identity string.
     * Used in endorsement letters and official documents.
     */
    public function getSaps271IdentityAttribute(): string
    {
        $parts = [];

        // Type
        if ($this->firearm_type) {
            $parts[] = $this->firearm_type_label;
        }

        // Action
        if ($this->action) {
            $parts[] = $this->action_label;
        }

        // Calibre
        if ($this->calibre_display) {
            $parts[] = $this->calibre_display;
            if ($this->calibre_code) {
                $parts[] = "({$this->calibre_code})";
            }
        } elseif ($this->calibre_code) {
            $parts[] = $this->calibre_code;
        }

        // Make/Model
        $makeName = $this->make_display ?? $this->make;
        if ($makeName) {
            $parts[] = $makeName;
        }
        $modelName = $this->model_display ?? $this->model;
        if ($modelName) {
            $parts[] = $modelName;
        }

        // Serial numbers
        $serials = [];
        foreach (['receiver', 'frame', 'barrel'] as $type) {
            $component = $this->components()->where('type', $type)->first();
            if ($component && $component->serial) {
                $serials[] = ucfirst($type).": {$component->serial}";
            }
        }

        if (! empty($serials)) {
            $parts[] = implode(', ', $serials);
        }

        return implode(' - ', $parts);
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->license_expiry_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->license_expiry_date, false);
    }

    public function getMonthsUntilExpiryAttribute(): ?int
    {
        if (! $this->license_expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInMonths($this->license_expiry_date, false);
    }

    /**
     * Check if notification should be sent for a specific month interval.
     */
    public function shouldSendNotification(int $months): bool
    {
        if (! $this->license_expiry_date) {
            return false;
        }

        $expiryDate = $this->license_expiry_date;
        $notificationDate = now()->addMonths($months);
        $fieldName = "expiry_notification_sent_{$months}m";

        // Check if expiry is within the notification window (same month or earlier)
        // and notification hasn't been sent yet
        if (! isset($this->$fieldName)) {
            return false;
        }

        return $expiryDate->lte($notificationDate) &&
               ! $this->is_expired &&
               ! $this->$fieldName;
    }

    /**
     * Mark notification as sent for a specific month interval.
     */
    public function markNotificationSent(int $months): void
    {
        $fieldName = "expiry_notification_sent_{$months}m";
        if (isset($this->$fieldName)) {
            $this->update([$fieldName => true]);
        }
    }

    public function getIsExpiredAttribute(): bool
    {
        if (! $this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $daysUntilExpiry = $this->days_until_expiry;

        if ($daysUntilExpiry === null) {
            return false;
        }

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 90;
    }

    public function getLicenseStatusBadgeAttribute(): array
    {
        if ($this->is_expired) {
            return ['color' => 'red', 'text' => 'Expired'];
        }

        if ($this->license_status === 'revoked') {
            return ['color' => 'red', 'text' => 'Revoked'];
        }

        if ($this->license_status === 'renewal_pending') {
            return ['color' => 'amber', 'text' => 'Renewal Pending'];
        }

        $daysUntilExpiry = $this->days_until_expiry;

        if ($daysUntilExpiry !== null && $daysUntilExpiry <= 30) {
            return ['color' => 'red', 'text' => "Expires in {$daysUntilExpiry} days"];
        }

        if ($daysUntilExpiry !== null && $daysUntilExpiry <= 60) {
            return ['color' => 'amber', 'text' => "Expires in {$daysUntilExpiry} days"];
        }

        if ($daysUntilExpiry !== null && $daysUntilExpiry <= 90) {
            return ['color' => 'yellow', 'text' => "Expires in {$daysUntilExpiry} days"];
        }

        return ['color' => 'green', 'text' => 'Valid'];
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<=', now()->addDays($days))
            ->where('license_expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // License type labels
    public static function licenseTypes(): array
    {
        return [
            'self_defence' => 'Self Defence',
            'occasional_sport' => 'Occasional Sport/Hunting',
            'dedicated_sport' => 'Dedicated Sport Shooter',
            'dedicated_hunting' => 'Dedicated Hunter',
            'business' => 'Business',
            'private_collection' => 'Private Collection',
        ];
    }

    public function getLicenseTypeLabelAttribute(): string
    {
        return self::licenseTypes()[$this->license_type] ?? $this->license_type ?? 'Unknown';
    }
}
