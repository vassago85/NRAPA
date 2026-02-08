<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReloadingInventory extends Model
{
    protected $table = 'reloading_inventories';

    protected $fillable = [
        'user_id',
        'type',
        'make',
        'name',
        'bullet_weight',
        'bullet_bc',
        'bullet_bc_type',
        'bullet_type',
        'calibre',
        'quantity',
        'unit',
        'cost_per_unit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:4',
        'bullet_weight' => 'decimal:1',
        'bullet_bc' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(InventoryPurchase::class, 'reloading_inventory_id')->orderByDesc('purchased_at');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = "{$this->make} {$this->name}";
        if ($this->type === 'bullet' && $this->bullet_weight) {
            $name .= " {$this->bullet_weight}gr";
            if ($this->bullet_type) {
                $name .= " {$this->bullet_type}";
            }
        }
        return $name;
    }

    /**
     * Friendly stock display (e.g., "1,200g" or "350 units").
     */
    public function getStockDisplayAttribute(): string
    {
        if ($this->type === 'powder') {
            return number_format($this->quantity, 0) . 'g';
        }
        return number_format($this->quantity, 0);
    }

    /**
     * Get the latest purchase record.
     */
    public function getLatestPurchaseAttribute(): ?InventoryPurchase
    {
        return $this->purchases->first();
    }

    /**
     * Friendly price display based on type.
     * Powder: shows R/lb, Primers/Bullets/Brass: shows R per box of 100/50.
     */
    public function getFriendlyPriceAttribute(): ?string
    {
        if (!$this->cost_per_unit || $this->cost_per_unit <= 0) {
            return null;
        }

        return match ($this->type) {
            'powder' => '~R' . number_format($this->cost_per_unit * 453.592, 0) . '/lb',
            'primer' => 'R' . number_format($this->cost_per_unit * 100, 0) . '/100',
            'bullet' => 'R' . number_format($this->cost_per_unit * 100, 0) . '/100',
            'brass'  => 'R' . number_format($this->cost_per_unit * 50, 0) . '/50',
            default  => 'R' . number_format($this->cost_per_unit, 2) . '/unit',
        };
    }

    /**
     * Get the price per unit as stored for load data:
     * Powder -> R/kg, Others -> R/unit.
     */
    public function getPriceForLoadAttribute(): ?float
    {
        if (!$this->cost_per_unit || $this->cost_per_unit <= 0) {
            return null;
        }

        if ($this->type === 'powder') {
            // cost_per_unit is R/gram, load_data stores R/kg
            return round($this->cost_per_unit * 1000, 2);
        }

        // primers, bullets, brass: cost_per_unit is already R/unit
        return round((float) $this->cost_per_unit, 2);
    }

    /**
     * Get dropdown display for load data forms.
     */
    public function getLoadDropdownLabelAttribute(): string
    {
        $label = $this->display_name;
        $stock = $this->stock_display;
        $price = $this->friendly_price;

        $parts = ["{$label} ({$stock} in stock)"];
        if ($price) {
            $parts[] = "-- {$price}";
        }

        return implode(' ', $parts);
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

    public static function bulletTypes(): array
    {
        return [
            'HPBT' => 'HPBT (Hollow Point Boat Tail)',
            'HP' => 'HP (Hollow Point)',
            'SP' => 'SP (Soft Point)',
            'FMJ' => 'FMJ (Full Metal Jacket)',
            'OTM' => 'OTM (Open Tip Match)',
            'ELD-X' => 'ELD-X',
            'ELD-M' => 'ELD-M (Match)',
            'A-TIP' => 'A-TIP (Match)',
            'ABLR' => 'AccuBond Long Range',
            'AB' => 'AccuBond',
            'PT' => 'Partition',
            'TSX' => 'TSX (Triple Shock)',
            'TTSX' => 'TTSX (Tipped Triple Shock)',
            'RN' => 'RN (Round Nose)',
            'other' => 'Other',
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

    /**
     * Default purchase unit options by type.
     */
    public static function purchaseUnits(): array
    {
        return [
            'powder' => [
                ['value' => '453.592', 'label' => '1 lb bottle', 'tag' => '1lb'],
                ['value' => '500', 'label' => '500g tin', 'tag' => '500g'],
                ['value' => '1000', 'label' => '1 kg tin', 'tag' => '1kg'],
                ['value' => '3628.74', 'label' => '8 lb jug', 'tag' => '8lb'],
            ],
            'primer' => [
                ['value' => '100', 'label' => 'Box of 100', 'tag' => '100'],
                ['value' => '1000', 'label' => 'Brick of 1000', 'tag' => '1000'],
                ['value' => '1', 'label' => 'Per unit', 'tag' => '1'],
            ],
            'bullet' => [
                ['value' => '100', 'label' => 'Box of 100', 'tag' => '100'],
                ['value' => '50', 'label' => 'Box of 50', 'tag' => '50'],
                ['value' => '1', 'label' => 'Per unit', 'tag' => '1'],
            ],
            'brass' => [
                ['value' => '50', 'label' => 'Box of 50', 'tag' => '50'],
                ['value' => '100', 'label' => 'Box of 100', 'tag' => '100'],
                ['value' => '20', 'label' => 'Box of 20', 'tag' => '20'],
                ['value' => '1', 'label' => 'Per unit', 'tag' => '1'],
            ],
        ];
    }
}
