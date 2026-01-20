<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Dedicated type constants
    public const DEDICATED_TYPE_HUNTER = 'hunter';
    public const DEDICATED_TYPE_SPORT = 'sport';
    public const DEDICATED_TYPE_BOTH = 'both';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'duration_type',
        'duration_months',
        'requires_renewal',
        'expiry_rule',
        'expiry_month',
        'expiry_day',
        'pricing_model',
        'price',
        'admin_fee',
        'allows_dedicated_status',
        'dedicated_type',
        'requires_knowledge_test',
        'discount_eligible',
        'is_active',
        'is_featured',
        'display_on_landing',
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
            'duration_months' => 'integer',
            'expiry_month' => 'integer',
            'expiry_day' => 'integer',
            'price' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'requires_renewal' => 'boolean',
            'allows_dedicated_status' => 'boolean',
            'requires_knowledge_test' => 'boolean',
            'discount_eligible' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'display_on_landing' => 'boolean',
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
     * Get all memberships of this type.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the required document types for this membership type.
     */
    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class)
            ->withPivot('is_required')
            ->withTimestamps();
    }

    /**
     * Get the required document types.
     */
    public function requiredDocumentTypes(): BelongsToMany
    {
        return $this->documentTypes()->wherePivot('is_required', true);
    }

    /**
     * Get the certificate types entitled for this membership type.
     */
    public function certificateTypes(): BelongsToMany
    {
        return $this->belongsToMany(CertificateType::class)
            ->withPivot(['requires_dedicated_status', 'requires_active_membership'])
            ->withTimestamps();
    }

    /**
     * Scope to only active membership types.
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
     * Scope to only featured membership type.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to only types displayed on landing page.
     */
    public function scopeDisplayOnLanding($query)
    {
        return $query->where('display_on_landing', true);
    }

    /**
     * Scope to filter by dedicated type.
     */
    public function scopeForDedicatedType($query, ?string $type)
    {
        if ($type === self::DEDICATED_TYPE_BOTH) {
            // Users with "both" can see all dedicated types
            return $query;
        }

        if ($type) {
            // Users with specific type see their type + both + general
            return $query->where(function ($q) use ($type) {
                $q->whereNull('dedicated_type')
                    ->orWhere('dedicated_type', $type)
                    ->orWhere('dedicated_type', self::DEDICATED_TYPE_BOTH);
            });
        }

        // Users with no dedicated status see only general content
        return $query->whereNull('dedicated_type');
    }

    /**
     * Get the total price including admin fee.
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->price + (float) $this->admin_fee;
    }

    /**
     * Check if this is a lifetime membership.
     */
    public function isLifetime(): bool
    {
        return $this->duration_type === 'lifetime';
    }

    /**
     * Calculate expiry date based on attributes.
     */
    public function calculateExpiryDate(?\DateTimeInterface $startDate = null): ?\DateTimeInterface
    {
        $startDate = $startDate ?? now();

        // Lifetime memberships don't expire
        if ($this->duration_type === 'lifetime' || $this->expiry_rule === 'none') {
            return null;
        }

        // Fixed date expiry (e.g., all expire on March 31)
        if ($this->expiry_rule === 'fixed_date' && $this->expiry_month && $this->expiry_day) {
            $expiryDate = $startDate->copy()
                ->month($this->expiry_month)
                ->day($this->expiry_day);

            // If the fixed date has passed this year, use next year
            if ($expiryDate->isPast()) {
                $expiryDate->addYear();
            }

            return $expiryDate;
        }

        // Rolling expiry (e.g., 12 months from activation)
        if ($this->expiry_rule === 'rolling' && $this->duration_months) {
            return $startDate->copy()->addMonths($this->duration_months);
        }

        return null;
    }
}
