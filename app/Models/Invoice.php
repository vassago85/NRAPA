<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * NRAPA invoice.
 *
 * Immutable once issued. The number, line_items and payload columns are the
 * authoritative record of what was invoiced; changes to the underlying
 * member/bank/membership data do not retroactively change past invoices.
 */
class Invoice extends Model
{
    protected $fillable = [
        'uuid',
        'number',
        'user_id',
        'source_type',
        'source_id',
        'issued_at',
        'currency',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'reference',
        'line_items',
        'payload',
        'pdf_path',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'line_items' => 'array',
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
