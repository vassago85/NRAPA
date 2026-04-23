<?php

namespace App\Models;

use App\Mail\DocumentRejected;
use App\Mail\DocumentVerified;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        'file_purged_at',
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
            'file_purged_at' => 'datetime',
        ];
    }

    /**
     * Document type slugs that require ID information.
     */
    public const ID_DOCUMENT_SLUGS = ['identity-document', 'id-document', 'id-copy'];

    /**
     * Document type slugs that require address information.
     */
    public const ADDRESS_DOCUMENT_SLUGS = ['proof-of-address', 'proof-of-residence', 'address-proof'];

    /**
     * Document type slugs that require competency information.
     */
    public const COMPETENCY_DOCUMENT_SLUGS = ['firearm-competency', 'competency-certificate', 'competency'];

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
     * Check if this document type requires competency metadata.
     */
    public function requiresCompetencyMetadata(): bool
    {
        return $this->documentType && in_array($this->documentType->slug, self::COMPETENCY_DOCUMENT_SLUGS);
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
        if (! preg_match('/^\d{13}$/', $idNumber)) {
            return null;
        }

        // Extract date parts (YYMMDD)
        $year = substr($idNumber, 0, 2);
        $month = substr($idNumber, 2, 2);
        $day = substr($idNumber, 4, 2);

        // Determine century (assume 00-29 is 2000s, 30-99 is 1900s)
        $fullYear = ((int) $year <= 29) ? '20'.$year : '19'.$year;

        // Validate date
        if (! checkdate((int) $month, (int) $day, (int) $fullYear)) {
            return null;
        }

        // Extract gender (digit 7-10, 0000-4999 = female, 5000-9999 = male)
        $genderDigits = (int) substr($idNumber, 6, 4);
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

    /**
     * Get the shooting activity that uses this document as evidence.
     */
    public function shootingActivityAsEvidence(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShootingActivity::class, 'evidence_document_id');
    }

    /**
     * Get the shooting activity that uses this document as additional evidence.
     */
    public function shootingActivityAsAdditional(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShootingActivity::class, 'additional_document_id');
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

        // Sync ID number to users table when an ID document is verified
        $this->syncIdNumberToUser();

        // Send verification email to member
        $this->notifyVerification();
    }

    /**
     * When an ID document is verified, copy the identity_number from
     * the document metadata to the users.id_number column so it is
     * always available as a fallback (e.g. on certificates).
     */
    protected function syncIdNumberToUser(): void
    {
        if (! $this->requiresIdMetadata()) {
            return;
        }

        $idNumber = $this->metadata['identity_number'] ?? null;

        if (! $idNumber || ! $this->user) {
            return;
        }

        if ($this->user->id_number === $idNumber) {
            return;
        }

        $existing = User::where('id_number', $idNumber)
            ->where('id', '!=', $this->user->id)
            ->first();

        if ($existing) {
            Log::warning('Cannot sync ID number to user: duplicate exists', [
                'id_number' => $idNumber,
                'target_user_id' => $this->user->id,
                'existing_user_id' => $existing->id,
                'document_id' => $this->id,
            ]);

            return;
        }

        $this->user->update(['id_number' => $idNumber]);
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

        // Send notification to member
        $this->notifyRejection($reason);
    }

    /**
     * Send notification to member about document verification.
     */
    protected function notifyVerification(): void
    {
        $user = $this->user;
        if (! $user) {
            return;
        }

        try {
            Mail::to($user->email)->send(new DocumentVerified(
                document: $this->load('documentType', 'user'),
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send document verified email', [
                'document_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to member about document rejection.
     */
    protected function notifyRejection(string $reason): void
    {
        $user = $this->user;
        if (! $user) {
            return;
        }

        // Send rejection email
        try {
            Mail::to($user->email)->send(new DocumentRejected(
                document: $this->load('documentType', 'user'),
                reason: $reason,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send document rejected email', [
                'document_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Also send via NtfyService if available
        $documentTypeName = $this->documentType?->name ?? 'Document';
        $title = "Document Rejected: {$documentTypeName}";
        $message = "Your {$documentTypeName} has been rejected.\n\nReason: {$reason}\n\nPlease review and upload a new document.";

        if (class_exists(\App\Services\NtfyService::class)) {
            $ntfyService = app(\App\Services\NtfyService::class);
            $ntfyService->notifyUser(
                $user,
                'document_rejected',
                $title,
                $message,
                'high',
                [
                    'document_id' => $this->id,
                    'document_type' => $documentTypeName,
                    'rejection_reason' => $reason,
                ]
            );
        }
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
