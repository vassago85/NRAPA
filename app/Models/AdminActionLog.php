<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AdminActionLog extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'role_at_action',
        'permission_used',
        'action',
        'target_type',
        'target_id',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AdminActionLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the admin who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the target of the action.
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create an action log entry.
     */
    public static function log(
        User $admin,
        string $permission,
        string $action,
        Model $target,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null
    ): self {
        return self::create([
            'user_id' => $admin->id,
            'role_at_action' => $admin->role,
            'permission_used' => $permission,
            'action' => $action,
            'target_type' => get_class($target),
            'target_id' => $target->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope to filter by admin.
     */
    public function scopeByAdmin($query, User $admin)
    {
        return $query->where('user_id', $admin->id);
    }

    /**
     * Scope to filter by permission.
     */
    public function scopeByPermission($query, string $permission)
    {
        return $query->where('permission_used', $permission);
    }

    /**
     * Scope to filter by target type.
     */
    public function scopeByTargetType($query, string $type)
    {
        return $query->where('target_type', $type);
    }

    /**
     * Get action display name.
     */
    public function getActionDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }
}
