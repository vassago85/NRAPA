<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EndorsementRequest extends Model
{
    use SoftDeletes;

    // Request type constants
    public const TYPE_NEW = 'new';
    public const TYPE_RENEWAL = 'renewal';

    // Minimum activity requirements (can be overridden via SystemSetting)
    public const DEFAULT_MIN_ACTIVITIES_SPORT = 2; // per 12 months
    public const DEFAULT_MIN_ACTIVITIES_HUNTER = 2; // per 24 months

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PENDING_DOCUMENTS = 'pending_documents';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    // Purpose constants
    public const PURPOSE_SECTION_16 = 'section_16_application';
    public const PURPOSE_STATUS_CONFIRMATION = 'status_confirmation';
    public const PURPOSE_LICENCE_RENEWAL = 'licence_renewal';
    public const PURPOSE_ADDITIONAL_FIREARM = 'additional_firearm';
    public const PURPOSE_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'request_type',
        'status',
        'purpose',
        'purpose_other_text',
        'declaration_accepted_at',
        'declaration_text',
        'submitted_at',
        'reviewed_at',
        'issued_at',
        'rejected_at',
        'cancelled_at',
        'reviewer_id',
        'issued_by',
        'member_notes',
        'admin_notes',
        'rejection_reason',
        'letter_reference',
        'letter_file_path',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'declaration_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'issued_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EndorsementRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
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
     * Get the user that owns this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the firearm for this request.
     */
    public function firearm(): HasOne
    {
        return $this->hasOne(EndorsementFirearm::class);
    }

    /**
     * Get the components for this request.
     */
    public function components(): HasMany
    {
        return $this->hasMany(EndorsementComponent::class);
    }

    /**
     * Get the documents for this request.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EndorsementDocument::class);
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user who issued the letter.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // ===== Eligibility Checks =====

    /**
     * Check if a user is eligible to request an endorsement letter.
     * Requirements:
     * 1. Must have passed knowledge test (once-off)
     * 2. Must have required documents on file (verified)
     * 3. Must have minimum number of approved activities
     */
    public static function checkUserEligibility(User $user): array
    {
        $errors = [];
        $warnings = [];

        // 1. Knowledge Test Check (once-off requirement)
        if (!$user->hasPassedKnowledgeTest()) {
            $errors[] = [
                'type' => 'knowledge_test',
                'message' => 'You must pass the dedicated status knowledge test before requesting an endorsement letter.',
                'action' => 'Take the knowledge test',
                'route' => 'knowledge-test.index',
            ];
        }

        // 2. Required Documents Check
        $missingDocs = self::getMissingRequiredDocuments($user);
        if (count($missingDocs) > 0) {
            foreach ($missingDocs as $doc) {
                $errors[] = [
                    'type' => 'document',
                    'message' => "Missing required document: {$doc['name']}",
                    'action' => 'Upload document',
                    'route' => 'documents.index',
                ];
            }
        }

        // 3. Activity Requirements Check
        $activityCheck = self::checkActivityRequirements($user);
        if (!$activityCheck['met']) {
            $errors[] = [
                'type' => 'activities',
                'message' => $activityCheck['message'],
                'action' => 'Submit activities',
                'route' => 'activities.index',
                'details' => $activityCheck,
            ];
        }

        // 4. Active membership check
        $membership = $user->activeMembership;
        if (!$membership) {
            $errors[] = [
                'type' => 'membership',
                'message' => 'You must have an active membership to request an endorsement letter.',
                'action' => 'Apply for membership',
                'route' => 'membership.apply',
            ];
        }

        return [
            'eligible' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get missing required documents for endorsement.
     */
    public static function getMissingRequiredDocuments(User $user): array
    {
        $missing = [];

        // Required document type slugs for endorsement
        $requiredDocSlugs = [
            'id-document' => 'South African ID Document',
            'proof-of-address' => 'Proof of Address',
        ];

        foreach ($requiredDocSlugs as $slug => $name) {
            $docType = DocumentType::where('slug', $slug)->first();
            if (!$docType) continue;

            // Check if user has a valid (verified, not expired) document of this type
            $hasValid = MemberDocument::where('user_id', $user->id)
                ->where('document_type_id', $docType->id)
                ->where('status', 'verified')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (!$hasValid) {
                $missing[] = [
                    'slug' => $slug,
                    'name' => $name,
                    'document_type_id' => $docType->id,
                ];
            }
        }

        return $missing;
    }

    /**
     * Check if user meets activity requirements.
     */
    public static function checkActivityRequirements(User $user): array
    {
        // Get minimum requirements from settings or use defaults
        $minActivitiesSport = SystemSetting::get('endorsement_min_activities_sport', self::DEFAULT_MIN_ACTIVITIES_SPORT);
        $minActivitiesHunter = SystemSetting::get('endorsement_min_activities_hunter', self::DEFAULT_MIN_ACTIVITIES_HUNTER);
        $activityPeriodMonths = SystemSetting::get('endorsement_activity_period_months', 12);

        // Count approved activities in the period
        $periodStart = now()->subMonths($activityPeriodMonths);
        $approvedCount = ShootingActivity::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('activity_date', '>=', $periodStart)
            ->count();

        // For now, use sport shooter requirement (can be made dynamic based on membership type)
        $required = $minActivitiesSport;

        // Check if user's membership type indicates hunter
        $membership = $user->activeMembership;
        if ($membership && $membership->type) {
            $dedicatedType = $membership->type->dedicated_type ?? null;
            if ($dedicatedType === 'hunter') {
                $required = $minActivitiesHunter;
                $activityPeriodMonths = SystemSetting::get('endorsement_hunter_activity_period_months', 24);
                $periodStart = now()->subMonths($activityPeriodMonths);
                $approvedCount = ShootingActivity::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('activity_date', '>=', $periodStart)
                    ->count();
            }
        }

        $met = $approvedCount >= $required;

        return [
            'met' => $met,
            'approved_count' => $approvedCount,
            'required' => $required,
            'period_months' => $activityPeriodMonths,
            'message' => $met 
                ? "You have {$approvedCount} approved activities (minimum {$required} required)."
                : "You need at least {$required} approved activities in the last {$activityPeriodMonths} months. You currently have {$approvedCount}.",
        ];
    }

    /**
     * Get user's eligibility summary for display.
     */
    public static function getEligibilitySummary(User $user): array
    {
        $eligibility = self::checkUserEligibility($user);
        
        return [
            'eligible' => $eligibility['eligible'],
            'knowledge_test_passed' => $user->hasPassedKnowledgeTest(),
            'documents_complete' => count(self::getMissingRequiredDocuments($user)) === 0,
            'activities_met' => self::checkActivityRequirements($user)['met'],
            'activity_details' => self::checkActivityRequirements($user),
            'missing_documents' => self::getMissingRequiredDocuments($user),
            'errors' => $eligibility['errors'],
        ];
    }

    // ===== Static Options =====

    /**
     * Get request type options.
     */
    public static function getRequestTypeOptions(): array
    {
        return [
            self::TYPE_NEW => 'New Endorsement',
            self::TYPE_RENEWAL => 'Renewal Endorsement',
        ];
    }

    /**
     * Get status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_PENDING_DOCUMENTS => 'Pending Documents',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get purpose options.
     */
    public static function getPurposeOptions(): array
    {
        return [
            self::PURPOSE_SECTION_16 => 'Section 16 Licence Application',
            self::PURPOSE_STATUS_CONFIRMATION => 'Dedicated Status Confirmation',
            self::PURPOSE_LICENCE_RENEWAL => 'Firearm Licence Renewal',
            self::PURPOSE_ADDITIONAL_FIREARM => 'Additional Firearm Application',
            self::PURPOSE_OTHER => 'Other',
        ];
    }

    /**
     * Get required document types based on request type.
     */
    public static function getRequiredDocumentTypes(string $requestType): array
    {
        $baseRequired = [
            'sa_id',
            'dedicated_status_certificate',
            'activity_proof',
        ];

        if ($requestType === self::TYPE_NEW) {
            return array_merge($baseRequired, [
                'proof_of_address',
                'membership_proof',
            ]);
        }

        // Renewal - fewer required, some optional
        return $baseRequired;
    }

    /**
     * Get optional document types based on request type.
     */
    public static function getOptionalDocumentTypes(string $requestType): array
    {
        if ($requestType === self::TYPE_NEW) {
            return [
                'competency_certificate',
            ];
        }

        // Renewal has more optional docs
        return [
            'proof_of_address',
            'previous_endorsement_letter',
            'firearm_licence_card',
            'competency_certificate',
        ];
    }

    // ===== Status Checks =====

    /**
     * Check if request is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if request is submitted.
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_PENDING_DOCUMENTS,
        ]);
    }

    /**
     * Check if request is issued.
     */
    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if this is a renewal request.
     */
    public function isRenewal(): bool
    {
        return $this->request_type === self::TYPE_RENEWAL;
    }

    /**
     * Check if this is a new request.
     */
    public function isNew(): bool
    {
        return $this->request_type === self::TYPE_NEW;
    }

    /**
     * Check if component requests are allowed.
     */
    public function allowsComponents(): bool
    {
        return $this->isRenewal();
    }

    /**
     * Check if declaration has been accepted.
     */
    public function hasDeclaration(): bool
    {
        return $this->declaration_accepted_at !== null;
    }

    // ===== Validation =====

    /**
     * Check if the request can be submitted.
     */
    public function canSubmit(): bool
    {
        // Must be in draft status
        if (!$this->isDraft()) {
            return false;
        }

        // Must have firearm details
        if (!$this->firearm) {
            return false;
        }

        // Must have declaration accepted
        if (!$this->hasDeclaration()) {
            return false;
        }

        // Must have all required documents uploaded or system verified
        if (!$this->hasAllRequiredDocuments()) {
            return false;
        }

        // Must have purpose selected
        if (!$this->purpose) {
            return false;
        }

        return true;
    }

    /**
     * Get validation errors preventing submission.
     */
    public function getSubmissionErrors(): array
    {
        $errors = [];

        if (!$this->isDraft()) {
            $errors[] = 'Request is not in draft status.';
        }

        if (!$this->firearm) {
            $errors[] = 'Firearm details are required.';
        }

        if (!$this->hasDeclaration()) {
            $errors[] = 'You must accept the declaration.';
        }

        if (!$this->purpose) {
            $errors[] = 'Purpose is required.';
        }

        $missingDocs = $this->getMissingRequiredDocuments();
        foreach ($missingDocs as $docType) {
            $errors[] = "Missing required document: " . self::getDocumentTypeLabel($docType);
        }

        return $errors;
    }

    /**
     * Check if all required documents are uploaded.
     */
    public function hasAllRequiredDocuments(): bool
    {
        return count($this->getMissingRequiredDocuments()) === 0;
    }

    /**
     * Get list of missing required documents.
     */
    public function getMissingRequiredDocuments(): array
    {
        $required = self::getRequiredDocumentTypes($this->request_type);
        $uploaded = $this->documents()
            ->whereIn('status', ['uploaded', 'verified', 'system_verified'])
            ->pluck('document_type')
            ->toArray();

        return array_diff($required, $uploaded);
    }

    /**
     * Get document type label.
     */
    public static function getDocumentTypeLabel(string $type): string
    {
        return match($type) {
            'sa_id' => 'South African ID',
            'proof_of_address' => 'Proof of Address',
            'dedicated_status_certificate' => 'Dedicated Status Certificate',
            'membership_proof' => 'Membership Proof',
            'activity_proof' => 'Activity Proof / Participation Record',
            'previous_endorsement_letter' => 'Previous Endorsement Letter',
            'firearm_licence_card' => 'Firearm Licence Card',
            'competency_certificate' => 'Competency Certificate',
            'other' => 'Other Document',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    // ===== Actions =====

    /**
     * Submit the request for review.
     */
    public function submit(): bool
    {
        if (!$this->canSubmit()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark as under review.
     */
    public function markUnderReview(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_at' => now(),
            'reviewer_id' => $admin->id,
        ]);
    }

    /**
     * Mark as pending documents.
     */
    public function markPendingDocuments(User $admin, ?string $notes = null): void
    {
        $updates = [
            'status' => self::STATUS_PENDING_DOCUMENTS,
            'reviewer_id' => $admin->id,
        ];

        if ($notes) {
            $updates['admin_notes'] = $notes;
        }

        $this->update($updates);
    }

    /**
     * Issue the endorsement letter.
     */
    public function issue(User $admin, string $letterReference, ?string $letterPath = null): void
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by' => $admin->id,
            'letter_reference' => $letterReference,
            'letter_file_path' => $letterPath,
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'reviewer_id' => $admin->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel the request.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Generate a unique letter reference.
     */
    public static function generateLetterReference(): string
    {
        $year = date('Y');
        $prefix = 'END';
        
        // Get the last reference for this year
        $lastRef = self::whereYear('issued_at', $year)
            ->whereNotNull('letter_reference')
            ->orderBy('letter_reference', 'desc')
            ->value('letter_reference');

        if ($lastRef && preg_match('/(\d+)$/', $lastRef, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        } else {
            $nextNum = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $nextNum);
    }

    // ===== Scopes =====

    /**
     * Scope to drafts only.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to submitted requests.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Scope to pending review.
     */
    public function scopePendingReview($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_PENDING_DOCUMENTS,
        ]);
    }

    /**
     * Scope to issued requests.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope to new requests.
     */
    public function scopeNewRequests($query)
    {
        return $query->where('request_type', self::TYPE_NEW);
    }

    /**
     * Scope to renewal requests.
     */
    public function scopeRenewals($query)
    {
        return $query->where('request_type', self::TYPE_RENEWAL);
    }

    // ===== Accessors =====

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the request type label.
     */
    public function getRequestTypeLabelAttribute(): string
    {
        return self::getRequestTypeOptions()[$this->request_type] ?? ucfirst($this->request_type);
    }

    /**
     * Get the purpose label.
     */
    public function getPurposeLabelAttribute(): string
    {
        if ($this->purpose === self::PURPOSE_OTHER) {
            return $this->purpose_other_text ?? 'Other';
        }
        return self::getPurposeOptions()[$this->purpose] ?? '';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
            self::STATUS_SUBMITTED => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            self::STATUS_UNDER_REVIEW => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            self::STATUS_PENDING_DOCUMENTS => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            self::STATUS_ISSUED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            self::STATUS_CANCELLED => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }
}
