<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FirearmMake extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'country',
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

        static::creating(function ($make) {
            if (empty($make->normalized_name)) {
                $make->normalized_name = static::normalize($make->name);
            }
        });

        static::updating(function ($make) {
            if ($make->isDirty('name') && empty($make->normalized_name)) {
                $make->normalized_name = static::normalize($make->name);
            }
        });
    }

    /**
     * Normalize a make name for searching.
     */
    public static function normalize(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    /**
     * Get models for this make.
     */
    public function models(): HasMany
    {
        return $this->hasMany(FirearmModel::class, 'firearm_make_id');
    }

    /**
     * Get active models for this make.
     */
    public function activeModels(): HasMany
    {
        return $this->hasMany(FirearmModel::class, 'firearm_make_id')->where('is_active', true);
    }

    /**
     * Scope to only active makes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Search makes by name.
     */
    public function scopeSearch($query, string $term)
    {
        $normalizedTerm = static::normalize($term);
        
        return $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('normalized_name', 'LIKE', "%{$normalizedTerm}%");
    }
}
