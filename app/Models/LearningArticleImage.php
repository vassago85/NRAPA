<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningArticleImage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'learning_article_id',
        'path',
        'alt_text',
        'caption',
    ];

    /**
     * Get the article.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(LearningArticle::class, 'learning_article_id');
    }

    /**
     * Get the image URL.
     * Learning center images are always served from local storage.
     */
    public function getUrlAttribute(): string
    {
        return '/storage/' . ltrim($this->path, '/');
    }
}
