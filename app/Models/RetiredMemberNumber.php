<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks member numbers that have been used in the past and must never be
 * re-issued to a new member. A row is written here whenever a user is
 * deleted so their NRAPA number stays retired.
 */
class RetiredMemberNumber extends Model
{
    protected $fillable = [
        'member_number',
        'user_id',
        'name',
        'email',
        'reason',
        'retired_at',
    ];

    protected $casts = [
        'member_number' => 'integer',
        'retired_at' => 'datetime',
    ];

    /**
     * Retire a member number so it cannot be reused.
     */
    public static function retire(
        int $memberNumber,
        ?int $userId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $reason = null,
    ): void {
        if ($memberNumber <= 0) {
            return;
        }

        static::updateOrCreate(
            ['member_number' => $memberNumber],
            [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'reason' => $reason,
                'retired_at' => now(),
            ]
        );
    }
}
