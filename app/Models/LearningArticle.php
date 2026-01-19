<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningArticle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'learning_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'reading_time_minutes',
        'sort_order',
        'is_published',
        'is_featured',
        'published_at',
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
            'reading_time_minutes' => 'integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($article) {
            // Calculate reading time based on content
            if ($article->isDirty('content')) {
                $wordCount = str_word_count(strip_tags($article->content));
                $article->reading_time_minutes = max(1, ceil($wordCount / 200)); // Assume 200 words per minute
            }
        });
    }

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    /**
     * Get the author.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the images for this article.
     */
    public function images(): HasMany
    {
        return $this->hasMany(LearningArticleImage::class);
    }

    /**
     * Get the users who have read this article.
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_article_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if the article has a featured image.
     */
    public function hasFeaturedImage(): bool
    {
        return !empty($this->featured_image);
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->hasFeaturedImage()) {
            return null;
        }

        return Storage::url($this->featured_image);
    }

    /**
     * Check if a user has read this article.
     */
    public function isReadBy(User $user): bool
    {
        return $this->readers()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark the article as read by a user.
     */
    public function markAsReadBy(User $user): void
    {
        if (!$this->isReadBy($user)) {
            $this->readers()->attach($user->id, ['read_at' => now()]);
        }
    }

    /**
     * Publish the article.
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Unpublish the article.
     */
    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }

    /**
     * Get excerpt or generate from content.
     */
    public function getExcerptOrSummaryAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(strip_tags($this->content), 200);
    }

    /**
     * Scope to only published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to only featured articles.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Scope to order by published date.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at');
    }

    /**
     * Get the route key name for Laravel.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
