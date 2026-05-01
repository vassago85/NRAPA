<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipRenewalReminder extends Model
{
    public const KIND_THIRTY_DAYS = 'thirty_days';
    public const KIND_SEVEN_DAYS = 'seven_days';
    public const KIND_EXPIRED = 'expired';

    protected $fillable = [
        'membership_id',
        'kind',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
