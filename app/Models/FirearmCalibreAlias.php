<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirearmCalibreAlias extends Model
{
    protected $fillable = [
        'firearm_calibre_id',
        'alias',
        'normalized_alias',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alias) {
            if (empty($alias->normalized_alias)) {
                $alias->normalized_alias = FirearmCalibre::normalize($alias->alias);
            }
        });

        static::updating(function ($alias) {
            if ($alias->isDirty('alias') && empty($alias->normalized_alias)) {
                $alias->normalized_alias = FirearmCalibre::normalize($alias->alias);
            }
        });
    }

    /**
     * Get the calibre this alias belongs to.
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(FirearmCalibre::class, 'firearm_calibre_id');
    }
}
