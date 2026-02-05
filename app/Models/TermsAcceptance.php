<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermsAcceptance extends Model
{
    protected $fillable = [
        'user_id',
        'terms_version_id',
        'accepted_at',
        'accepted_ip',
        'accepted_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the user who accepted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the terms version that was accepted.
     */
    public function termsVersion(): BelongsTo
    {
        return $this->belongsTo(TermsVersion::class);
    }
}
