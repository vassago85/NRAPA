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
}
