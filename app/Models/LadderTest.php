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
        'start_charge',
        'end_charge',
        'increment',
        'rounds_per_step',
        'notes',
    ];

    protected $casts = [
        'bullet_weight' => 'decimal:1',
        'start_charge' => 'decimal:1',
        'end_charge' => 'decimal:1',
        'increment' => 'decimal:2',
        'rounds_per_step' => 'integer',
    ];

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

    public static function generateSteps(float $startCharge, float $endCharge, float $increment): array
    {
        $steps = [];
        $stepNumber = 1;
        for ($charge = $startCharge; $charge <= $endCharge + 0.001; $charge += $increment) {
            $steps[] = [
                'step_number' => $stepNumber,
                'charge_weight' => round($charge, 1),
            ];
            $stepNumber++;
        }
        return $steps;
    }
}
