<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EndorsementComponent extends Model
{
    // Component type constants (only barrel, action, and receiver are allowed)
    public const TYPE_BARREL = 'barrel';
    public const TYPE_ACTION = 'action';
    public const TYPE_RECEIVER = 'receiver';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'endorsement_request_id',
        'component_type',
        'component_description',
        'component_serial',
        'component_make',
        'component_model',
        'calibre_id',
        'calibre_manual',
        'diameter', // Barrel diameter (required before chambering)
        'relates_to_firearm',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'relates_to_firearm' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EndorsementComponent $component) {
            if (empty($component->uuid)) {
                $component->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ===== Relationships =====

    /**
     * Get the endorsement request.
     */
    public function endorsementRequest(): BelongsTo
    {
        return $this->belongsTo(EndorsementRequest::class);
    }

    // Legacy calibre relationship removed - EndorsementComponent uses calibre_id for legacy data only

    // ===== Static Options =====

    /**
     * Get component type options.
     * Only barrel, action, and receiver are allowed.
     */
    public static function getComponentTypeOptions(): array
    {
        return [
            self::TYPE_BARREL => 'Barrel',
            self::TYPE_ACTION => 'Action',
            self::TYPE_RECEIVER => 'Receiver',
        ];
    }

    /**
     * Check if this component type typically requires a calibre.
     */
    public static function requiresCalibre(string $componentType): bool
    {
        return $componentType === self::TYPE_BARREL;
    }

    /**
     * Check if this component type can have a diameter.
     * Barrels can specify either calibre or diameter (diameter for barrels before chambering).
     */
    public static function canHaveDiameter(string $componentType): bool
    {
        return $componentType === self::TYPE_BARREL;
    }

    // ===== Accessors =====

    /**
     * Get the component type label.
     */
    public function getComponentTypeLabelAttribute(): string
    {
        return self::getComponentTypeOptions()[$this->component_type] ?? ucfirst(str_replace('_', ' ', $this->component_type));
    }

    /**
     * Get the calibre display name.
     * Legacy calibre relationship removed - uses calibre_manual for display.
     */
    public function getCalibreDisplayAttribute(): ?string
    {
        // Legacy calibre_id is kept for backward compatibility but relationship removed
        // Use calibre_manual for display
        return $this->calibre_manual;
    }

    /**
     * Get a summary description.
     */
    public function getSummaryAttribute(): string
    {
        $parts = [$this->component_type_label];
        
        if ($this->component_make) {
            $parts[] = $this->component_make;
        }
        
        if ($this->component_model) {
            $parts[] = $this->component_model;
        }
        
        if ($this->calibre_display) {
            $parts[] = '(' . $this->calibre_display . ')';
        }

        return implode(' - ', $parts);
    }
}
