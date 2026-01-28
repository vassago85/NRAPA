<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "uuid", "commentable_type", "commentable_id", "author_id", "body",
        "visibility", "notify_applicant", "notified_at",
    ];

    protected function casts(): array
    {
        return [
            "notify_applicant" => "boolean",
            "notified_at" => "datetime",
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Comment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = (string) Str::uuid();
            }
        });
    }

    public function commentable(): MorphTo { return $this->morphTo(); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, "author_id"); }
    public function isVisibleToApplicant(): bool { return $this->visibility === "applicant"; }
    public function isInternal(): bool { return $this->visibility === "internal"; }
}
