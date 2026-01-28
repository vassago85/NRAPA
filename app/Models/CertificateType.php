<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'template',
        'validity_months',
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
            'validity_months' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all certificates of this type.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the membership types entitled to this certificate.
     */
    public function membershipTypes(): BelongsToMany
    {
        return $this->belongsToMany(MembershipType::class)
            ->withPivot(['requires_dedicated_status', 'requires_active_membership'])
            ->withTimestamps();
    }

    /**
     * Scope to only active certificate types.
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
        return $query->orderBy('sort_order');
    }

    /**
     * Check if the certificate has indefinite validity.
     */
    public function hasIndefiniteValidity(): bool
    {
        return $this->validity_months === null;
    }

    /**
     * Calculate valid until date from issue date.
     */
    public function calculateValidUntilDate(\DateTimeInterface $issueDate): ?\DateTimeInterface
    {
        if ($this->hasIndefiniteValidity()) {
            return null;
        }

        // Ensure we have a Carbon instance for date manipulation
        $date = $issueDate instanceof Carbon 
            ? $issueDate->copy() 
            : Carbon::instance($issueDate);
        
        return $date->addMonths($this->validity_months);
    }

    /**
     * Check if a user is eligible for this certificate based on their membership.
     */
    public function isEligibleFor(Membership $membership, bool $hasDedicatedStatus = false): bool
    {
        // Check if this certificate type is linked to the membership type
        $pivot = $this->membershipTypes()
            ->where('membership_type_id', $membership->membership_type_id)
            ->first()?->pivot;

        if (! $pivot) {
            return false;
        }

        // Check if active membership is required
        if ($pivot->requires_active_membership && ! $membership->isActive()) {
            return false;
        }

        // Check if dedicated status is required
        if ($pivot->requires_dedicated_status && ! $hasDedicatedStatus) {
            return false;
        }

        return true;
    }
}
