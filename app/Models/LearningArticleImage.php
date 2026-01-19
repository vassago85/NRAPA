<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
