<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LadderTestStep extends Model
{
    protected $fillable = [
        'ladder_test_id',
        'step_number',
        'charge_weight',
        'velocities',
        'group_size',
        'es',
        'sd',
        'notes',
    ];

    protected $casts = [
        'charge_weight' => 'decimal:3',
        'velocities' => 'array',
        'group_size' => 'decimal:2',
        'es' => 'integer',
        'sd' => 'integer',
    ];

    public function ladderTest(): BelongsTo
    {
        return $this->belongsTo(LadderTest::class);
    }

    public function getAverageVelocityAttribute(): ?float
    {
        if (empty($this->velocities)) {
            return null;
        }

        $valid = array_filter($this->velocities, fn ($v) => is_numeric($v) && $v > 0);
        if (empty($valid)) {
            return null;
        }

        return round(array_sum($valid) / count($valid), 0);
    }

    public function getHasResultsAttribute(): bool
    {
        return !empty($this->velocities) || $this->group_size !== null || $this->es !== null || $this->sd !== null;
    }
}
