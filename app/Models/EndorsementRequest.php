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
    public const STATUS_APPROVED = 'approved'; // Approved but letter not yet generated
    public const STATUS_ISSUED = 'issued'; // Letter generated/issued
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
        'expires_at',
        'rejected_at',
        'cancelled_at',
        'reviewer_id',
        'issued_by',
        'member_notes',
        'admin_notes',
        'rejection_reason',
        'letter_reference',
        'letter_file_path',
        'dedicated_status_compliant',
        'dedicated_category',
        'dedicated_status_snapshot_at',
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
            'expires_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'dedicated_status_snapshot_at' => 'datetime',
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
     * Get all comments for this request.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Comment::class, 'commentable');
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
     * For Proof of Address: requires document to be verified and not older than 3 months.
     * ID can be satisfied by either 'identity-document' or 'id-document' slug.
     */
    public static function getMissingRequiredDocuments(User $user): array
    {
        $missing = [];

        // ID: accept any of these slugs (identity-document or id-document)
        $idSlugs = ['identity-document', 'id-document'];
        $idTypes = DocumentType::whereIn('slug', $idSlugs)->pluck('id');
        $hasValidId = $idTypes->isNotEmpty() && MemberDocument::where('user_id', $user->id)
            ->whereIn('document_type_id', $idTypes)
            ->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
        if (!$hasValidId) {
            $missing[] = ['slug' => 'identity-document', 'name' => 'ID', 'document_type_id' => null];
        }

        // Proof of Address: single slug, with 3-month age check
        $poaSlug = 'proof-of-address';
        $poaType = DocumentType::where('slug', $poaSlug)->first();
        if ($poaType) {
            $validPoa = MemberDocument::where('user_id', $user->id)
                ->where('document_type_id', $poaType->id)
                ->where('status', 'verified')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('verified_at', 'desc')
                ->first();
            $hasValidPoa = $validPoa !== null;
            if ($hasValidPoa) {
                $threeMonthsAgo = now()->subMonths(3);
                if ($validPoa->verified_at && $validPoa->verified_at->lt($threeMonthsAgo)) {
                    $hasValidPoa = false;
                }
            }
            if (!$hasValidPoa) {
                $missing[] = [
                    'slug' => $poaSlug,
                    'name' => 'Proof of Address',
                    'document_type_id' => $poaType->id,
                ];
            }
        }

        return $missing;
    }

    /**
     * Check Proof of Address compliance for dedicated status.
     * Returns compliance info without requiring new upload (only checks age).
     */
    public static function checkProofOfAddressCompliance(User $user): array
    {
        $docType = DocumentType::where('slug', 'proof-of-address')->first();
        
        if (!$docType) {
            return [
                'has_document' => false,
                'is_compliant' => false,
                'is_older_than_3_months' => false,
                'verified_at' => null,
                'age_days' => null,
                'message' => 'Proof of Address document type not found',
            ];
        }

        // Get the most recent verified document
        $validDocument = MemberDocument::where('user_id', $user->id)
            ->where('document_type_id', $docType->id)
            ->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('verified_at', 'desc')
            ->first();

        if (!$validDocument || !$validDocument->verified_at) {
            return [
                'has_document' => false,
                'is_compliant' => false,
                'is_older_than_3_months' => true,
                'verified_at' => null,
                'age_days' => null,
                'message' => 'No verified Proof of Address found',
            ];
        }

        $threeMonthsAgo = now()->subMonths(3);
        $isOlderThan3Months = $validDocument->verified_at->lt($threeMonthsAgo);
        $ageDays = $validDocument->verified_at->diffInDays(now());

        return [
            'has_document' => true,
            'is_compliant' => !$isOlderThan3Months,
            'is_older_than_3_months' => $isOlderThan3Months,
            'verified_at' => $validDocument->verified_at,
            'age_days' => $ageDays,
            'age_months' => round($ageDays / 30, 1),
            'message' => $isOlderThan3Months 
                ? "Proof of Address is older than 3 months ({$ageDays} days old). New POA will be required when requesting an endorsement."
                : "Proof of Address is up to date ({$ageDays} days old).",
        ];
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
        $membershipType = $membership?->type;
        if ($membership && $membershipType) {
            $dedicatedType = $membershipType->dedicated_type ?? null;
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
            self::STATUS_APPROVED => 'Approved',
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
     * Check if the request can be edited.
     * Only draft requests can be edited.
     */
    public function canEdit(): bool
    {
        return $this->isDraft();
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
     * Check if request is approved (ready for letter generation).
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
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

        // Must have either firearm or at least one component (component-only requests allowed)
        $hasFirearm = (bool) $this->firearm;
        $hasComponents = $this->components()->exists();
        if (!$hasFirearm && !$hasComponents) {
            return false;
        }

        // Must have declaration accepted
        if (!$this->hasDeclaration()) {
            return false;
        }

        // Purpose is defaulted on save if not set (Section 16 dedicated) – not required for canSubmit

        // Note: Documents are checked but not blocking submission
        // Admin can request documents after submission if needed
        // This allows members to submit even if auto-verification didn't work perfectly

        return true;
    }

    /**
     * Get validation errors preventing submission.
     */
    public function getSubmissionErrors(): array
    {
        $errors = [];
        
        // Check terms acceptance
        $activeTerms = \App\Models\TermsVersion::active();
        if ($activeTerms && !$this->user->hasAcceptedActiveTerms()) {
            $errors[] = 'You must accept the Terms & Conditions before submitting endorsement requests.';
        }

        if (!$this->isDraft()) {
            $errors[] = 'Request is not in draft status.';
        }

        $hasFirearm = (bool) $this->firearm;
        $hasComponents = $this->components()->exists();
        if (!$hasFirearm && !$hasComponents) {
            $errors[] = 'Either firearm details or at least one component is required.';
        }

        if (!$this->hasDeclaration()) {
            $errors[] = 'You must accept the declaration.';
        }

        // Note: Missing documents are warnings, not blockers
        // Admin can request documents after submission if needed
        $missingDocs = $this->getMissingRequestDocuments();
        if (count($missingDocs) > 0) {
            $docLabels = array_map(fn($doc) => self::getDocumentTypeLabel($doc), $missingDocs);
            $errors[] = "Note: Some documents may need to be uploaded: " . implode(', ', $docLabels) . ". Admin can request these after submission.";
        }

        return $errors;
    }

    /**
     * Check if all required documents are uploaded.
     */
    public function hasAllRequiredDocuments(): bool
    {
        return count($this->getMissingRequestDocuments()) === 0;
    }

    /**
     * Get list of missing required documents for this request.
     */
    public function getMissingRequestDocuments(): array
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

        $result = $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Refresh the model to ensure status is updated
        $this->refresh();

        // Notify admins about the new endorsement request
        if ($result) {
            $this->notifyAdminsOfSubmission();
        }

        return $result;
    }

    /**
     * Notify admins when an endorsement request is submitted.
     */
    protected function notifyAdminsOfSubmission(): void
    {
        try {
            $ntfyService = app(\App\Services\NtfyService::class);
            
            $title = 'New Endorsement Request Submitted';
            $subject = $this->firearm
                ? (trim(($this->firearm->make ?? '') . ' ' . ($this->firearm->model ?? '')) ?: 'a firearm')
                : ($this->components->first()?->summary ?? 'a component');
            $message = sprintf(
                '%s has submitted an endorsement request for %s. Review and approve at: %s',
                $this->user->name,
                $subject,
                route('admin.endorsements.show', $this->uuid)
            );

            $ntfyService->notifyAdmins(
                'endorsement_request',
                $title,
                $message,
                'high',
                [
                    'endorsement_request_id' => $this->id,
                    'endorsement_request_uuid' => $this->uuid,
                    'user_id' => $this->user_id,
                    'user_name' => $this->user->name,
                ]
            );
        } catch (\Exception $e) {
            // Log error but don't fail the submission
            \Illuminate\Support\Facades\Log::error('Failed to send endorsement request notification', [
                'endorsement_request_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
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
     * Approve the endorsement request (allows letter generation).
     */
    public function approve(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewer_id' => $admin->id,
        ]);

        if ($notes) {
            $this->update(['admin_notes' => $notes]);
        }
    }

    /**
     * Issue the endorsement letter (can only be called on approved requests).
     * Requires dedicated_status_compliant and dedicated_category to be set.
     */
    public function issue(User $admin, string $letterReference, ?string $letterPath = null, ?bool $dedicatedStatusCompliant = null, ?string $dedicatedCategory = null): void
    {
        // Ensure request is approved before issuing letter
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \Exception('Endorsement request must be approved before letter can be issued.');
        }

        // If dedicated status not provided, determine from current membership
        if ($dedicatedStatusCompliant === null || $dedicatedCategory === null) {
            $eligibility = self::getEligibilitySummary($this->user);
            $dedicatedStatusCompliant = $eligibility['eligible'] ?? false;
            
            // Determine dedicated category from membership type
            $membership = $this->user->activeMembership;
            $dedicatedType = $membership?->type?->dedicated_type ?? null;
            
            if ($dedicatedCategory === null) {
                $dedicatedCategory = match($dedicatedType) {
                    'sport' => 'Dedicated Sport Shooter',
                    'hunter' => 'Dedicated Hunter',
                    'both' => 'Dedicated Sport Shooter & Dedicated Hunter',
                    default => null,
                };
            }
        }

        // Block issuance if not compliant
        if (!$dedicatedStatusCompliant) {
            throw new \Exception('Dedicated Status is not compliant. Endorsement cannot be issued.');
        }

        // Ensure dedicated category is set
        if (empty($dedicatedCategory)) {
            throw new \Exception('Dedicated Category must be specified before endorsement can be issued.');
        }

        $issuedAt = now();
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->copy()->addYear(), // Endorsement letters expire 1 year after issue
            'issued_by' => $admin->id,
            'letter_reference' => $letterReference,
            'letter_file_path' => $letterPath,
            'dedicated_status_compliant' => $dedicatedStatusCompliant,
            'dedicated_category' => $dedicatedCategory,
            'dedicated_status_snapshot_at' => now(),
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

    /**
     * Scope to valid (not expired) issued letters.
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to expired issued letters.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
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
     * Check if the endorsement letter is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->isIssued() || !$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the endorsement letter is expiring soon (within 30 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->isIssued() || !$this->expires_at || $this->is_expired) {
            return false;
        }

        return $this->expires_at->isBefore(now()->addDays(30));
    }

    /**
     * Get days until expiry (negative if expired).
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->isIssued() || !$this->expires_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->expires_at, false);
    }

    /**
     * Get dedicated status label (Compliant, Non-Compliant, or Not Recorded (Legacy)).
     */
    public function getDedicatedStatusLabelAttribute(): string
    {
        if ($this->dedicated_status_compliant === null) {
            return 'Not Recorded (Legacy)';
        }
        
        return $this->dedicated_status_compliant ? 'Compliant' : 'Non-Compliant';
    }

    /**
     * Get dedicated category label (with legacy fallback).
     */
    public function getDedicatedCategoryLabelAttribute(): string
    {
        if (empty($this->dedicated_category)) {
            return 'Not Recorded (Legacy)';
        }
        
        return $this->dedicated_category;
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
            self::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
            self::STATUS_ISSUED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            self::STATUS_CANCELLED => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }
}
