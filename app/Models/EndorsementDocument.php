<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EndorsementDocument extends Model
{
    use SoftDeletes;

    // Document type constants
    public const TYPE_SA_ID = 'sa_id';

    public const TYPE_PROOF_OF_ADDRESS = 'proof_of_address';

    public const TYPE_DEDICATED_STATUS_CERTIFICATE = 'dedicated_status_certificate';

    public const TYPE_MEMBERSHIP_PROOF = 'membership_proof';

    public const TYPE_ACTIVITY_PROOF = 'activity_proof';

    public const TYPE_PREVIOUS_ENDORSEMENT = 'previous_endorsement_letter';

    public const TYPE_FIREARM_LICENCE = 'firearm_licence_card';

    public const TYPE_COMPETENCY_CERTIFICATE = 'competency_certificate';

    public const TYPE_OTHER = 'other';

    // Status constants
    public const STATUS_REQUIRED = 'required';

    public const STATUS_PENDING_UPLOAD = 'pending_upload';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WAIVED = 'waived';

    public const STATUS_SYSTEM_VERIFIED = 'system_verified';

    // Activity type constants (for activity proof)
    public const ACTIVITY_MATCH = 'match';

    public const ACTIVITY_TRAINING = 'training';

    public const ACTIVITY_HUNT = 'hunt';

    public const ACTIVITY_PRACTICE = 'practice';

    public const ACTIVITY_COMPETITION = 'competition';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'endorsement_request_id',
        'document_type',
        'status',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
        'verified_by',
        'verified_at',
        'member_document_id',
        'metadata',
        'activity_type',
        'activity_discipline',
        'activity_date',
        'activity_venue',
        'activity_organiser',
        'document_date',
        'expires_at',
        'notes',
        'rejection_reason',
        'is_required',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'activity_date' => 'date',
            'document_date' => 'date',
            'expires_at' => 'date',
            'is_required' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EndorsementDocument $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
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

    // ===== Relationships =====

    /**
     * Get the endorsement request.
     */
    public function endorsementRequest(): BelongsTo
    {
        return $this->belongsTo(EndorsementRequest::class);
    }

    /**
     * Get the user who uploaded.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the admin who verified.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the linked member document.
     */
    public function memberDocument(): BelongsTo
    {
        return $this->belongsTo(MemberDocument::class);
    }

    // ===== Static Options =====

    /**
     * Get document type options.
     */
    public static function getDocumentTypeOptions(): array
    {
        return [
            self::TYPE_SA_ID => 'South African ID',
            self::TYPE_PROOF_OF_ADDRESS => 'Proof of Address',
            self::TYPE_DEDICATED_STATUS_CERTIFICATE => 'Dedicated Status Certificate',
            self::TYPE_MEMBERSHIP_PROOF => 'Membership Proof',
            self::TYPE_ACTIVITY_PROOF => 'Activity Proof / Participation Record',
            self::TYPE_PREVIOUS_ENDORSEMENT => 'Previous Endorsement Letter',
            self::TYPE_FIREARM_LICENCE => 'Firearm Licence Card',
            self::TYPE_COMPETENCY_CERTIFICATE => 'Competency Certificate',
            self::TYPE_OTHER => 'Other Document',
        ];
    }

    /**
     * Get status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_REQUIRED => 'Required',
            self::STATUS_PENDING_UPLOAD => 'Submit Later',
            self::STATUS_UPLOADED => 'Uploaded',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WAIVED => 'Waived',
            self::STATUS_SYSTEM_VERIFIED => 'Auto-Verified',
        ];
    }

    /**
     * Get activity type options.
     */
    public static function getActivityTypeOptions(): array
    {
        return [
            self::ACTIVITY_MATCH => 'Match / Competition',
            self::ACTIVITY_TRAINING => 'Training Day',
            self::ACTIVITY_HUNT => 'Hunt',
            self::ACTIVITY_PRACTICE => 'Practice Session',
            self::ACTIVITY_COMPETITION => 'Official Competition',
        ];
    }

    /**
     * Get discipline options.
     */
    public static function getDisciplineOptions(): array
    {
        return [
            'ipsc' => 'IPSC',
            'idpa' => 'IDPA',
            'prs' => 'PRS / Precision Rifle',
            '3gun' => '3-Gun',
            'bisley' => 'Bisley',
            'f-class' => 'F-Class',
            'benchrest' => 'Benchrest',
            'clay' => 'Clay Target',
            'hunting' => 'Hunting',
            'general' => 'General Practice',
            'other' => 'Other',
        ];
    }

    // ===== Status Checks =====

    /**
     * Check if document is uploaded.
     */
    public function isUploaded(): bool
    {
        return in_array($this->status, [
            self::STATUS_UPLOADED,
            self::STATUS_VERIFIED,
            self::STATUS_SYSTEM_VERIFIED,
        ]);
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return in_array($this->status, [
            self::STATUS_VERIFIED,
            self::STATUS_SYSTEM_VERIFIED,
        ]);
    }

    /**
     * Check if document is pending upload.
     */
    public function isPendingUpload(): bool
    {
        return $this->status === self::STATUS_PENDING_UPLOAD;
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if document is waived.
     */
    public function isWaived(): bool
    {
        return $this->status === self::STATUS_WAIVED;
    }

    /**
     * Check if this is an activity proof document.
     */
    public function isActivityProof(): bool
    {
        return $this->document_type === self::TYPE_ACTIVITY_PROOF;
    }

    // ===== Actions =====

    /**
     * Mark as uploaded.
     */
    public function markUploaded(string $filePath, string $originalFilename, string $mimeType, int $fileSize, User $uploader): void
    {
        $this->update([
            'status' => self::STATUS_UPLOADED,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Mark as pending upload (submit later).
     */
    public function markPendingUpload(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING_UPLOAD,
        ]);
    }

    /**
     * Verify the document.
     */
    public function verify(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $this->notifyVerification();
    }

    /**
     * Reject the document.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notifyRejection($reason);
    }

    protected function notifyVerification(): void
    {
        $user = $this->endorsementRequest?->user;
        if (! $user?->email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(
                new \App\Mail\EndorsementDocumentVerified($this)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send endorsement document verified email', [
                'document_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyRejection(string $reason): void
    {
        $user = $this->endorsementRequest?->user;
        if (! $user?->email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(
                new \App\Mail\EndorsementDocumentRejected($this, $reason)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send endorsement document rejected email', [
                'document_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Waive the requirement.
     */
    public function waive(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_WAIVED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'notes' => $notes,
        ]);
    }

    // ===== Scopes =====

    /**
     * Scope to uploaded documents.
     */
    public function scopeUploaded($query)
    {
        return $query->whereIn('status', [
            self::STATUS_UPLOADED,
            self::STATUS_VERIFIED,
            self::STATUS_SYSTEM_VERIFIED,
        ]);
    }

    /**
     * Scope to pending documents.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_REQUIRED,
            self::STATUS_PENDING_UPLOAD,
        ]);
    }

    /**
     * Scope to required documents.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to activity proofs.
     */
    public function scopeActivityProofs($query)
    {
        return $query->where('document_type', self::TYPE_ACTIVITY_PROOF);
    }

    // ===== Accessors =====

    /**
     * Get the document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::getDocumentTypeOptions()[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the activity type label.
     */
    public function getActivityTypeLabelAttribute(): ?string
    {
        if (! $this->activity_type) {
            return null;
        }

        return self::getActivityTypeOptions()[$this->activity_type] ?? ucfirst($this->activity_type);
    }

    /**
     * Get the discipline label.
     */
    public function getDisciplineLabelAttribute(): ?string
    {
        if (! $this->activity_discipline) {
            return null;
        }

        return self::getDisciplineOptions()[$this->activity_discipline] ?? strtoupper($this->activity_discipline);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REQUIRED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            self::STATUS_PENDING_UPLOAD => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            self::STATUS_UPLOADED => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            self::STATUS_VERIFIED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::STATUS_SYSTEM_VERIFIED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            self::STATUS_WAIVED => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }

    /**
     * Get file size formatted.
     */
    public function getFileSizeFormattedAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        if ($this->file_size < 1024) {
            return $this->file_size.' B';
        } elseif ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 1).' KB';
        } else {
            return round($this->file_size / 1048576, 1).' MB';
        }
    }
}
