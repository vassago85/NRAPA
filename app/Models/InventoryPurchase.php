<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPurchase extends Model
{
    protected $fillable = [
        'reloading_inventory_id',
        'quantity_purchased',
        'purchase_unit_size',
        'purchase_unit_label',
        'quantity_added',
        'price_paid',
        'price_per_base_unit',
        'purchased_at',
        'notes',
    ];

    protected $casts = [
        'quantity_purchased' => 'decimal:2',
        'purchase_unit_size' => 'decimal:2',
        'quantity_added' => 'decimal:2',
        'price_paid' => 'decimal:2',
        'price_per_base_unit' => 'decimal:4',
        'purchased_at' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(ReloadingInventory::class, 'reloading_inventory_id');
    }

    /**
     * Get a friendly display of the purchase (e.g., "1x 1lb bottle @ R2,000").
     */
    public function getDisplayAttribute(): string
    {
        $qty = (int) $this->quantity_purchased === (float) $this->quantity_purchased
            ? (int) $this->quantity_purchased
            : $this->quantity_purchased;

        return "{$qty}x {$this->purchase_unit_label} @ R".number_format($this->price_paid, 2);
    }
}
