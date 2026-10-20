<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Idempotency log for the year-end activity reminder. One row per
 * (user, year) — the unique index on the DB side blocks a second send for
 * the same user and activity year.
 */
class ActivityReminder extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'emailed',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'emailed' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
