<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgeTestAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'knowledge_test_id',
        'started_at',
        'submitted_at',
        'auto_score',
        'manual_score',
        'total_score',
        'passed',
        'marked_at',
        'marked_by',
        'marker_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'marked_at' => 'datetime',
            'auto_score' => 'integer',
            'manual_score' => 'integer',
            'total_score' => 'integer',
            'passed' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (KnowledgeTestAttempt $attempt) {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
            if (empty($attempt->started_at)) {
                $attempt->started_at = now();
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

    /**
     * Get the user that owns the attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the knowledge test.
     */
    public function knowledgeTest(): BelongsTo
    {
        return $this->belongsTo(KnowledgeTest::class);
    }

    /**
     * Get the admin who marked the attempt.
     */
    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Get the answers for this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(KnowledgeTestAnswer::class, 'attempt_id');
    }

    // ===== Status Checks =====

    /**
     * Check if the attempt is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->submitted_at === null;
    }

    /**
     * Check if the attempt is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Check if the attempt is fully marked.
     */
    public function isFullyMarked(): bool
    {
        if (! $this->isSubmitted()) {
            return false;
        }

        // Check if all written questions have been marked
        // (multiple_choice, multiple_select, and priority_order are auto-marked)
        $writtenAnswers = $this->answers()
            ->whereHas('question', fn ($q) => $q->where('question_type', 'written'))
            ->get();

        foreach ($writtenAnswers as $answer) {
            if ($answer->points_awarded === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the attempt has timed out.
     */
    public function hasTimedOut(): bool
    {
        if ($this->isSubmitted()) {
            return false;
        }

        $timeLimit = $this->knowledgeTest->time_limit_minutes;
        if (! $timeLimit) {
            return false;
        }

        return $this->started_at->addMinutes($timeLimit)->isPast();
    }

    /**
     * Get the time remaining in seconds.
     */
    public function getTimeRemainingInSeconds(): ?int
    {
        if ($this->isSubmitted()) {
            return 0;
        }

        $timeLimit = $this->knowledgeTest->time_limit_minutes;
        if (! $timeLimit) {
            return null;
        }

        $endTime = $this->started_at->addMinutes($timeLimit);
        $remaining = now()->diffInSeconds($endTime, false);

        return max(0, $remaining);
    }

    // ===== Actions =====

    /**
     * Submit the attempt.
     */
    public function submit(): void
    {
        // Auto-score auto-markable answers (multiple choice, multiple select, priority order)
        $autoScore = 0;
        foreach ($this->answers as $answer) {
            $question = $answer->question;
            
            if ($question->isMultipleChoice()) {
                // Single answer - existing logic
                $isCorrect = $question->isCorrectAnswer($answer->answer_text ?? '');
                $pointsAwarded = $isCorrect ? $question->points : 0;

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ]);

                $autoScore += $pointsAwarded;
            } elseif ($question->isMultipleSelect()) {
                // Multiple answers - check all selections
                $answerText = $answer->answer_text ?? '';
                $selectedAnswers = [];
                
                // Parse JSON array from answer
                if (!empty($answerText) && str_starts_with($answerText, '[')) {
                    $selectedAnswers = json_decode($answerText, true) ?? [];
                }
                
                $result = $question->checkMultiSelectAnswer($selectedAnswers);
                
                // Award points based on partial_score (0-1) * total points
                $pointsAwarded = (int) round($result['partial_score'] * $question->points);
                
                $answer->update([
                    'is_correct' => $result['correct'],
                    'points_awarded' => $pointsAwarded,
                ]);

                $autoScore += $pointsAwarded;
            } elseif ($question->isPriorityOrder()) {
                // Priority order - check sequence
                $answerText = $answer->answer_text ?? '';
                $orderedAnswers = [];
                
                // Parse JSON array from answer
                if (!empty($answerText) && str_starts_with($answerText, '[')) {
                    $orderedAnswers = json_decode($answerText, true) ?? [];
                }
                
                $result = $question->checkPriorityOrderAnswer($orderedAnswers);
                
                // Award points based on partial_score (0-1) * total points
                $pointsAwarded = (int) round($result['partial_score'] * $question->points);
                
                $answer->update([
                    'is_correct' => $result['correct'],
                    'points_awarded' => $pointsAwarded,
                ]);

                $autoScore += $pointsAwarded;
            }
        }

        $this->update([
            'submitted_at' => now(),
            'auto_score' => $autoScore,
        ]);

        // If no written questions, finalize immediately
        $this->finalizeIfComplete();
    }

    /**
     * Finalize the attempt if all questions are marked.
     */
    public function finalizeIfComplete(): void
    {
        if (! $this->isFullyMarked()) {
            return;
        }

        $totalScore = $this->answers()->sum('points_awarded');
        $totalPossible = $this->knowledgeTest->total_points;
        $percentage = $totalPossible > 0 ? ($totalScore / $totalPossible) * 100 : 0;
        $passed = $percentage >= $this->knowledgeTest->passing_score;

        $this->update([
            'total_score' => $totalScore,
            'passed' => $passed,
        ]);
    }

    /**
     * Mark the attempt (for written questions).
     */
    public function markComplete(User $admin, ?string $notes = null): void
    {
        $manualScore = $this->answers()
            ->whereHas('question', fn ($q) => $q->where('question_type', 'written'))
            ->sum('points_awarded');

        $this->update([
            'manual_score' => $manualScore,
            'marked_at' => now(),
            'marked_by' => $admin->id,
            'marker_notes' => $notes,
        ]);

        $this->finalizeIfComplete();
    }

    // ===== Scopes =====

    /**
     * Scope to only submitted attempts.
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope to only passed attempts.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to attempts needing marking.
     */
    public function scopeNeedsMarking($query)
    {
        return $query->whereNotNull('submitted_at')
            ->whereNull('marked_at')
            ->whereHas('answers.question', fn ($q) => $q->where('question_type', 'written'));
    }
}
