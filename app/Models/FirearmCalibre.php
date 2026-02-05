<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FirearmCalibre extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'category',
        'ignition',
        'family',
        'bullet_diameter_mm',
        'case_length_mm',
        'parent',
        'is_wildcat',
        'is_obsolete',
        'is_active',
        'tags',
    ];

    protected $casts = [
        'bullet_diameter_mm' => 'decimal:2',
        'case_length_mm' => 'decimal:2',
        'is_wildcat' => 'boolean',
        'is_obsolete' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($calibre) {
            if (empty($calibre->normalized_name)) {
                $calibre->normalized_name = static::normalize($calibre->name);
            }
        });

        static::updating(function ($calibre) {
            if ($calibre->isDirty('name') && empty($calibre->normalized_name)) {
                $calibre->normalized_name = static::normalize($calibre->name);
            }
        });
    }

    /**
     * Normalize a calibre name for searching.
     */
    public static function normalize(string $name): string
    {
        // Remove common prefixes/suffixes and normalize
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = str_replace(['.', '-', '/'], '', $normalized);
        return $normalized;
    }

    /**
     * Get aliases for this calibre.
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(FirearmCalibreAlias::class, 'firearm_calibre_id');
    }

    /**
     * Scope to only active calibres.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by name alphabetically.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to exclude obsolete calibres.
     */
    public function scopeNotObsolete($query)
    {
        return $query->where('is_obsolete', false);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeForCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Search calibres by name or aliases.
     */
    public function scopeSearch($query, string $term)
    {
        $normalizedTerm = static::normalize($term);
        
        return $query->where(function ($q) use ($term, $normalizedTerm) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('normalized_name', 'LIKE', "%{$normalizedTerm}%")
              ->orWhereHas('aliases', function ($aliasQuery) use ($term, $normalizedTerm) {
                  $aliasQuery->where('alias', 'LIKE', "%{$term}%")
                            ->orWhere('normalized_alias', 'LIKE', "%{$normalizedTerm}%");
              });
        });
    }

    /**
     * Get display name with family/parent info.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->name];
        
        if ($this->family) {
            $parts[] = "({$this->family})";
        }
        
        return implode(' ', $parts);
    }

    /**
     * Get category label (firearm type).
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'handgun' => 'Handgun',
            'rifle' => 'Rifle',
            'shotgun' => 'Shotgun',
            'muzzleloader' => 'Muzzleloader',
            'historic' => 'Historic',
            default => ucfirst($this->category ?? 'Unknown'),
        };
    }
    
    /**
     * Get ignition label.
     */
    public function getIgnitionLabelAttribute(): string
    {
        return match($this->ignition) {
            'rimfire' => 'Rimfire',
            'centerfire' => 'Centerfire',
            default => ucfirst($this->ignition ?? 'Unknown'),
        };
    }
    
    /**
     * Scope to filter by ignition type.
     */
    public function scopeForIgnition($query, ?string $ignition)
    {
        if ($ignition) {
            return $query->where('ignition', $ignition);
        }
        return $query;
    }

    /**
     * Check if this calibre is imperial (starts with decimal point).
     */
    public function isImperial(): bool
    {
        return str_starts_with(trim($this->name), '.');
    }

    /**
     * Get bullet diameter in appropriate units.
     * Returns array with 'value' and 'unit' keys.
     */
    public function getBulletDiameterDisplayAttribute(): ?array
    {
        if (!$this->bullet_diameter_mm) {
            return null;
        }

        if ($this->isImperial()) {
            // Convert mm to inches (1 inch = 25.4 mm)
            $inches = round($this->bullet_diameter_mm / 25.4, 3);
            return [
                'value' => $inches,
                'unit' => 'in'
            ];
        }

        return [
            'value' => $this->bullet_diameter_mm,
            'unit' => 'mm'
        ];
    }
}
