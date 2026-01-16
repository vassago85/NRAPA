<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
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
        'expiry_months',
        'archive_months',
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
            'expiry_months' => 'integer',
            'archive_months' => 'integer',
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
     * Get all documents of this type.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    /**
     * Get the membership types that require this document.
     */
    public function membershipTypes(): BelongsToMany
    {
        return $this->belongsToMany(MembershipType::class)
            ->withPivot('is_required')
            ->withTimestamps();
    }

    /**
     * Scope to only active document types.
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
     * Check if this document type expires.
     */
    public function expires(): bool
    {
        return $this->expiry_months !== null;
    }

    /**
     * Calculate expiry date from upload date.
     */
    public function calculateExpiryDate(\DateTimeInterface $uploadDate): ?\DateTimeInterface
    {
        if (! $this->expires()) {
            return null;
        }

        return $uploadDate->copy()->addMonths($this->expiry_months);
    }

    /**
     * Calculate archive until date from expiry date.
     */
    public function calculateArchiveUntilDate(\DateTimeInterface $expiryDate): \DateTimeInterface
    {
        return $expiryDate->copy()->addMonths($this->archive_months);
    }
}
