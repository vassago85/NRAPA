<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AffiliatedClub extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'dedicated_type',
        'initial_fee',
        'renewal_fee',
        'requires_competency',
        'required_activities_per_year',
        'contact_name',
        'contact_email',
        'contact_phone',
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
            'initial_fee' => 'decimal:2',
            'renewal_fee' => 'decimal:2',
            'requires_competency' => 'boolean',
            'required_activities_per_year' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AffiliatedClub $club) {
            if (empty($club->uuid)) {
                $club->uuid = (string) Str::uuid();
            }
            if (empty($club->slug)) {
                $club->slug = Str::slug($club->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all memberships associated with this club.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Scope to only active clubs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the dedicated type display label.
     */
    public function getDedicatedTypeLabelAttribute(): string
    {
        return match ($this->dedicated_type) {
            'hunter' => 'Dedicated Hunter',
            'sport' => 'Dedicated Sport Shooter',
            'both' => 'Dedicated Hunter & Sport Shooter',
            default => 'Unknown',
        };
    }
}
