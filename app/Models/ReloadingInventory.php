<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReloadingInventory extends Model
{
    protected $table = 'reloading_inventories';

    protected $fillable = [
        'user_id',
        'type',
        'make',
        'name',
        'quantity',
        'unit',
        'cost_per_unit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->make} {$this->name}";
    }

    public function getIsLowStockAttribute(): bool
    {
        return match ($this->type) {
            'powder' => $this->quantity < 500,    // Less than 500g
            'primer' => $this->quantity < 100,    // Less than 100 primers
            'bullet' => $this->quantity < 50,     // Less than 50 bullets
            'brass' => $this->quantity < 50,      // Less than 50 brass
            default => false,
        };
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public static function types(): array
    {
        return [
            'powder' => 'Powder',
            'primer' => 'Primer',
            'bullet' => 'Bullet',
            'brass' => 'Brass',
        ];
    }

    public static function defaultUnits(): array
    {
        return [
            'powder' => 'grams',
            'primer' => 'units',
            'bullet' => 'units',
            'brass' => 'units',
        ];
    }
}
