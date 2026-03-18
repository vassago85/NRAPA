<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ActivityTag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'label',
        'track',
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
     * Get the shooting activities that have this tag.
     */
    public function shootingActivities(): BelongsToMany
    {
        return $this->belongsToMany(ShootingActivity::class, 'activity_tag_shooting_activity')
            ->withTimestamps();
    }

    // ===== Scopes =====

    /**
     * Scope to only active tags.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to tags for a specific track.
     */
    public function scopeForTrack($query, ?string $track)
    {
        if (! $track) {
            return $query->whereNull('track');
        }

        return $query->where(function ($q) use ($track) {
            $q->where('track', $track)
                ->orWhereNull('track');
        });
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
