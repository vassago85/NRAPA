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
        'bullet_bc_type',
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
        'powder_price_per_kg',
        'primer_price_per_unit',
        'bullet_price_per_unit',
        'brass_price_per_unit',
        'powder_inventory_id',
        'primer_inventory_id',
        'bullet_inventory_id',
        'brass_inventory_id',
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
        'powder_price_per_kg' => 'decimal:2',
        'primer_price_per_unit' => 'decimal:2',
        'bullet_price_per_unit' => 'decimal:2',
        'brass_price_per_unit' => 'decimal:2',
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

    public function loadingSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoadingSession::class);
    }

    public function ladderTests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LadderTest::class);
    }

    // Inventory relationships
    public function powderInventory(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'powder_inventory_id');
    }

    public function primerInventory(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'primer_inventory_id');
    }

    public function bulletInventory(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'bullet_inventory_id');
    }

    public function brassInventory(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'brass_inventory_id');
    }

    // Legacy calibre relationship removed - LoadData uses calibre_id for legacy data only

    // Accessors

    /**
     * Get calibre name from user firearm if available, or from direct calibre_id.
     */
    public function getCalibreNameAttribute(): ?string
    {
        if ($this->userFirearm && $this->userFirearm->calibre_display) {
            return $this->userFirearm->calibre_display;
        }

        // Standalone load (no firearm linked) — look up calibre directly
        if ($this->calibre_id) {
            return \App\Models\FirearmCalibre::find($this->calibre_id)?->name;
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

    /**
     * Calculate cost per round based on component prices and charge weight.
     */
    public function getCostPerRoundAttribute(): ?float
    {
        $cost = 0;
        $hasAny = false;

        // Powder cost: price_per_kg / 1000 * charge_weight_grams (1 grain = 0.0648g)
        if ($this->powder_price_per_kg && $this->powder_charge) {
            $gramsPerCharge = $this->powder_charge * 0.0648;
            $cost += ($this->powder_price_per_kg / 1000) * $gramsPerCharge;
            $hasAny = true;
        }

        if ($this->primer_price_per_unit) {
            $cost += $this->primer_price_per_unit;
            $hasAny = true;
        }

        if ($this->bullet_price_per_unit) {
            $cost += $this->bullet_price_per_unit;
            $hasAny = true;
        }

        if ($this->brass_price_per_unit) {
            $cost += $this->brass_price_per_unit / max($this->brass_load_count, 1);
            $hasAny = true;
        }

        return $hasAny ? round($cost, 2) : null;
    }

    /**
     * How many times this load has been loaded (loading sessions count).
     * Used to amortise brass cost — each session = one use of the brass cases.
     */
    public function getBrassLoadCountAttribute(): int
    {
        return max($this->loadingSessions()->count(), 1);
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
            'HPBT' => 'HPBT (Hollow Point Boat Tail)',
            'HP' => 'HP (Hollow Point)',
            'SP' => 'SP (Soft Point)',
            'FMJ' => 'FMJ (Full Metal Jacket)',
            'OTM' => 'OTM (Open Tip Match)',
            'BT' => 'BT (Boat Tail)',
            'FB' => 'FB (Flat Base)',
            'RN' => 'RN (Round Nose)',
            'VLD' => 'VLD (Very Low Drag)',
            'ELD-X' => 'ELD-X (Hornady)',
            'ELD-M' => 'ELD-M (Match)',
            'A-TIP' => 'A-TIP (Match)',
            'A-MAX' => 'A-MAX (Hornady)',
            'ABLR' => 'AccuBond Long Range',
            'AB' => 'AccuBond',
            'PT' => 'Partition',
            'TSX' => 'TSX (Triple Shock)',
            'TTSX' => 'TTSX (Tipped Triple Shock)',
            'SMK' => 'Sierra MatchKing',
            'TMK' => 'Sierra Tipped MatchKing',
            'Berger' => 'Berger Hybrid',
            'other' => 'Other',
        ];
    }
}
