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
        'track',
        'group',
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
     * Scope to activity types for a specific track.
     */
    public function scopeForTrack($query, ?string $track)
    {
        if (!$track) {
            return $query;
        }

        return $query->where('track', $track);
    }

    /**
     * Scope to activity types in a specific group.
     */
    public function scopeForGroup($query, ?string $group)
    {
        if (!$group) {
            return $query;
        }

        return $query->where('group', $group);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get available groups for UI grouping.
     */
    public static function getGroups(): array
    {
        return [
            'Training' => 'Training',
            'Competitions' => 'Competitions',
            'Hunting' => 'Hunting',
            'Meetings' => 'Meetings',
            'Expos' => 'Expos',
            'Other' => 'Other',
        ];
    }
}
