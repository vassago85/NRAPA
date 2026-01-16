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
        'firearm_type_id',
        'calibre_id',
        'make',
        'model',
        'serial_number',
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
        'notes',
        'image_path',
        'license_document_path',
        'is_active',
    ];

    protected $casts = [
        'license_issue_date' => 'date',
        'license_expiry_date' => 'date',
        'expiry_notification_sent_90' => 'boolean',
        'expiry_notification_sent_60' => 'boolean',
        'expiry_notification_sent_30' => 'boolean',
        'expiry_notification_sent_7' => 'boolean',
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

    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class);
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

    public function getDisplayNameAttribute(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        $parts = array_filter([
            $this->make,
            $this->model,
            $this->calibre?->name,
        ]);

        return implode(' ', $parts) ?: 'Unnamed Firearm';
    }

    public function getFullDescriptionAttribute(): string
    {
        $parts = array_filter([
            $this->make,
            $this->model,
            $this->calibre?->name,
            $this->serial_number ? "S/N: {$this->serial_number}" : null,
        ]);

        return implode(' - ', $parts) ?: 'No details';
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->license_expiry_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->license_expiry_date, false);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->license_expiry_date) {
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
