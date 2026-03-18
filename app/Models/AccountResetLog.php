<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountResetLog extends Model
{
    public const TYPE_PASSWORD = 'password';

    public const TYPE_2FA = '2fa';

    protected $fillable = [
        'user_id',
        'reset_by',
        'reset_type',
        'verification_passed',
        'notes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'verification_passed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by');
    }

    /**
     * Scope to get password resets.
     */
    public function scopePasswordResets($query)
    {
        return $query->where('reset_type', self::TYPE_PASSWORD);
    }

    /**
     * Scope to get 2FA resets.
     */
    public function scope2faResets($query)
    {
        return $query->where('reset_type', self::TYPE_2FA);
    }

    /**
     * Get reset type label.
     */
    public function getResetTypeLabelAttribute(): string
    {
        return match ($this->reset_type) {
            self::TYPE_PASSWORD => 'Password Reset',
            self::TYPE_2FA => '2FA Reset',
            default => 'Unknown',
        };
    }
}
