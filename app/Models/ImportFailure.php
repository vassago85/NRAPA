<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFailure extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'row_data',
        'error_message',
        'resolved',
        'resolved_at',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
            'resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The admin who ran the import.
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    // ===== Scopes =====

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeForBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    // ===== Helpers =====

    /**
     * Mark this failure as resolved (imported or dismissed).
     */
    public function markResolved(): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Get a human-readable summary of the row data.
     */
    public function getNameAttribute(): string
    {
        $data = $this->row_data;
        return trim(($data['initials'] ?? '') . ' ' . ($data['surname'] ?? ''));
    }

    /**
     * Get email from row data.
     */
    public function getEmailAttribute(): string
    {
        return $this->row_data['email'] ?? '';
    }
}
