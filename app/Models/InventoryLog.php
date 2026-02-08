<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLog extends Model
{
    protected $fillable = [
        'reloading_inventory_id',
        'user_id',
        'type',
        'quantity_change',
        'balance_after',
        'rounds',
        'source',
        'source_id',
        'source_type',
        'reason',
        'logged_at',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:4',
        'balance_after' => 'decimal:2',
        'rounds' => 'integer',
        'logged_at' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'reloading_inventory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source model (LoadData, LadderTest, etc.).
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    /**
     * Friendly display of the log entry.
     */
    public function getDisplayAttribute(): string
    {
        $item = $this->inventoryItem;
        $absQty = abs((float) $this->quantity_change);

        if ($item && $item->type === 'powder') {
            $qtyStr = number_format($absQty * 15.4324, 0) . ' grains (' . number_format($absQty, 1) . 'g)';
        } else {
            $qtyStr = number_format($absQty, 0) . ' units';
        }

        return match ($this->type) {
            'usage' => "Used {$qtyStr}" . ($this->rounds ? " ({$this->rounds} rounds)" : '') . ($this->source ? " — {$this->source}" : ''),
            'restock' => "Restocked +{$qtyStr}" . ($this->source ? " — {$this->source}" : ''),
            'adjustment' => ((float) $this->quantity_change >= 0 ? "Added {$qtyStr}" : "Removed {$qtyStr}") . ($this->reason ? " — {$this->reason}" : ''),
            'ladder_test' => "Ladder test used {$qtyStr}" . ($this->rounds ? " ({$this->rounds} rounds)" : '') . ($this->source ? " — {$this->source}" : ''),
            default => "{$qtyStr}",
        };
    }

    /**
     * Get the type badge color.
     */
    public function getBadgeColorAttribute(): string
    {
        return match ($this->type) {
            'usage' => 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400',
            'restock' => 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400',
            'adjustment' => 'text-purple-600 bg-purple-50 dark:bg-purple-900/20 dark:text-purple-400',
            'ladder_test' => 'text-nrapa-orange bg-nrapa-orange/10 dark:text-nrapa-orange',
            default => 'text-zinc-600 bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-400',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'usage' => 'Loaded',
            'restock' => 'Restock',
            'adjustment' => 'Adjust',
            'ladder_test' => 'Ladder',
            default => ucfirst($this->type),
        };
    }

    /**
     * Static helper to log an inventory change.
     */
    public static function record(
        int $inventoryId,
        int $userId,
        string $type,
        float $quantityChange,
        ?int $rounds = null,
        ?string $source = null,
        ?int $sourceId = null,
        ?string $sourceType = null,
        ?string $reason = null,
        ?string $loggedAt = null,
    ): self {
        $item = ReloadingInventory::find($inventoryId);

        return self::create([
            'reloading_inventory_id' => $inventoryId,
            'user_id' => $userId,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'balance_after' => $item ? (float) $item->quantity : null,
            'rounds' => $rounds,
            'source' => $source,
            'source_id' => $sourceId,
            'source_type' => $sourceType,
            'reason' => $reason,
            'logged_at' => $loggedAt ?? now()->toDateString(),
        ]);
    }
}
