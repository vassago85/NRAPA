<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EndorsementFirearm extends Model
{
    // Firearm category constants (aligned with SAPS)
    public const CATEGORY_HANDGUN = 'handgun';
    public const CATEGORY_RIFLE_MANUAL = 'rifle_manual';
    public const CATEGORY_RIFLE_SELF_LOADING = 'rifle_self_loading';
    public const CATEGORY_SHOTGUN = 'shotgun';

    // Ignition type constants
    public const IGNITION_RIMFIRE = 'rimfire';
    public const IGNITION_CENTERFIRE = 'centerfire';

    // Action type constants
    public const ACTION_SINGLE_SHOT = 'single_shot';
    public const ACTION_REVOLVER = 'revolver';
    public const ACTION_SEMI_AUTO = 'semi_auto';
    public const ACTION_BOLT_ACTION = 'bolt_action';
    public const ACTION_LEVER_ACTION = 'lever_action';
    public const ACTION_PUMP_ACTION = 'pump_action';
    public const ACTION_BREAK_ACTION = 'break_action';
    public const ACTION_OTHER = 'other';

    // Licence section constants
    public const LICENCE_SECTION_13 = '13';
    public const LICENCE_SECTION_15 = '15';
    public const LICENCE_SECTION_16 = '16';
    public const LICENCE_SECTION_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'endorsement_request_id',
        'firearm_category',
        'ignition_type',
        'action_type',
        'calibre_id',
        'calibre_manual',
        'make',
        'model',
        'serial_number',
        'licence_section',
        'saps_reference',
        'licence_expiry_date',
        'user_firearm_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'licence_expiry_date' => 'date',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EndorsementFirearm $firearm) {
            if (empty($firearm->uuid)) {
                $firearm->uuid = (string) Str::uuid();
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
     * Get the calibre.
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class);
    }

    /**
     * Get the linked user firearm.
     */
    public function userFirearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class);
    }

    // ===== Static Options =====

    /**
     * Get firearm category options.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_HANDGUN => 'Handgun',
            self::CATEGORY_RIFLE_MANUAL => 'Rifle (Manual)',
            self::CATEGORY_RIFLE_SELF_LOADING => 'Rifle (Self-loading)',
            self::CATEGORY_SHOTGUN => 'Shotgun',
        ];
    }

    /**
     * Get ignition type options.
     */
    public static function getIgnitionTypeOptions(): array
    {
        return [
            self::IGNITION_RIMFIRE => 'Rimfire',
            self::IGNITION_CENTERFIRE => 'Centerfire',
        ];
    }

    /**
     * Get action type options based on firearm category.
     */
    public static function getActionTypeOptions(?string $category = null): array
    {
        $allOptions = [
            self::ACTION_SINGLE_SHOT => 'Single Shot',
            self::ACTION_REVOLVER => 'Revolver',
            self::ACTION_SEMI_AUTO => 'Semi-Automatic',
            self::ACTION_BOLT_ACTION => 'Bolt Action',
            self::ACTION_LEVER_ACTION => 'Lever Action',
            self::ACTION_PUMP_ACTION => 'Pump Action',
            self::ACTION_BREAK_ACTION => 'Break Action',
            self::ACTION_OTHER => 'Other',
        ];

        if ($category === self::CATEGORY_HANDGUN) {
            return [
                self::ACTION_REVOLVER => 'Revolver',
                self::ACTION_SEMI_AUTO => 'Semi-Automatic',
                self::ACTION_SINGLE_SHOT => 'Single Shot',
                self::ACTION_OTHER => 'Other',
            ];
        }

        if ($category === self::CATEGORY_RIFLE_MANUAL) {
            return [
                self::ACTION_BOLT_ACTION => 'Bolt Action',
                self::ACTION_LEVER_ACTION => 'Lever Action',
                self::ACTION_SINGLE_SHOT => 'Single Shot',
                self::ACTION_PUMP_ACTION => 'Pump Action',
                self::ACTION_OTHER => 'Other',
            ];
        }

        if ($category === self::CATEGORY_RIFLE_SELF_LOADING) {
            return [
                self::ACTION_SEMI_AUTO => 'Semi-Automatic',
                self::ACTION_OTHER => 'Other',
            ];
        }

        if ($category === self::CATEGORY_SHOTGUN) {
            return [
                self::ACTION_PUMP_ACTION => 'Pump Action',
                self::ACTION_SEMI_AUTO => 'Semi-Automatic',
                self::ACTION_BREAK_ACTION => 'Break Action',
                self::ACTION_BOLT_ACTION => 'Bolt Action',
                self::ACTION_LEVER_ACTION => 'Lever Action',
                self::ACTION_OTHER => 'Other',
            ];
        }

        return $allOptions;
    }

    /**
     * Get licence section options.
     */
    public static function getLicenceSectionOptions(): array
    {
        return [
            self::LICENCE_SECTION_13 => 'Section 13 (Self-defence)',
            self::LICENCE_SECTION_15 => 'Section 15 (Occasional hunting/sport)',
            self::LICENCE_SECTION_16 => 'Section 16 (Dedicated)',
            self::LICENCE_SECTION_OTHER => 'Other',
        ];
    }

    /**
     * Get calibre filter based on category.
     */
    public static function getCalibreCategoryFilter(string $firearmCategory): ?string
    {
        return match($firearmCategory) {
            self::CATEGORY_HANDGUN => Calibre::CATEGORY_HANDGUN,
            self::CATEGORY_RIFLE_MANUAL, self::CATEGORY_RIFLE_SELF_LOADING => Calibre::CATEGORY_RIFLE,
            self::CATEGORY_SHOTGUN => Calibre::CATEGORY_SHOTGUN,
            default => null,
        };
    }

    // ===== Accessors =====

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategoryOptions()[$this->firearm_category] ?? ucfirst($this->firearm_category);
    }

    /**
     * Get the ignition type label.
     */
    public function getIgnitionTypeLabelAttribute(): ?string
    {
        if (!$this->ignition_type) return null;
        return self::getIgnitionTypeOptions()[$this->ignition_type] ?? ucfirst($this->ignition_type);
    }

    /**
     * Get the action type label.
     */
    public function getActionTypeLabelAttribute(): ?string
    {
        if (!$this->action_type) return null;
        return self::getActionTypeOptions()[$this->action_type] ?? ucfirst(str_replace('_', ' ', $this->action_type));
    }

    /**
     * Get the licence section label.
     */
    public function getLicenceSectionLabelAttribute(): ?string
    {
        if (!$this->licence_section) return null;
        return self::getLicenceSectionOptions()[$this->licence_section] ?? 'Section ' . $this->licence_section;
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
        $parts = [];
        
        if ($this->make) $parts[] = $this->make;
        if ($this->model) $parts[] = $this->model;
        
        if (empty($parts)) {
            $parts[] = $this->category_label;
        }
        
        if ($this->calibre_display) {
            $parts[] = '(' . $this->calibre_display . ')';
        }

        return implode(' ', $parts);
    }
}
