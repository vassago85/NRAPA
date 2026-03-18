<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirearmModel extends Model
{
    protected $fillable = [
        'firearm_make_id',
        'name',
        'normalized_name',
        'category_hint',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->normalized_name)) {
                $model->normalized_name = static::normalize($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->normalized_name)) {
                $model->normalized_name = static::normalize($model->name);
            }
        });
    }

    /**
     * Normalize a model name for searching.
     */
    public static function normalize(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    /**
     * Get the make this model belongs to.
     */
    public function make(): BelongsTo
    {
        return $this->belongsTo(FirearmMake::class, 'firearm_make_id');
    }

    /**
     * Scope to only active models.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by make.
     */
    public function scopeForMake($query, ?int $makeId)
    {
        if ($makeId) {
            return $query->where('firearm_make_id', $makeId);
        }

        return $query;
    }

    /**
     * Scope to filter by category hint.
     */
    public function scopeForCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category_hint', $category);
        }

        return $query;
    }

    /**
     * Search models by name.
     */
    public function scopeSearch($query, string $term)
    {
        $normalizedTerm = static::normalize($term);

        return $query->where('name', 'LIKE', "%{$term}%")
            ->orWhere('normalized_name', 'LIKE', "%{$normalizedTerm}%");
    }

    /**
     * Get full name (make + model).
     */
    public function getFullNameAttribute(): string
    {
        return $this->make ? "{$this->make->name} {$this->name}" : $this->name;
    }
}
