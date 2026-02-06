<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_firearm_id',
        'rounds_fired',
        'logged_date',
        'note',
    ];

    protected $casts = [
        'logged_date' => 'date',
        'rounds_fired' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userFirearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class);
    }

    public function scopeForFirearm($query, int $firearmId)
    {
        return $query->where('user_firearm_id', $firearmId);
    }
}
