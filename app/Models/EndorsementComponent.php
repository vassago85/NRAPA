<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EndorsementComponent extends Model
{
    // Component type constants
    public const TYPE_BARREL = 'barrel';
    public const TYPE_ACTION = 'action';
    public const TYPE_BOLT = 'bolt';
    public const TYPE_RECEIVER = 'receiver';
    public const TYPE_FRAME = 'frame';
    public const TYPE_SLIDE = 'slide';
    public const TYPE_CYLINDER = 'cylinder';
    public const TYPE_TRIGGER_GROUP = 'trigger_group';
    public const TYPE_OTHER = 'other';

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

    /**
     * Get the calibre (for barrels).
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class);
    }

    // ===== Static Options =====

    /**
     * Get component type options.
     */
    public static function getComponentTypeOptions(): array
    {
        return [
            self::TYPE_BARREL => 'Barrel',
            self::TYPE_ACTION => 'Action',
            self::TYPE_BOLT => 'Bolt',
            self::TYPE_RECEIVER => 'Receiver',
            self::TYPE_FRAME => 'Frame',
            self::TYPE_SLIDE => 'Slide',
            self::TYPE_CYLINDER => 'Cylinder',
            self::TYPE_TRIGGER_GROUP => 'Trigger Group',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Check if this component type typically requires a calibre.
     */
    public static function requiresCalibre(string $componentType): bool
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
     */
    public function getCalibreDisplayAttribute(): ?string
    {
        if ($this->calibre) {
            return $this->calibre->name;
        }
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
