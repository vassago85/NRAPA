<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LadderTest extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'load_data_id',
        'user_firearm_id',
        'name',
        'calibre',
        'bullet_make',
        'bullet_weight',
        'bullet_type',
        'powder_type',
        'primer_type',
        'test_type',
        'value_unit',
        'start_charge',
        'end_charge',
        'increment',
        'rounds_per_step',
        'notes',
    ];

    protected $casts = [
        'bullet_weight' => 'decimal:1',
        'start_charge' => 'decimal:3',
        'end_charge' => 'decimal:3',
        'increment' => 'decimal:3',
        'rounds_per_step' => 'integer',
    ];

    public function isPowderCharge(): bool
    {
        return ($this->test_type ?? 'powder_charge') === 'powder_charge';
    }

    public function isSeatingDepth(): bool
    {
        return $this->test_type === 'seating_depth';
    }

    /**
     * Get a human-friendly unit label (e.g., "gr", "mm", "in").
     */
    public function getUnitLabelAttribute(): string
    {
        return match ($this->value_unit) {
            'inches' => '"',
            'mm' => 'mm',
            default => 'gr',
        };
    }

    /**
     * Get the type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->isSeatingDepth() ? 'Seating Depth' : 'Powder Charge';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loadData(): BelongsTo
    {
        return $this->belongsTo(LoadData::class);
    }

    public function userFirearm(): BelongsTo
    {
        return $this->belongsTo(UserFirearm::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LadderTestStep::class)->orderBy('step_number');
    }

    public function getTotalRoundsAttribute(): int
    {
        return $this->steps->count() * $this->rounds_per_step;
    }

    public function getStepCountAttribute(): int
    {
        return (int) ceil(($this->end_charge - $this->start_charge) / $this->increment) + 1;
    }

    public function getBestStepAttribute(): ?LadderTestStep
    {
        return $this->steps
            ->filter(fn ($s) => $s->sd !== null && $s->sd > 0)
            ->sortBy('sd')
            ->first();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function generateSteps(float $startCharge, float $endCharge, float $increment, int $precision = 1): array
    {
        $steps = [];
        $stepNumber = 1;
        // Use epsilon based on precision to avoid floating-point cutoff
        $epsilon = pow(10, -($precision + 1));
        for ($value = $startCharge; $value <= $endCharge + $epsilon; $value += $increment) {
            $steps[] = [
                'step_number' => $stepNumber,
                'charge_weight' => round($value, $precision),
            ];
            $stepNumber++;
        }

        return $steps;
    }
}
