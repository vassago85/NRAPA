<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calibre extends Model
{
    // Category constants
    public const CATEGORY_HANDGUN = 'handgun';
    public const CATEGORY_RIFLE = 'rifle';
    public const CATEGORY_SHOTGUN = 'shotgun';
    public const CATEGORY_OTHER = 'other';

    // Ignition type constants
    public const IGNITION_RIMFIRE = 'rimfire';
    public const IGNITION_CENTERFIRE = 'centerfire';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'saps_code',  // Official SAPS CFR calibre code
        'category',
        'ignition_type',
        'aliases',
        'is_active',
        'is_common',
        'is_obsolete',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_common' => 'boolean',
            'is_obsolete' => 'boolean',
            'aliases' => 'array',
        ];
    }

    /**
     * Get category options for dropdowns.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_HANDGUN => 'Handgun',
            self::CATEGORY_RIFLE => 'Rifle',
            self::CATEGORY_SHOTGUN => 'Shotgun',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get ignition type options for dropdowns.
     */
    public static function getIgnitionTypeOptions(): array
    {
        return [
            self::IGNITION_RIMFIRE => 'Rimfire',
            self::IGNITION_CENTERFIRE => 'Centerfire',
        ];
    }

    /**
     * Get the shooting activities using this calibre.
     */
    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    /**
     * Get the user firearms using this calibre.
     */
    public function userFirearms(): HasMany
    {
        return $this->hasMany(UserFirearm::class);
    }

    /**
     * Get the load data for this calibre.
     */
    public function loadData(): HasMany
    {
        return $this->hasMany(LoadData::class);
    }

    // ===== Scopes =====

    /**
     * Scope to only active calibres.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to calibres for a specific category.
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to calibres for a specific ignition type.
     */
    public function scopeForIgnitionType($query, string $ignitionType)
    {
        return $query->where('ignition_type', $ignitionType);
    }

    /**
     * Scope to only common calibres.
     */
    public function scopeCommon($query)
    {
        return $query->where('is_common', true);
    }

    /**
     * Scope to exclude obsolete calibres.
     */
    public function scopeNotObsolete($query)
    {
        return $query->where('is_obsolete', false);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Search calibres by name or aliases.
     */
    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(aliases) LIKE ?', ["%{$term}%"]);
        });
    }

    // ===== Accessors =====

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategoryOptions()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get the ignition type label.
     */
    public function getIgnitionTypeLabelAttribute(): string
    {
        return self::getIgnitionTypeOptions()[$this->ignition_type] ?? ucfirst($this->ignition_type);
    }

    /**
     * Get display name with common aliases.
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->aliases) && count($this->aliases) > 0) {
            return $this->name . ' (' . implode(', ', array_slice($this->aliases, 0, 2)) . ')';
        }
        return $this->name;
    }
}
