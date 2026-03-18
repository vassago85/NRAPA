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

    // Available icons for membership types (Heroicons)
    public const AVAILABLE_ICONS = [
        'shield-check' => 'Shield Check - Security/Protection',
        'star' => 'Star - Featured/Premium',
        'trophy' => 'Trophy - Achievement/Elite',
        'badge-check' => 'Badge Check - Verified/Certified',
        'sparkles' => 'Sparkles - Special/New',
        'fire' => 'Fire - Popular/Hot',
        'bolt' => 'Bolt - Power/Speed',
        'heart' => 'Heart - Passion/Love',
        'academic-cap' => 'Academic Cap - Education/Learning',
        'user-group' => 'User Group - Community/Team',
        'globe-alt' => 'Globe - International',
        'gift' => 'Gift - Special Offer',
        'clock' => 'Clock - Time-limited',
        'currency-dollar' => 'Currency - Value/Savings',
        'identification' => 'ID Card - Membership',
    ];

    protected $fillable = [
        'slug',
        'name',
        'icon',
        'description',
        'duration_type',
        'duration_months',
        'requires_renewal',
        'expiry_rule',
        'expiry_month',
        'expiry_day',
        'pricing_model',
        'initial_price',
        'renewal_price',
        'upgrade_price',
        'allows_dedicated_status',
        'dedicated_type',
        'requires_knowledge_test',
        'discount_eligible',
        'is_active',
        'is_featured',
        'display_on_landing',
        'display_on_signup',
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
            'initial_price' => 'decimal:2',
            'renewal_price' => 'decimal:2',
            'upgrade_price' => 'decimal:2',
            'requires_renewal' => 'boolean',
            'allows_dedicated_status' => 'boolean',
            'requires_knowledge_test' => 'boolean',
            'discount_eligible' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'display_on_landing' => 'boolean',
            'display_on_signup' => 'boolean',
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
     * Get the knowledge tests required for this membership type.
     */
    public function knowledgeTests(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTest::class)
            ->withPivot('is_required')
            ->withTimestamps();
    }

    /**
     * Get only the required knowledge tests for endorsement.
     */
    public function requiredKnowledgeTests(): BelongsToMany
    {
        return $this->knowledgeTests()->wherePivot('is_required', true);
    }

    /**
     * Get available knowledge tests for this membership type based on dedicated_type.
     */
    public function getAvailableKnowledgeTestsAttribute()
    {
        return KnowledgeTest::active()
            ->where(function ($q) {
                // Tests matching this membership's dedicated type
                if ($this->dedicated_type === 'both') {
                    // Both type can use hunter, sport, or both tests
                    $q->whereIn('dedicated_type', ['hunter', 'sport', 'sport_shooter', 'both'])
                        ->orWhereNull('dedicated_type');
                } elseif ($this->dedicated_type === 'hunter') {
                    $q->whereIn('dedicated_type', ['hunter', 'both'])
                        ->orWhereNull('dedicated_type');
                } elseif ($this->dedicated_type === 'sport' || $this->dedicated_type === 'sport_shooter') {
                    $q->whereIn('dedicated_type', ['sport', 'sport_shooter', 'both'])
                        ->orWhereNull('dedicated_type');
                } else {
                    // No dedicated type - only general tests
                    $q->whereNull('dedicated_type');
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if a user has passed all required tests for this membership type.
     */
    public function hasUserPassedAllRequiredTests(User $user): bool
    {
        $requiredTests = $this->requiredKnowledgeTests()->pluck('knowledge_tests.id');

        if ($requiredTests->isEmpty()) {
            return true; // No required tests means they've "passed"
        }

        $passedTests = KnowledgeTestAttempt::where('user_id', $user->id)
            ->whereIn('knowledge_test_id', $requiredTests)
            ->where(function ($q) {
                $q->where('passed', true)->orWhereNotNull('marked_by');
            })
            ->pluck('knowledge_test_id')
            ->unique();

        return $passedTests->count() >= $requiredTests->count();
    }

    /**
     * Get the tests a user still needs to pass for this membership type.
     */
    public function getOutstandingTestsForUser(User $user)
    {
        $requiredTestIds = $this->requiredKnowledgeTests()->pluck('knowledge_tests.id');

        $passedTestIds = KnowledgeTestAttempt::where('user_id', $user->id)
            ->whereIn('knowledge_test_id', $requiredTestIds)
            ->where(function ($q) {
                $q->where('passed', true)->orWhereNotNull('marked_by');
            })
            ->pluck('knowledge_test_id')
            ->unique();

        return KnowledgeTest::whereIn('id', $requiredTestIds)
            ->whereNotIn('id', $passedTestIds)
            ->get();
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
     * Scope to only types displayed on signup/apply pages.
     */
    public function scopeDisplayOnSignup($query)
    {
        return $query->where('display_on_signup', true);
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
     * Get the sign-up price (initial_price for new members).
     * For backwards compatibility, also accessible as 'price'.
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->initial_price;
    }

    /**
     * Get the total price for initial sign-up.
     * For basic members: initial_price
     * For dedicated members applying directly: basic initial_price + upgrade_price
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->initial_price;
    }

    /**
     * Get the sign-up fee for new members.
     */
    public function getSignupPriceAttribute(): float
    {
        return (float) $this->initial_price;
    }

    /**
     * Get the annual renewal fee.
     */
    public function getRenewalFeeAttribute(): float
    {
        return (float) $this->renewal_price;
    }

    /**
     * Get the once-off upgrade fee (for dedicated types).
     * Returns null for basic membership (no upgrade fee).
     */
    public function getUpgradeFeeAttribute(): ?float
    {
        return $this->upgrade_price !== null ? (float) $this->upgrade_price : null;
    }

    /**
     * Check if this membership type has an upgrade fee (i.e. is a dedicated type).
     */
    public function hasUpgradeFee(): bool
    {
        return $this->upgrade_price !== null && $this->upgrade_price > 0;
    }

    /**
     * Check if this is a basic (non-dedicated) membership type.
     */
    public function isBasic(): bool
    {
        return $this->dedicated_type === null && $this->upgrade_price === null;
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
        // Ensure we have a Carbon instance for date manipulation
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now();

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
