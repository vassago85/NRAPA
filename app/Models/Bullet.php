<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bullet extends Model
{
    protected $fillable = [
        'manufacturer', 'brand_line', 'bullet_label', 'caliber_label',
        'weight_gr', 'diameter_in', 'diameter_mm', 'length_in', 'length_mm',
        'bc_g1', 'bc_g7', 'bc_reference', 'construction', 'intended_use',
        'twist_note', 'sku_or_part_no', 'source_url', 'status', 'last_verified_at',
    ];

    protected $casts = [
        'weight_gr' => 'integer',
        'diameter_in' => 'decimal:3',
        'diameter_mm' => 'decimal:3',
        'length_in' => 'decimal:3',
        'length_mm' => 'decimal:3',
        'bc_g1' => 'decimal:3',
        'bc_g7' => 'decimal:3',
        'last_verified_at' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function sources(): HasMany
    {
        return $this->hasMany(BulletSource::class)->orderByDesc('captured_at');
    }

    // ── Unit Conversion ─────────────────────────────────────────

    /**
     * Convert inches to mm: mm = in * 25.4
     */
    public static function inToMm(float $inches): float
    {
        return round($inches * 25.4, 3);
    }

    /**
     * Convert mm to inches: in = mm / 25.4
     */
    public static function mmToIn(float $mm): float
    {
        return round($mm / 25.4, 3);
    }

    /**
     * Set diameter from inches, auto-calc mm.
     */
    public function setDiameterFromIn(float $inches): void
    {
        $this->diameter_in = round($inches, 3);
        $this->diameter_mm = self::inToMm($inches);
    }

    /**
     * Set diameter from mm, auto-calc inches.
     */
    public function setDiameterFromMm(float $mm): void
    {
        $this->diameter_mm = round($mm, 3);
        $this->diameter_in = self::mmToIn($mm);
    }

    /**
     * Set length from inches, auto-calc mm.
     */
    public function setLengthFromIn(?float $inches): void
    {
        if ($inches === null) {
            $this->length_in = null;
            $this->length_mm = null;

            return;
        }
        $this->length_in = round($inches, 3);
        $this->length_mm = self::inToMm($inches);
    }

    /**
     * Set length from mm, auto-calc inches.
     */
    public function setLengthFromMm(?float $mm): void
    {
        if ($mm === null) {
            $this->length_mm = null;
            $this->length_in = null;

            return;
        }
        $this->length_mm = round($mm, 3);
        $this->length_in = self::mmToIn($mm);
    }

    /**
     * Validate that stored in/mm values match within tolerance.
     */
    public static function unitsMatch(float $inches, float $mm, float $toleranceMm = 0.001): bool
    {
        $expectedMm = $inches * 25.4;

        return abs($expectedMm - $mm) <= $toleranceMm;
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getDropdownLabelAttribute(): string
    {
        $parts = [$this->manufacturer, $this->bullet_label];
        if ($this->twist_note) {
            $parts[] = "({$this->twist_note})";
        }

        return implode(' ', $parts);
    }

    public function getShortLabelAttribute(): string
    {
        return "{$this->weight_gr}gr {$this->brand_line}";
    }

    public function getDiameterDisplayAttribute(): string
    {
        return "{$this->diameter_in}\" / {$this->diameter_mm}mm";
    }

    public function getLengthDisplayAttribute(): ?string
    {
        if (! $this->length_in && ! $this->length_mm) {
            return null;
        }

        return "{$this->length_in}\" / {$this->length_mm}mm";
    }

    public function getBcDisplayAttribute(): ?string
    {
        $parts = [];
        if ($this->bc_g1) {
            $parts[] = "G1: {$this->bc_g1}";
        }
        if ($this->bc_g7) {
            $parts[] = "G7: {$this->bc_g7}";
        }
        if (empty($parts)) {
            return null;
        }
        $display = implode(' / ', $parts);
        if ($this->bc_reference) {
            $display .= " ({$this->bc_reference})";
        }

        return $display;
    }

    // ── Static Lookups ──────────────────────────────────────────

    public static function constructionTypes(): array
    {
        return [
            'cup_and_core' => 'Cup & Core',
            'bonded' => 'Bonded',
            'monolithic_copper' => 'Monolithic Copper',
            'solid' => 'Solid',
            'fmj' => 'FMJ',
            'otm' => 'OTM',
            'frangible' => 'Frangible',
            'other' => 'Other',
        ];
    }

    public static function intendedUses(): array
    {
        return [
            'match' => 'Match',
            'hunting' => 'Hunting',
            'varmint' => 'Varmint',
            'tactical' => 'Tactical',
            'fmj' => 'FMJ / Range',
            'other' => 'Other',
        ];
    }

    public static function statuses(): array
    {
        return [
            'active' => 'Active',
            'discontinued' => 'Discontinued',
            'unknown' => 'Unknown',
        ];
    }

    public static function caliberDiameters(): array
    {
        return [
            '17 Cal' => ['in' => 0.172, 'mm' => 4.369],
            '20 Cal' => ['in' => 0.204, 'mm' => 5.182],
            '22 Cal' => ['in' => 0.224, 'mm' => 5.690],
            '6mm' => ['in' => 0.243, 'mm' => 6.172],
            '25 Cal' => ['in' => 0.257, 'mm' => 6.528],
            '6.5mm' => ['in' => 0.264, 'mm' => 6.706],
            '270 Cal' => ['in' => 0.277, 'mm' => 7.036],
            '7mm' => ['in' => 0.284, 'mm' => 7.214],
            '30 Cal' => ['in' => 0.308, 'mm' => 7.823],
            '8mm' => ['in' => 0.323, 'mm' => 8.204],
            '338 Cal' => ['in' => 0.338, 'mm' => 8.585],
            '35 Cal' => ['in' => 0.358, 'mm' => 9.093],
            '375 Cal' => ['in' => 0.375, 'mm' => 9.525],
            '416 Cal' => ['in' => 0.416, 'mm' => 10.566],
            '458 Cal' => ['in' => 0.458, 'mm' => 11.633],
            '50 Cal' => ['in' => 0.510, 'mm' => 12.954],
        ];
    }

    /**
     * Lookup diameter from caliber label.
     */
    public static function diameterForCaliber(string $caliberLabel): ?array
    {
        return static::caliberDiameters()[$caliberLabel] ?? null;
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeForCaliber($query, string $caliberLabel)
    {
        return $query->where('caliber_label', $caliberLabel);
    }

    public function scopeForManufacturer($query, string $manufacturer)
    {
        return $query->where('manufacturer', $manufacturer);
    }

    public function scopeForUse($query, string $use)
    {
        return $query->where('intended_use', $use);
    }

    public function scopeForConstruction($query, string $construction)
    {
        return $query->where('construction', $construction);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHasBc($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('bc_g1')->orWhereNotNull('bc_g7');
        });
    }

    public function scopeHasLength($query)
    {
        return $query->whereNotNull('length_in');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('bullet_label', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%")
                ->orWhere('brand_line', 'like', "%{$search}%")
                ->orWhere('sku_or_part_no', 'like', "%{$search}%");
        });
    }

    /**
     * Weight range scope.
     */
    public function scopeWeightBetween($query, ?int $min, ?int $max)
    {
        if ($min) {
            $query->where('weight_gr', '>=', $min);
        }
        if ($max) {
            $query->where('weight_gr', '<=', $max);
        }

        return $query;
    }

    // ── Validation Rules ────────────────────────────────────────

    public static function validationRules(bool $isUpdate = false): array
    {
        return [
            'manufacturer' => ['required', 'string', 'max:64'],
            'brand_line' => ['required', 'string', 'max:64'],
            'bullet_label' => ['required', 'string', 'max:128'],
            'caliber_label' => ['required', 'string', 'max:32'],
            'weight_gr' => ['required', 'integer', 'min:10', 'max:1000'],
            'diameter_in' => ['required', 'numeric', 'min:0.100', 'max:1.000'],
            'diameter_mm' => ['required', 'numeric', 'min:2.000', 'max:26.000'],
            'length_in' => ['nullable', 'numeric', 'min:0.100', 'max:5.000'],
            'length_mm' => ['nullable', 'numeric', 'min:2.000', 'max:130.000'],
            'bc_g1' => ['nullable', 'numeric', 'min:0.010', 'max:2.000'],
            'bc_g7' => ['nullable', 'numeric', 'min:0.010', 'max:2.000'],
            'bc_reference' => ['nullable', 'string', 'max:32'],
            'construction' => ['required', 'in:'.implode(',', array_keys(self::constructionTypes()))],
            'intended_use' => ['required', 'in:'.implode(',', array_keys(self::intendedUses()))],
            'twist_note' => ['nullable', 'string', 'max:64'],
            'sku_or_part_no' => ['nullable', 'string', 'max:64'],
            'source_url' => ['required', 'string', 'max:255', 'url'],
            'status' => ['required', 'in:active,discontinued,unknown'],
            'last_verified_at' => ['required', 'date'],
        ];
    }
}
