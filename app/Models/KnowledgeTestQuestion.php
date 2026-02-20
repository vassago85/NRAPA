<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeTestQuestion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'knowledge_test_id',
        'question_type',
        'question_text',
        'image_path',
        'options',
        'correct_answer',
        'correct_answers',
        'points',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answers' => 'array',
            'points' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the knowledge test.
     */
    public function knowledgeTest(): BelongsTo
    {
        return $this->belongsTo(KnowledgeTest::class);
    }

    /**
     * Get the answers for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(KnowledgeTestAnswer::class, 'question_id');
    }

    /**
     * Check if the question is multiple choice.
     */
    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    /**
     * Check if the question is written.
     */
    public function isWritten(): bool
    {
        return $this->question_type === 'written';
    }

    /**
     * Check if the question is multiple select (checkboxes - multiple answers).
     */
    public function isMultipleSelect(): bool
    {
        return $this->question_type === 'multiple_select';
    }

    /**
     * Check if the question is priority order (drag to order).
     */
    public function isPriorityOrder(): bool
    {
        return $this->question_type === 'priority_order';
    }

    /**
     * Check if the question is matching (drag to match pairs).
     */
    public function isMatching(): bool
    {
        return $this->question_type === 'matching';
    }

    /**
     * Check if this question can be auto-marked.
     */
    public function isAutoMarkable(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'multiple_select', 'priority_order', 'matching']);
    }

    /**
     * Get correct answers as array (for multi-select and priority questions).
     */
    public function getCorrectAnswersArray(): array
    {
        if ($this->correct_answers) {
            return $this->correct_answers;
        }

        // Fallback for single correct_answer (multiple_choice)
        if ($this->correct_answer) {
            return [$this->correct_answer];
        }

        return [];
    }

    /**
     * Check if an answer is correct (for multiple choice).
     */
    public function isCorrectAnswer(string $answer): bool
    {
        if (! $this->isMultipleChoice()) {
            return false;
        }

        return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
    }

    /**
     * Check multi-select answers and return score info.
     * Returns ['correct' => bool, 'partial_score' => float (0-1), 'correct_count' => int, 'total' => int]
     */
    public function checkMultiSelectAnswer(array $selectedAnswers): array
    {
        if (!$this->isMultipleSelect()) {
            return ['correct' => false, 'partial_score' => 0, 'correct_count' => 0, 'total' => 0];
        }

        $correctAnswers = $this->getCorrectAnswersArray();
        $normalizedCorrect = array_map(fn($a) => strtolower(trim($a)), $correctAnswers);
        $normalizedSelected = array_map(fn($a) => strtolower(trim($a)), $selectedAnswers);

        // Count correct selections
        $correctCount = count(array_intersect($normalizedSelected, $normalizedCorrect));
        $totalCorrect = count($correctAnswers);

        // Check for wrong selections (selected but not in correct answers)
        $wrongSelections = count(array_diff($normalizedSelected, $normalizedCorrect));

        // Exact match = fully correct
        $isExactMatch = $correctCount === $totalCorrect && $wrongSelections === 0;

        // Partial score: correct selections only; no deduction for wrong answers
        $partialScore = $totalCorrect > 0 ? $correctCount / $totalCorrect : 0;

        return [
            'correct' => $isExactMatch,
            'partial_score' => $partialScore,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongSelections,
            'total' => $totalCorrect,
        ];
    }

    /**
     * Check priority order answers and return score info.
     * Returns ['correct' => bool, 'partial_score' => float (0-1), 'positions_correct' => int, 'total' => int]
     */
    public function checkPriorityOrderAnswer(array $orderedAnswers): array
    {
        if (!$this->isPriorityOrder()) {
            return ['correct' => false, 'partial_score' => 0, 'positions_correct' => 0, 'total' => 0];
        }

        $correctOrder = $this->getCorrectAnswersArray();
        $normalizedCorrect = array_map(fn($a) => strtolower(trim($a)), $correctOrder);
        $normalizedOrdered = array_map(fn($a) => strtolower(trim($a)), $orderedAnswers);

        $total = count($correctOrder);
        $positionsCorrect = 0;

        // Compare position by position
        for ($i = 0; $i < $total; $i++) {
            if (isset($normalizedOrdered[$i]) && $normalizedOrdered[$i] === $normalizedCorrect[$i]) {
                $positionsCorrect++;
            }
        }

        $isExactMatch = $positionsCorrect === $total;
        $partialScore = $total > 0 ? $positionsCorrect / $total : 0;

        return [
            'correct' => $isExactMatch,
            'partial_score' => $partialScore,
            'positions_correct' => $positionsCorrect,
            'total' => $total,
        ];
    }

    /**
     * Check matching answers and return score info.
     * Member answer format: {"A": "Answer text they matched", "B": "Another answer", ...}
     * Correct answers format: {"A": "Correct answer for A", "B": "Correct answer for B", ...}
     * Returns ['correct' => bool, 'partial_score' => float (0-1), 'matches_correct' => int, 'total' => int]
     */
    public function checkMatchingAnswer(array $memberMatches): array
    {
        if (!$this->isMatching()) {
            return ['correct' => false, 'partial_score' => 0, 'matches_correct' => 0, 'total' => 0];
        }

        $allCorrect = $this->correct_answers ?? [];
        // Only letter-keyed pairs (A, B, C, ...) count; exclude _distractors (extra wrong answers)
        $correctMatches = array_filter($allCorrect, function ($value, $key) {
            return $key !== '_distractors' && is_string($value);
        }, ARRAY_FILTER_USE_BOTH);
        $total = count($correctMatches);
        $matchesCorrect = 0;

        foreach ($correctMatches as $key => $correctAnswer) {
            $memberAnswer = $memberMatches[$key] ?? null;
            if ($memberAnswer !== null && strtolower(trim($memberAnswer)) === strtolower(trim($correctAnswer))) {
                $matchesCorrect++;
            }
        }

        $isExactMatch = $matchesCorrect === $total;
        $partialScore = $total > 0 ? $matchesCorrect / $total : 0;

        return [
            'correct' => $isExactMatch,
            'partial_score' => $partialScore,
            'matches_correct' => $matchesCorrect,
            'total' => $total,
        ];
    }

    /**
     * Get matching answers (the right-side options) for display.
     * Returns array of answer values from correct_answers (excluding distractors).
     */
    public function getMatchingAnswerOptions(): array
    {
        if (!$this->isMatching() || !$this->correct_answers) {
            return [];
        }

        // Filter out the _distractors key
        $answers = [];
        foreach ($this->correct_answers as $key => $value) {
            if ($key !== '_distractors') {
                $answers[] = $value;
            }
        }
        return $answers;
    }

    /**
     * Get distractor answers (extra wrong answers).
     */
    public function getMatchingDistractors(): array
    {
        if (!$this->isMatching() || !$this->correct_answers) {
            return [];
        }

        return $this->correct_answers['_distractors'] ?? [];
    }

    /**
     * Get all possible matching answers including distractors.
     */
    public function getAllMatchingAnswers(): array
    {
        $answers = $this->getMatchingAnswerOptions();
        $distractors = $this->getMatchingDistractors();
        
        return array_merge($answers, $distractors);
    }

    /**
     * Get shuffled matching answers for test-taking.
     * Uses a seed to ensure consistent shuffle per attempt.
     * Includes distractors (extra wrong answers).
     */
    public function getShuffledMatchingAnswers(int $seed): array
    {
        $answers = $this->getAllMatchingAnswers();
        
        // Seed the random number generator for consistent shuffle
        mt_srand($seed + $this->id);
        shuffle($answers);
        mt_srand(); // Reset to true random
        
        return $answers;
    }

    /**
     * Scope to only active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to only multiple choice questions.
     */
    public function scopeMultipleChoice($query)
    {
        return $query->where('question_type', 'multiple_choice');
    }

    /**
     * Scope to only written questions.
     */
    public function scopeWritten($query)
    {
        return $query->where('question_type', 'written');
    }

    /**
     * Scope to only multiple select questions.
     */
    public function scopeMultipleSelect($query)
    {
        return $query->where('question_type', 'multiple_select');
    }

    /**
     * Scope to only priority order questions.
     */
    public function scopePriorityOrder($query)
    {
        return $query->where('question_type', 'priority_order');
    }

    /**
     * Scope to only matching questions.
     */
    public function scopeMatching($query)
    {
        return $query->where('question_type', 'matching');
    }

    /**
     * Scope to auto-markable questions.
     */
    public function scopeAutoMarkable($query)
    {
        return $query->whereIn('question_type', ['multiple_choice', 'multiple_select', 'priority_order', 'matching']);
    }

    /**
     * Check if the question has an image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->hasImage()) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::url($this->image_path);
    }
}
