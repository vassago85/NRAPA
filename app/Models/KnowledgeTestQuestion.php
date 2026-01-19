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
