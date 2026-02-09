<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'role',
        'ip_address',
        'user_agent',
        'via_2fa',
        'via_remember',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'via_2fa' => 'boolean',
            'via_remember' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LoginLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    /**
     * Get the user who logged in.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a login event.
     */
    public static function record(User $user, bool $viaRemember = false): static
    {
        return static::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'via_2fa' => $user->has2FAEnabled(),
            'via_remember' => $viaRemember,
        ]);
    }

    /**
     * Get the role badge colour.
     */
    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            'developer' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
            'owner' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200',
            'admin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }

    /**
     * Scope to admin-level users (admin, owner, developer).
     */
    public function scopeAdminLevel($query)
    {
        return $query->whereIn('role', [
            User::ROLE_ADMIN,
            User::ROLE_OWNER,
            User::ROLE_DEVELOPER,
        ]);
    }

    /**
     * Parse the user agent into a short browser string.
     */
    public function getBrowserAttribute(): string
    {
        $ua = $this->user_agent ?? '';

        if (str_contains($ua, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'Edg/')) {
            return 'Edge';
        }
        if (str_contains($ua, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Safari')) {
            return 'Safari';
        }

        return 'Other';
    }
}
