<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class LearningArticle extends Model
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
        'learning_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'document_path',
        'reading_time_minutes',
        'sort_order',
        'is_published',
        'is_featured',
        'dedicated_type',
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
     * Get the display name for the author (NRAPA when no user author).
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->author?->name ?? 'NRAPA';
    }

    /**
     * Get the images for this article.
     */
    public function images(): HasMany
    {
        return $this->hasMany(LearningArticleImage::class);
    }

    /**
     * Get the pages for this article.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(LearningArticlePage::class)->orderBy('page_number');
    }

    /**
     * Check if the article has multiple pages.
     */
    public function hasPages(): bool
    {
        return $this->pages()->count() > 0;
    }

    /**
     * Get the total number of pages.
     */
    public function getTotalPagesAttribute(): int
    {
        return $this->pages()->count();
    }

    /**
     * Get the first page of the article.
     */
    public function getFirstPageAttribute(): ?LearningArticlePage
    {
        return $this->pages()->orderBy('page_number')->first();
    }

    /**
     * Check how many pages a user has read.
     */
    public function getPagesReadByUser(User $user): int
    {
        return $this->pages()
            ->whereHas('readers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();
    }

    /**
     * Check if user has completed all pages.
     */
    public function isCompletedBy(User $user): bool
    {
        if (!$this->hasPages()) {
            return $this->isReadBy($user);
        }

        return $this->getPagesReadByUser($user) >= $this->total_pages;
    }

    /**
     * Get completion percentage for a user.
     */
    public function getCompletionPercentageFor(User $user): int
    {
        if (!$this->hasPages()) {
            return $this->isReadBy($user) ? 100 : 0;
        }

        $totalPages = $this->total_pages;
        if ($totalPages === 0) {
            return 0;
        }

        return (int) round(($this->getPagesReadByUser($user) / $totalPages) * 100);
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
     * Learning center images are always served from local storage.
     * Returns null when no image is set.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->hasFeaturedImage()) {
            return null;
        }

        return '/storage/' . ltrim($this->featured_image, '/');
    }

    /**
     * Get the image URL to display (featured image or default NRAPA logo).
     * Use this when you always want to show a picture.
     */
    public function getDisplayImageUrlAttribute(): string
    {
        return $this->featured_image_url ?? asset('nrapa-logo.png');
    }

    /**
     * Check if the article has a document.
     */
    public function hasDocument(): bool
    {
        return !empty($this->document_path);
    }

    /**
     * Get the document URL.
     * Learning center documents are always served from local storage.
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->hasDocument()) {
            return null;
        }

        return StorageHelper::getLearningCenterUrl($this->document_path);
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
     * Scope to filter by user's dedicated type access.
     * Uses article's dedicated_type if set, otherwise falls back to category's dedicated_type.
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
                // Check article's own dedicated_type first
                $q->where(function ($articleQ) use ($userDedicatedType) {
                    $articleQ->whereNull('dedicated_type')
                        ->orWhere('dedicated_type', $userDedicatedType)
                        ->orWhere('dedicated_type', self::DEDICATED_TYPE_BOTH);
                })
                // Also filter by category's dedicated_type if article doesn't have one
                ->whereHas('category', function ($catQ) use ($userDedicatedType) {
                    $catQ->whereNull('dedicated_type')
                        ->orWhere('dedicated_type', $userDedicatedType)
                        ->orWhere('dedicated_type', self::DEDICATED_TYPE_BOTH);
                });
            });
        }

        // Users with no dedicated status only see general content
        return $query->whereNull('dedicated_type')
            ->whereHas('category', function ($catQ) {
                $catQ->whereNull('dedicated_type');
            });
    }

    /**
     * Get the effective dedicated type (article's own or inherited from category).
     */
    public function getEffectiveDedicatedTypeAttribute(): ?string
    {
        return $this->dedicated_type ?? $this->category?->dedicated_type;
    }

    /**
     * Get the dedicated type label.
     */
    public function getDedicatedTypeLabelAttribute(): string
    {
        return match($this->effective_dedicated_type) {
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
