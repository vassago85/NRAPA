<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     * Matches the existing audit_logs table structure.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Indicates if the model uses timestamps.
     * We only use created_at, not updated_at.
     */
    public $timestamps = true;

    /**
     * The name of the "updated at" column.
     * Set to null to disable updated_at since the column doesn't exist.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AuditLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            // Ensure created_at is set
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model (what was acted upon).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for auditable() for backward compatibility.
     */
    public function subject(): MorphTo
    {
        return $this->auditable();
    }

    /**
     * Alias for user() for backward compatibility.
     */
    public function actor(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Static helper to create an audit log entry.
     *
     * @param  string  $event  The event name (e.g., 'membership_approved')
     * @param  Model  $auditable  The model being audited
     * @param  array|null  $oldValues  Old values (optional)
     * @param  array|null  $newValues  New values (optional)
     * @param  User|null  $user  The user performing the action (defaults to auth user)
     */
    public static function log(
        string $event,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): static {
        return static::create([
            'user_id' => $user?->id ?? auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
