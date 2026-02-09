<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationDismissal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'dismissable_type',
        'dismissable_id',
        'dismissed_at',
    ];

    protected $casts = [
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dismissable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Dismiss a specific item for a user.
     */
    public static function dismiss(int $userId, string $type, int $id): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'dismissable_type' => $type,
                'dismissable_id' => $id,
            ],
            [
                'dismissed_at' => now(),
            ]
        );
    }

    /**
     * Dismiss multiple items of the same type for a user.
     */
    public static function dismissMany(int $userId, string $type, array $ids): void
    {
        foreach ($ids as $id) {
            static::dismiss($userId, $type, $id);
        }
    }

    /**
     * Get all dismissed IDs of a given type for a user.
     */
    public static function getDismissedIds(int $userId, string $type): array
    {
        return static::where('user_id', $userId)
            ->where('dismissable_type', $type)
            ->pluck('dismissable_id')
            ->toArray();
    }

    /**
     * Check if a specific item is dismissed for a user.
     */
    public static function isDismissed(int $userId, string $type, int $id): bool
    {
        return static::where('user_id', $userId)
            ->where('dismissable_type', $type)
            ->where('dismissable_id', $id)
            ->exists();
    }

    /**
     * Restore (un-dismiss) a specific item for a user.
     */
    public static function restore(int $userId, string $type, int $id): void
    {
        static::where('user_id', $userId)
            ->where('dismissable_type', $type)
            ->where('dismissable_id', $id)
            ->delete();
    }
}
