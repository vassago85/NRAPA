<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FirearmType extends Model
{
    // Category constants
    public const CATEGORY_HANDGUN = 'handgun';
    public const CATEGORY_RIFLE = 'rifle';
    public const CATEGORY_SHOTGUN = 'shotgun';

    // Ignition type constants
    public const IGNITION_RIMFIRE = 'rimfire';
    public const IGNITION_CENTERFIRE = 'centerfire';
    public const IGNITION_BOTH = 'both';

    // Action type constants
    public const ACTION_SINGLE_SHOT = 'single_shot';
    public const ACTION_REVOLVER = 'revolver';
    public const ACTION_SEMI_AUTO = 'semi_auto';
    public const ACTION_BOLT_ACTION = 'bolt_action';
    public const ACTION_LEVER_ACTION = 'lever_action';
    public const ACTION_PUMP_ACTION = 'pump_action';
    public const ACTION_BREAK_ACTION = 'break_action';
    public const ACTION_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'category',
        'ignition_type',
        'action_type',
        'description',
        'dedicated_type',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get category options for dropdowns.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_HANDGUN => 'Handgun',
            self::CATEGORY_RIFLE => 'Rifle',
            self::CATEGORY_SHOTGUN => 'Shotgun',
        ];
    }

    /**
     * Get ignition type options for dropdowns.
     */
    public static function getIgnitionTypeOptions(): array
    {
        return [
            self::IGNITION_RIMFIRE => 'Rimfire',
            self::IGNITION_CENTERFIRE => 'Centerfire',
            self::IGNITION_BOTH => 'Both',
        ];
    }

    /**
     * Get action type options for dropdowns based on category.
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

        if ($category === self::CATEGORY_RIFLE) {
            return [
                self::ACTION_BOLT_ACTION => 'Bolt Action',
                self::ACTION_LEVER_ACTION => 'Lever Action',
                self::ACTION_SEMI_AUTO => 'Semi-Automatic',
                self::ACTION_SINGLE_SHOT => 'Single Shot',
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
     * Get the shooting activities using this firearm type.
     */
    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    /**
     * Get the user firearms of this type.
     */
    public function userFirearms(): HasMany
    {
        return $this->hasMany(UserFirearm::class);
    }

    // ===== Scopes =====

    /**
     * Scope to only active firearm types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to firearm types for a specific dedicated type.
     */
    public function scopeForDedicatedType($query, string $dedicatedType)
    {
        return $query->where(function ($q) use ($dedicatedType) {
            $q->where('dedicated_type', $dedicatedType)
              ->orWhere('dedicated_type', 'both');
        });
    }

    /**
     * Scope to firearm types for a specific category.
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to firearm types for a specific ignition type.
     */
    public function scopeForIgnitionType($query, string $ignitionType)
    {
        return $query->where(function ($q) use ($ignitionType) {
            $q->where('ignition_type', $ignitionType)
              ->orWhere('ignition_type', 'both')
              ->orWhereNull('ignition_type');
        });
    }

    /**
     * Scope to firearm types for a specific action type.
     */
    public function scopeForActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ===== Accessors =====

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategoryOptions()[$this->category] ?? ucfirst($this->category);
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
}
