<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
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
        'activity_type_id',
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
     * Get the activity type.
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the event types for this category.
     */
    public function eventTypes(): HasMany
    {
        return $this->hasMany(EventType::class);
    }

    /**
     * Get the shooting activities for this category.
     */
    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    // ===== Scopes =====

    /**
     * Scope to only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to categories for a specific activity type.
     */
    public function scopeForActivityType($query, $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

    /**
     * Scope to categories for a specific dedicated type.
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
