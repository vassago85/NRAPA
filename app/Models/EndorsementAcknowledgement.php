<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable per-clause acknowledgement record captured when a self-defence
 * endorsement is submitted. Rows are written once and never updated — they
 * form the evidentiary audit trail for the member's declaration.
 */
class EndorsementAcknowledgement extends Model
{
    protected $fillable = [
        'endorsement_request_id',
        'user_id',
        'clause_key',
        'clause_text',
        'accepted',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Guard against edits after creation — these records are immutable.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            return false;
        });
    }

    public function endorsementRequest(): BelongsTo
    {
        return $this->belongsTo(EndorsementRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
