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
        'document_path',
        'passing_score',
        'time_limit_minutes',
        'max_attempts',
        'is_active',
        'show_answers_after_completion',
        'archived_at',
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
            'show_answers_after_completion' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Check if the test is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Archive the test.
     */
    public function archive(): void
    {
        $this->update([
            'archived_at' => now(),
            'is_active' => false,
        ]);
    }

    /**
     * Restore the test from archive.
     */
    public function restore(): void
    {
        $this->update([
            'archived_at' => null,
        ]);
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
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Scope to exclude archived tests.
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to only archived tests.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope to tests for a specific dedicated type.
     *
     * @param  string|null  $userDedicatedType  The user's membership dedicated_type
     */
    public function scopeForDedicatedType($query, ?string $userDedicatedType)
    {
        if ($userDedicatedType === 'both') {
            // Users with "both" membership see all 3 tests: Hunter, Sport, and Combined
            return $query->where(function ($q) {
                $q->whereNull('dedicated_type')
                    ->orWhereIn('dedicated_type', ['hunter', 'sport', 'sport_shooter', 'both']);
            });
        }

        if ($userDedicatedType === 'hunter') {
            // Hunter membership: only see Hunter test (and general tests)
            return $query->where(function ($q) {
                $q->whereNull('dedicated_type')
                    ->orWhere('dedicated_type', 'hunter');
            });
        }

        if ($userDedicatedType === 'sport' || $userDedicatedType === 'sport_shooter') {
            // Sport Shooter membership: only see Sport Shooter test (and general tests)
            return $query->where(function ($q) {
                $q->whereNull('dedicated_type')
                    ->orWhereIn('dedicated_type', ['sport', 'sport_shooter']);
            });
        }

        if ($userDedicatedType) {
            // Fallback for any other type: show general + their type only
            return $query->where(function ($q) use ($userDedicatedType) {
                $q->whereNull('dedicated_type')
                    ->orWhere('dedicated_type', $userDedicatedType);
            });
        }

        // Users with no dedicated status only see general tests
        return $query->whereNull('dedicated_type');
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
        return match ($this->dedicated_type) {
            'hunter' => 'Dedicated Hunter',
            'sport' => 'Dedicated Sport Shooter',
            'sport_shooter' => 'Dedicated Sport Shooter', // Legacy support
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

    /**
     * Check if the test has a document.
     */
    public function hasDocument(): bool
    {
        return ! empty($this->document_path);
    }

    /**
     * Get the document URL.
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if (! $this->hasDocument()) {
            return null;
        }

        return \App\Helpers\StorageHelper::getUrl($this->document_path);
    }
}
