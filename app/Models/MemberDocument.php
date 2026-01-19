<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemberDocument extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'document_type_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'metadata',
        'status',
        'uploaded_at',
        'verified_at',
        'verified_by',
        'expires_at',
        'archived_at',
        'archive_until',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'metadata' => 'array',
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'archived_at' => 'datetime',
            'archive_until' => 'datetime',
        ];
    }

    /**
     * Document type slugs that require ID information.
     */
    public const ID_DOCUMENT_SLUGS = ['id-document', 'id-copy', 'identity-document'];

    /**
     * Document type slugs that require address information.
     */
    public const ADDRESS_DOCUMENT_SLUGS = ['proof-of-address', 'proof-of-residence', 'address-proof'];

    /**
     * Check if this document type requires ID metadata.
     */
    public function requiresIdMetadata(): bool
    {
        return $this->documentType && in_array($this->documentType->slug, self::ID_DOCUMENT_SLUGS);
    }

    /**
     * Check if this document type requires address metadata.
     */
    public function requiresAddressMetadata(): bool
    {
        return $this->documentType && in_array($this->documentType->slug, self::ADDRESS_DOCUMENT_SLUGS);
    }

    /**
     * Parse South African ID number to extract date of birth.
     * Format: YYMMDD SSSS C A Z
     * Example: 8507026265088 = 02 July 1985
     */
    public static function parseSaIdNumber(string $idNumber): ?array
    {
        // Remove any spaces or dashes
        $idNumber = preg_replace('/[\s-]/', '', $idNumber);

        // SA ID must be 13 digits
        if (!preg_match('/^\d{13}$/', $idNumber)) {
            return null;
        }

        // Extract date parts (YYMMDD)
        $year = substr($idNumber, 0, 2);
        $month = substr($idNumber, 2, 2);
        $day = substr($idNumber, 4, 2);

        // Determine century (assume 00-29 is 2000s, 30-99 is 1900s)
        $fullYear = ((int)$year <= 29) ? '20' . $year : '19' . $year;

        // Validate date
        if (!checkdate((int)$month, (int)$day, (int)$fullYear)) {
            return null;
        }

        // Extract gender (digit 7-10, 0000-4999 = female, 5000-9999 = male)
        $genderDigits = (int)substr($idNumber, 6, 4);
        $sex = $genderDigits >= 5000 ? 'male' : 'female';

        return [
            'date_of_birth' => "{$fullYear}-{$month}-{$day}",
            'sex' => $sex,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MemberDocument $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
            if (empty($document->uploaded_at)) {
                $document->uploaded_at = now();
            }

            // Calculate expiry date based on document type
            if (empty($document->expires_at) && $document->documentType) {
                $document->expires_at = $document->documentType->calculateExpiryDate($document->uploaded_at);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document type.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Get the admin who verified the document.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ===== Status Checks =====

    /**
     * Check if the document is pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the document is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Check if the document is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the document is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    // ===== Actions =====

    /**
     * Verify the document.
     */
    public function verify(User $admin): void
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);
    }

    /**
     * Reject the document.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark the document as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Archive the document.
     */
    public function archive(): void
    {
        $archiveUntil = $this->expires_at
            ? $this->documentType->calculateArchiveUntilDate($this->expires_at)
            : now()->addMonths($this->documentType->archive_months);

        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archive_until' => $archiveUntil,
        ]);
    }

    // ===== Scopes =====

    /**
     * Scope to only pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to only verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope to only valid (verified and not expired) documents.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to only expired documents.
     */
    public function scopeExpiredDocuments($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            });
    }
}
