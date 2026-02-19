<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningArticlePage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'learning_article_id',
        'title',
        'image_path',
        'image_caption',
        'content',
        'page_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
        ];
    }

    /**
     * Get the article this page belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(LearningArticle::class, 'learning_article_id');
    }

    /**
     * Get users who have read this page.
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_page_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if the page has an image.
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

        return '/storage/' . ltrim($this->image_path, '/');
    }

    /**
     * Get the image URL to display (page image or default NRAPA logo).
     */
    public function getDisplayImageUrlAttribute(): string
    {
        return $this->image_url ?? asset('nrapa-logo.png');
    }

    /**
     * Check if a user has read this page.
     */
    public function isReadBy(User $user): bool
    {
        return $this->readers()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark the page as read by a user.
     */
    public function markAsReadBy(User $user): void
    {
        if (!$this->isReadBy($user)) {
            $this->readers()->attach($user->id, ['read_at' => now()]);
        }
    }

    /**
     * Get the next page in the article.
     */
    public function getNextPageAttribute(): ?self
    {
        return static::where('learning_article_id', $this->learning_article_id)
            ->where('page_number', '>', $this->page_number)
            ->orderBy('page_number')
            ->first();
    }

    /**
     * Get the previous page in the article.
     */
    public function getPreviousPageAttribute(): ?self
    {
        return static::where('learning_article_id', $this->learning_article_id)
            ->where('page_number', '<', $this->page_number)
            ->orderByDesc('page_number')
            ->first();
    }

    /**
     * Check if this is the first page.
     */
    public function isFirstPage(): bool
    {
        return $this->page_number === 1;
    }

    /**
     * Check if this is the last page.
     */
    public function isLastPage(): bool
    {
        return $this->next_page === null;
    }

    /**
     * Scope to order by page number.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('page_number');
    }
}
