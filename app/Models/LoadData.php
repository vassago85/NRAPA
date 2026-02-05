<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoadData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'load_data';

    protected $fillable = [
        'uuid',
        'user_id',
        'user_firearm_id',
        'calibre_id',
        'name',
        'bullet_make',
        'bullet_model',
        'bullet_weight',
        'bullet_bc',
        'bullet_type',
        'powder_make',
        'powder_type',
        'powder_charge',
        'primer_make',
        'primer_type',
        'brass_make',
        'brass_firings',
        'brass_annealed',
        'coal',
        'cbto',
        'jump_to_lands',
        'muzzle_velocity',
        'velocity_es',
        'velocity_sd',
        'group_size',
        'group_size_unit',
        'tested_date',
        'tested_distance',
        'tested_distance_unit',
        'tested_temperature',
        'tested_altitude',
        'status',
        'is_favorite',
        'notes',
        'is_max_load',
        'safety_notes',
    ];

    protected $casts = [
        'bullet_weight' => 'decimal:1',
        'bullet_bc' => 'decimal:3',
        'powder_charge' => 'decimal:1',
        'coal' => 'decimal:3',
        'cbto' => 'decimal:3',
        'jump_to_lands' => 'decimal:3',
        'group_size' => 'decimal:2',
        'tested_date' => 'date',
        'tested_temperature' => 'decimal:1',
        'brass_annealed' => 'boolean',
        'is_favorite' => 'boolean',
        'is_max_load' => 'boolean',
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

    public function userFirearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class);
    }

    // Legacy calibre relationship removed - LoadData uses calibre_id for legacy data only

    // Accessors

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

    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->name];
        
        // Use calibre_name accessor
        if ($this->calibre_name) {
            $parts[] = "({$this->calibre_name})";
        }

        return implode(' ', $parts);
    }

    public function getBulletDescriptionAttribute(): string
    {
        $parts = array_filter([
            $this->bullet_make,
            $this->bullet_model,
            $this->bullet_weight ? "{$this->bullet_weight}gr" : null,
            $this->bullet_type,
        ]);

        return implode(' ', $parts) ?: 'Not specified';
    }

    public function getPowderDescriptionAttribute(): string
    {
        $parts = array_filter([
            $this->powder_make,
            $this->powder_type,
            $this->powder_charge ? "{$this->powder_charge}gr" : null,
        ]);

        return implode(' ', $parts) ?: 'Not specified';
    }

    public function getPerformanceSummaryAttribute(): string
    {
        $parts = [];

        if ($this->muzzle_velocity) {
            $parts[] = "{$this->muzzle_velocity} fps";
        }

        if ($this->velocity_sd) {
            $parts[] = "SD: {$this->velocity_sd}";
        }

        if ($this->group_size) {
            $unit = $this->group_size_unit === 'moa' ? 'MOA' : '"';
            $parts[] = "{$this->group_size}{$unit}";
        }

        return implode(' | ', $parts) ?: 'No data';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'development' => ['color' => 'amber', 'text' => 'Development'],
            'tested' => ['color' => 'blue', 'text' => 'Tested'],
            'approved' => ['color' => 'green', 'text' => 'Approved'],
            'retired' => ['color' => 'zinc', 'text' => 'Retired'],
            default => ['color' => 'zinc', 'text' => 'Unknown'],
        };
    }

    // Scopes

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCalibre($query, int $calibreId)
    {
        return $query->where('calibre_id', $calibreId);
    }

    public function scopeForFirearm($query, int $userFirearmId)
    {
        return $query->where('user_firearm_id', $userFirearmId);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Status labels
    public static function statuses(): array
    {
        return [
            'development' => 'Development',
            'tested' => 'Tested',
            'approved' => 'Approved',
            'retired' => 'Retired',
        ];
    }

    // Bullet types
    public static function bulletTypes(): array
    {
        return [
            'FMJ' => 'Full Metal Jacket',
            'HPBT' => 'Hollow Point Boat Tail',
            'SP' => 'Soft Point',
            'HP' => 'Hollow Point',
            'BT' => 'Boat Tail',
            'RN' => 'Round Nose',
            'FB' => 'Flat Base',
            'VLD' => 'Very Low Drag',
            'ELD-X' => 'ELD-X (Hornady)',
            'ELD-M' => 'ELD-Match (Hornady)',
            'A-MAX' => 'A-MAX (Hornady)',
            'SMK' => 'Sierra MatchKing',
            'TMK' => 'Sierra Tipped MatchKing',
            'Berger' => 'Berger Hybrid',
            'Other' => 'Other',
        ];
    }
}
