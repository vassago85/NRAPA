<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeTest extends Model
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
        'passing_score',
        'time_limit_minutes',
        'max_attempts',
        'is_active',
        'dedicated_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'time_limit_minutes' => 'integer',
            'max_attempts' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the questions for the test.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(KnowledgeTestQuestion::class);
    }

    /**
     * Get the active questions for the test.
     */
    public function activeQuestions(): HasMany
    {
        return $this->questions()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get all attempts for the test.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(KnowledgeTestAttempt::class);
    }

    /**
     * Scope to only active tests.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to tests for a specific dedicated type.
     */
    public function scopeForDedicatedType($query, string $dedicatedType)
    {
        return $query->where(function ($q) use ($dedicatedType) {
            $q->where('dedicated_type', $dedicatedType)
              ->orWhere('dedicated_type', 'both');
        });
    }

    /**
     * Scope to tests that are for dedicated status (hunter or sport_shooter).
     */
    public function scopeForDedicatedStatus($query)
    {
        return $query->whereNotNull('dedicated_type');
    }

    /**
     * Check if the test is for a specific dedicated type.
     */
    public function isForDedicatedType(string $dedicatedType): bool
    {
        return $this->dedicated_type === $dedicatedType || $this->dedicated_type === 'both';
    }

    /**
     * Get the dedicated type display name.
     */
    public function getDedicatedTypeDisplayAttribute(): ?string
    {
        return match($this->dedicated_type) {
            'hunter' => 'Dedicated Hunter',
            'sport_shooter' => 'Dedicated Sport Shooter',
            'both' => 'Both (Hunter & Sport Shooter)',
            default => null,
        };
    }

    /**
     * Get the total points available for the test.
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->activeQuestions()->sum('points');
    }

    /**
     * Check if a user can attempt the test.
     */
    public function canAttempt(User $user): bool
    {
        $attemptCount = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return $attemptCount < $this->max_attempts;
    }

    /**
     * Get the remaining attempts for a user.
     */
    public function remainingAttempts(User $user): int
    {
        $attemptCount = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return max(0, $this->max_attempts - $attemptCount);
    }
}
