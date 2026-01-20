<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class UserSecurityQuestion extends Model
{
    /**
     * Pre-defined security questions users can choose from.
     */
    public const AVAILABLE_QUESTIONS = [
        'pet_name' => "What was the name of your first pet?",
        'school' => "What was the name of your primary school?",
        'birth_city' => "In what city were you born?",
        'mother_maiden' => "What is your mother's maiden name?",
        'first_car' => "What was the make of your first car?",
        'childhood_friend' => "What was the name of your childhood best friend?",
        'favorite_teacher' => "What was the surname of your favorite teacher?",
        'street_grew_up' => "What street did you grow up on?",
        'first_job' => "What was your first job?",
        'wedding_city' => "In what city did you get married?",
    ];

    /**
     * Number of questions required for 2FA reset verification.
     */
    public const REQUIRED_QUESTIONS = 3;

    protected $fillable = [
        'user_id',
        'question',
        'answer_hash',
    ];

    protected $hidden = [
        'answer_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the answer (auto-hashes).
     */
    public function setAnswer(string $answer): void
    {
        $this->answer_hash = Hash::make(strtolower(trim($answer)));
        $this->save();
    }

    /**
     * Verify an answer against the hash.
     */
    public function verifyAnswer(string $answer): bool
    {
        return Hash::check(strtolower(trim($answer)), $this->answer_hash);
    }

    /**
     * Get the question text from the key.
     */
    public static function getQuestionText(string $key): ?string
    {
        return self::AVAILABLE_QUESTIONS[$key] ?? null;
    }

    /**
     * Get available questions formatted for a dropdown.
     */
    public static function getQuestionOptions(): array
    {
        return self::AVAILABLE_QUESTIONS;
    }
}
