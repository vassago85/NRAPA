<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'dedicated_type',
        'is_active',
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
        ];
    }

    /**
     * Get the event categories for this activity type.
     */
    public function eventCategories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }

    /**
     * Get the shooting activities for this activity type.
     */
    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    // ===== Scopes =====

    /**
     * Scope to only active activity types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to activity types for a specific dedicated type.
     */
    public function scopeForDedicatedType($query, string $dedicatedType)
    {
        return $query->where(function ($q) use ($dedicatedType) {
            $q->where('dedicated_type', $dedicatedType)
              ->orWhere('dedicated_type', 'both');
        });
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
