<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EndorsementFirearm extends Model
{
    // Firearm category constants (SAPS 271 Form Section E compliant)
    public const CATEGORY_RIFLE = 'rifle';

    public const CATEGORY_SELF_LOADING_RIFLE = 'self_loading_rifle';

    public const CATEGORY_SHOTGUN = 'shotgun';

    public const CATEGORY_HANDGUN = 'handgun';

    public const CATEGORY_COMBINATION = 'combination';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORY_BARREL = 'barrel';

    public const CATEGORY_ACTION = 'action';

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
     * Aligned with SAPS 271 Form Section E - Description of Firearm
     */
    protected $fillable = [
        'uuid',
        'endorsement_request_id',
        'firearm_category',          // 1. Type of Firearm (SAPS 271: rifle|shotgun|handgun|combination|other|barrel|action)
        'firearm_type_other',        // Specification when firearm_category = 'other'
        'component_diameter',        // Barrel diameter for barrel component endorsements
        'ignition_type',
        'action_type',               // 1.1 Action
        'action_other_specify',      // 1.1 Other action (specify)
        'metal_engraving',           // 1.2 Names and addresses engraved in the metal
        'calibre_id',                // 1.3 Calibre
        'calibre_manual',            // 1.3 Calibre (manual entry)
        'calibre_code',              // 1.4 Calibre code
        'make',                      // 1.5 Make
        'model',                     // 1.6 Model
        'serial_number',             // Legacy - general serial
        'barrel_serial_number',      // 1.7 Barrel serial number
        'barrel_make',               // 1.8 Barrel Make
        'frame_serial_number',       // 1.9 Frame serial number
        'frame_make',                // 1.10 Frame Make
        'receiver_serial_number',    // 1.11 Receiver serial number
        'receiver_make',             // 1.12 Receiver Make
        'licence_section',
        'saps_reference',
        'licence_expiry_date',
        'user_firearm_id',
        // New reference fields
        'firearm_calibre_id',
        'firearm_make_id',
        'firearm_model_id',
        'calibre_text_override',
        'make_text_override',
        'model_text_override',
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

    // Legacy calibre relationship removed - use firearmCalibre() instead

    /**
     * Get the firearm calibre reference.
     */
    public function firearmCalibre(): BelongsTo
    {
        return $this->belongsTo(FirearmCalibre::class, 'firearm_calibre_id');
    }

    /**
     * Get the firearm make reference.
     */
    public function firearmMake(): BelongsTo
    {
        return $this->belongsTo(FirearmMake::class, 'firearm_make_id');
    }

    /**
     * Get the firearm model reference.
     */
    public function firearmModel(): BelongsTo
    {
        return $this->belongsTo(FirearmModel::class, 'firearm_model_id');
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
            self::CATEGORY_RIFLE => 'Rifle (Manually Operated)',
            self::CATEGORY_SELF_LOADING_RIFLE => 'Self-Loading Rifle (S/L Rifle)',
            self::CATEGORY_SHOTGUN => 'Shotgun',
            self::CATEGORY_HANDGUN => 'Handgun',
            self::CATEGORY_BARREL => 'Main Firearm Component',
            self::CATEGORY_ACTION => 'Action (component)',
        ];
    }

    /**
     * Check if a category is a component (barrel or action) rather than a full firearm.
     */
    public static function isComponentCategory(?string $category): bool
    {
        return in_array($category, [self::CATEGORY_BARREL, self::CATEGORY_ACTION]);
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
            self::ACTION_BOLT_ACTION => 'Bolt Action',
            self::ACTION_LEVER_ACTION => 'Lever Action',
            self::ACTION_PUMP_ACTION => 'Pump Action',
            self::ACTION_BREAK_ACTION => 'Break Action',
            self::ACTION_OTHER => 'Other',
        ];

        if ($category === self::CATEGORY_RIFLE) {
            return [
                self::ACTION_BOLT_ACTION => 'Bolt Action',
                self::ACTION_LEVER_ACTION => 'Lever Action',
                self::ACTION_SINGLE_SHOT => 'Single Shot',
                self::ACTION_PUMP_ACTION => 'Pump Action',
                self::ACTION_OTHER => 'Other',
            ];
        }

        if ($category === self::CATEGORY_SELF_LOADING_RIFLE) {
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

        if ($category === self::CATEGORY_HANDGUN) {
            // Handguns use "Cylinder" as the action label for revolvers (per Riaan): the
            // mechanical action of a revolver is the rotating cylinder, not "revolver" itself.
            return [
                self::ACTION_REVOLVER => 'Cylinder',
                self::ACTION_SEMI_AUTO => 'Semi-Automatic',
                self::ACTION_SINGLE_SHOT => 'Single Shot',
                self::ACTION_OTHER => 'Other',
            ];
        }

        if ($category === self::CATEGORY_COMBINATION) {
            return $allOptions; // All actions possible for combination firearms
        }

        if ($category === self::CATEGORY_OTHER) {
            return $allOptions; // All actions possible for other types
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
        // Map SAPS 271 firearm categories to calibre categories for filtering
        // Note: This is for legacy Calibre system - new system uses FirearmCalibre
        return match ($firearmCategory) {
            self::CATEGORY_RIFLE, self::CATEGORY_SELF_LOADING_RIFLE, self::CATEGORY_COMBINATION => 'rifle',
            self::CATEGORY_SHOTGUN => 'shotgun',
            self::CATEGORY_HANDGUN => 'handgun',
            self::CATEGORY_OTHER => null,
            default => null,
        };
    }

    // ===== Validation =====

    /**
     * Check if at least one serial number is provided (SAPS requirement).
     */
    public function hasAtLeastOneSerialNumber(): bool
    {
        return ! empty($this->serial_number)
            || ! empty($this->barrel_serial_number)
            || ! empty($this->frame_serial_number)
            || ! empty($this->receiver_serial_number);
    }

    /**
     * Check if a serial/make value is actually meaningful (not null, empty, or placeholder text).
     */
    protected static function isRealValue(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return ! in_array(strtolower(trim($value)), ['none', 'n/a', 'na', '-', 'null', 'unknown']);
    }

    /**
     * Get all serial numbers as array.
     * Always includes barrel/frame/receiver headings (SAPS fields) but
     * cleans placeholder values like "None" or "N/A" to null.
     */
    public function getSerialNumbersAttribute(): array
    {
        $serials = [];

        $fields = [
            'barrel' => ['serial' => $this->barrel_serial_number, 'make' => $this->barrel_make],
            'frame' => ['serial' => $this->frame_serial_number, 'make' => $this->frame_make],
            'receiver' => ['serial' => $this->receiver_serial_number, 'make' => $this->receiver_make],
        ];

        foreach ($fields as $type => $data) {
            if (! empty($data['serial'])) {
                $serials[$type] = [
                    'serial' => static::isRealValue($data['serial']) ? $data['serial'] : null,
                    'make' => static::isRealValue($data['make']) ? $data['make'] : null,
                ];
            }
        }

        // Legacy serial number
        if (static::isRealValue($this->serial_number) && empty($serials)) {
            $serials['general'] = [
                'serial' => $this->serial_number,
                'make' => null,
            ];
        }

        return $serials;
    }

    /**
     * Get validation rules for SAPS 271 submission.
     */
    public static function getSaps271ValidationRules(): array
    {
        return [
            'firearm_category' => 'required',
            'action_type' => 'required',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            // At least one of these serial numbers required
            'has_serial' => 'required', // Custom validation in controller
        ];
    }

    // ===== Accessors =====

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        $label = self::getCategoryOptions()[$this->firearm_category] ?? ucfirst($this->firearm_category);

        if ($this->firearm_category === self::CATEGORY_OTHER && $this->firearm_type_other) {
            return "{$label} ({$this->firearm_type_other})";
        }

        if ($this->firearm_category === self::CATEGORY_BARREL) {
            return "{$label} (Barrel)";
        }

        if ($this->firearm_category === self::CATEGORY_ACTION) {
            return "{$label} (Receiver)";
        }

        return $label;
    }

    /**
     * Get the ignition type label.
     */
    public function getIgnitionTypeLabelAttribute(): ?string
    {
        if (! $this->ignition_type) {
            return null;
        }

        return self::getIgnitionTypeOptions()[$this->ignition_type] ?? ucfirst($this->ignition_type);
    }

    /**
     * Get the action type label.
     *
     * Resolved against the firearm's category so category-specific labels apply —
     * e.g. a handgun revolver is displayed as "Cylinder" rather than "Revolver".
     */
    public function getActionTypeLabelAttribute(): ?string
    {
        if (! $this->action_type) {
            return null;
        }

        $options = self::getActionTypeOptions($this->firearm_category);

        return $options[$this->action_type]
            ?? self::getActionTypeOptions()[$this->action_type]
            ?? ucfirst(str_replace('_', ' ', $this->action_type));
    }

    /**
     * Get the licence section label.
     */
    public function getLicenceSectionLabelAttribute(): ?string
    {
        if (! $this->licence_section) {
            return null;
        }

        return self::getLicenceSectionOptions()[$this->licence_section] ?? 'Section '.$this->licence_section;
    }

    /**
     * Get the calibre display name.
     */
    public function getCalibreDisplayAttribute(): ?string
    {
        // Prefer new reference system
        if ($this->firearmCalibre) {
            return $this->firearmCalibre->name;
        }
        // Fallback to legacy calibre
        if ($this->calibre) {
            return $this->calibre->name;
        }

        // Fallback to override or manual
        return $this->calibre_text_override ?? $this->calibre_manual;
    }

    /**
     * Get the make display name.
     */
    public function getMakeDisplayAttribute(): ?string
    {
        if ($this->firearmMake) {
            return $this->firearmMake->name;
        }

        return $this->make_text_override ?? $this->make;
    }

    /**
     * Get the model display name.
     */
    public function getModelDisplayAttribute(): ?string
    {
        if ($this->firearmModel) {
            return $this->firearmModel->name;
        }

        return $this->model_text_override ?? $this->model;
    }

    /**
     * Get a summary description.
     */
    public function getSummaryAttribute(): string
    {
        $parts = [];

        if ($this->make) {
            $parts[] = $this->make;
        }
        if ($this->model) {
            $parts[] = $this->model;
        }

        if (empty($parts)) {
            $parts[] = $this->category_label;
        }

        if ($this->calibre_display) {
            $parts[] = '('.$this->calibre_display.')';
        }

        return implode(' ', $parts);
    }

    /**
     * Get SAPS 271 canonical firearm identity.
     * If linked to UserFirearm, pulls from canonical source; otherwise uses stored fields.
     */
    public function getSaps271IdentityAttribute(): string
    {
        // If linked to canonical firearm, use that
        if ($this->user_firearm_id && $this->userFirearm) {
            return $this->userFirearm->saps_271_identity;
        }

        // Otherwise build from stored fields
        $parts = [];

        // Type
        if ($this->firearm_category) {
            $parts[] = $this->category_label;
        }

        // Action
        if ($this->action_type) {
            $parts[] = $this->action_type_label;
        }

        // Calibre
        if ($this->calibre_display) {
            $parts[] = $this->calibre_display;
            if ($this->calibre_code) {
                $parts[] = "({$this->calibre_code})";
            }
        } elseif ($this->calibre_code) {
            $parts[] = $this->calibre_code;
        }

        // Make/Model
        if ($this->make) {
            $parts[] = $this->make;
        }
        if ($this->model) {
            $parts[] = $this->model;
        }

        // Serial numbers
        $serials = [];
        if ($this->barrel_serial_number) {
            $serials[] = "Barrel: {$this->barrel_serial_number}";
        }
        if ($this->frame_serial_number) {
            $serials[] = "Frame: {$this->frame_serial_number}";
        }
        if ($this->receiver_serial_number) {
            $serials[] = "Receiver: {$this->receiver_serial_number}";
        }
        if (empty($serials) && $this->serial_number) {
            $serials[] = "Serial: {$this->serial_number}";
        }

        if (! empty($serials)) {
            $parts[] = implode(', ', $serials);
        }

        return implode(' - ', $parts);
    }
}
