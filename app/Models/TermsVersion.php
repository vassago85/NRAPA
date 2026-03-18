<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TermsVersion extends Model
{
    protected $fillable = [
        'version',
        'title',
        'html_path',
        'html_content',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the active terms version.
     */
    public static function active(): ?self
    {
        try {
            return static::where('is_active', true)->first();
        } catch (\Exception $e) {
            // Table doesn't exist yet - return null (migrations need to be run)
            return null;
        }
    }

    /**
     * Get the HTML content (from file or database).
     */
    public function getHtmlContent(): string
    {
        if ($this->html_content) {
            return $this->html_content;
        }

        if ($this->html_path) {
            $disk = \App\Helpers\StorageHelper::getPublicDisk();
            if (Storage::disk($disk)->exists($this->html_path)) {
                return Storage::disk($disk)->get($this->html_path);
            }
        }

        return '';
    }

    /**
     * Get all acceptances for this version.
     */
    public function acceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    /**
     * Check if a user has accepted this version.
     */
    public function isAcceptedBy(User $user): bool
    {
        return $this->acceptances()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Activate this version (deactivates all others).
     */
    public function activate(): void
    {
        // Deactivate all other versions
        static::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activate this one
        $this->update(['is_active' => true]);

        if (! $this->published_at) {
            $this->update(['published_at' => now()]);
        }
    }
}
