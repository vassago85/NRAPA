<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Dedicated type constants (matches MembershipType)
    public const DEDICATED_TYPE_HUNTER = 'hunter';
    public const DEDICATED_TYPE_SPORT = 'sport';
    public const DEDICATED_TYPE_BOTH = 'both';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image_path',
        'sort_order',
        'is_active',
        'dedicated_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the articles for this category.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(LearningArticle::class);
    }

    /**
     * Get published articles for this category.
     */
    public function publishedArticles(): HasMany
    {
        return $this->hasMany(LearningArticle::class)->published();
    }

    /**
     * Check if the category has an image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->hasImage()) {
            return null;
        }

        return StorageHelper::getUrl($this->image_path);
    }

    /**
     * Scope to only active categories.
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
     * Scope to filter by user's dedicated type access.
     * 
     * @param string|null $userDedicatedType The user's membership dedicated_type
     */
    public function scopeForDedicatedType($query, ?string $userDedicatedType)
    {
        if ($userDedicatedType === self::DEDICATED_TYPE_BOTH) {
            // Users with "both" can see all content
            return $query;
        }

        if ($userDedicatedType) {
            // Users with a specific type see: general + their type + both
            return $query->where(function ($q) use ($userDedicatedType) {
                $q->whereNull('dedicated_type')
                    ->orWhere('dedicated_type', $userDedicatedType)
                    ->orWhere('dedicated_type', self::DEDICATED_TYPE_BOTH);
            });
        }

        // Users with no dedicated status only see general content
        return $query->whereNull('dedicated_type');
    }

    /**
     * Get the dedicated type label.
     */
    public function getDedicatedTypeLabelAttribute(): string
    {
        return match($this->dedicated_type) {
            self::DEDICATED_TYPE_HUNTER => 'Dedicated Hunters',
            self::DEDICATED_TYPE_SPORT => 'Dedicated Sport Shooters',
            self::DEDICATED_TYPE_BOTH => 'All Dedicated Members',
            default => 'General',
        };
    }

    /**
     * Get the route key name for Laravel.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
