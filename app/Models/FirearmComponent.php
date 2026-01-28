<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirearmComponent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firearm_id',
        'type',
        'serial',
        'make',
        'notes',
    ];

    /**
     * Hard-coded component types as per SAPS 271.
     * These are NOT admin-configurable.
     */
    public const TYPE_BARREL = 'barrel';
    public const TYPE_FRAME = 'frame';
    public const TYPE_RECEIVER = 'receiver';

    public static function types(): array
    {
        return [
            self::TYPE_BARREL => 'Barrel',
            self::TYPE_FRAME => 'Frame',
            self::TYPE_RECEIVER => 'Receiver',
        ];
    }

    /**
     * Get the firearm that owns this component.
     */
    public function firearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class, 'firearm_id');
    }

    /**
     * Get the component type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    /**
     * Scope to get components with serial numbers.
     */
    public function scopeWithSerial($query)
    {
        return $query->whereNotNull('serial')->where('serial', '!=', '');
    }
}
