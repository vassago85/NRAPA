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
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image_path',
        'sort_order',
        'is_active',
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
     * Get the route key name for Laravel.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
