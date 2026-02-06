<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingSession extends Model
{
    protected $fillable = [
        'user_id',
        'load_data_id',
        'rounds_loaded',
        'session_date',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'rounds_loaded' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loadData(): BelongsTo
    {
        return $this->belongsTo(LoadData::class);
    }
}
