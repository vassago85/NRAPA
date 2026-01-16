<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeTestAnswer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_text',
        'is_correct',
        'points_awarded',
        'marker_feedback',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    /**
     * Get the attempt.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(KnowledgeTestAttempt::class, 'attempt_id');
    }

    /**
     * Get the question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(KnowledgeTestQuestion::class, 'question_id');
    }

    /**
     * Check if the answer has been marked.
     */
    public function isMarked(): bool
    {
        return $this->points_awarded !== null;
    }

    /**
     * Mark the answer (for written questions).
     */
    public function mark(int $points, ?string $feedback = null): void
    {
        $this->update([
            'points_awarded' => min($points, $this->question->points),
            'marker_feedback' => $feedback,
        ]);
    }
}
