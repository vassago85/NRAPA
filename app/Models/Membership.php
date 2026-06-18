<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Membership extends Model
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
        'membership_type_id',
        'membership_number',
        'payment_reference',
        'proof_of_payment_path',
        'status',
        'applied_at',
        'approved_at',
        'approved_by',
        'activated_at',
        'expires_at',
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'previous_membership_id',
        'notes',
        'dedicated_declaration_accepted_at',
        'source',
        'payment_email_sent_at',
        'payment_email_count',
        'welcome_email_sent_at',
        'approval_revoked_at',
        'approval_revoked_by',
        'approval_revoked_reason',
        'pop_reminder_sent_at',
        'pop_reminder_count',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'affiliated_club_id',
        'change_amount',
        'sage_invoice_id',
        'sage_invoice_number',
        'transfer_competency_document_id',
        'transfer_membership_document_id',
        'previous_association_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
            'revoked_at' => 'datetime',
            'payment_email_sent_at' => 'datetime',
            'payment_email_count' => 'integer',
            'welcome_email_sent_at' => 'datetime',
            'approval_revoked_at' => 'datetime',
            'pop_reminder_sent_at' => 'datetime',
            'pop_reminder_count' => 'integer',
            'payment_confirmed_at' => 'datetime',
            'dedicated_declaration_accepted_at' => 'datetime',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Record that a payment-instructions email was sent: stamp the time and
     * bump the running total so admins can see how many were sent.
     */
    public function recordPaymentEmailSent(): void
    {
        $this->update([
            'payment_email_sent_at' => now(),
            'payment_email_count' => ($this->payment_email_count ?? 0) + 1,
        ]);
    }

    /**
     * Record that a proof-of-payment reminder email was sent: stamp the time
     * and bump the running total.
     */
    public function recordPopReminderSent(): void
    {
        $this->update([
            'pop_reminder_sent_at' => now(),
            'pop_reminder_count' => ($this->pop_reminder_count ?? 0) + 1,
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Membership $membership) {
            if (empty($membership->uuid)) {
                $membership->uuid = (string) Str::uuid();
            }
            if (empty($membership->membership_number)) {
                $membership->membership_number = static::generateMembershipNumber($membership);
            }
            if (empty($membership->payment_reference)) {
                $membership->payment_reference = static::generatePaymentReference($membership);
            }
            if (empty($membership->applied_at)) {
                $membership->applied_at = now();
            }
        });

        static::saved(function (Membership $membership) {
            if ($membership->wasChanged('status')) {
                \Illuminate\Support\Facades\Cache::forget('sidebar_pending_total');
                \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
                \Illuminate\Support\Facades\Cache::forget('admin_members_stats');

                if ($membership->status === 'active') {
                    $user = $membership->user;
                    if ($user) {
                        \App\Helpers\TermsHelper::checkAndNotify($user);
                    }
                }
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
     * Generate a membership number from the user's permanent member number.
     */
    public static function generateMembershipNumber(?Membership $membership = null): string
    {
        $user = $membership?->user ?? ($membership?->user_id ? User::find($membership->user_id) : null);

        if ($user?->member_number) {
            return $user->formatted_member_number;
        }

        // Fallback: next sequential number (should rarely happen)
        $nextNumber = User::nextMemberNumber();

        return 'NRAPA-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique payment reference for EFT payments.
     * Format: PREFIX-SURNAME-XXXX (where XXXX is a random 4-digit number)
     */
    public static function generatePaymentReference(Membership $membership): string
    {
        $prefix = SystemSetting::get('bank_reference_prefix', 'NRAPA');

        // Get surname from user (if relationship loaded) or fetch it
        $user = $membership->user ?? User::find($membership->user_id);
        $surname = $user ? strtoupper(self::extractSurname($user->name)) : 'MEMBER';

        // Clean surname (remove special characters, limit length)
        $surname = preg_replace('/[^A-Z]/', '', $surname);
        $surname = substr($surname, 0, 10); // Max 10 chars for surname

        do {
            $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $reference = $prefix.'-'.$surname.'-'.$random;
        } while (static::where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Extract surname from full name (assumes last word is surname).
     */
    protected static function extractSurname(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        return count($parts) > 1 ? end($parts) : $fullName;
    }

    /**
     * Get the user that owns the membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Renewal-reminder log entries for this membership.
     */
    public function renewalReminders(): HasMany
    {
        return $this->hasMany(MembershipRenewalReminder::class);
    }

    /**
     * Get the membership type.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    /**
     * Get the admin who approved the membership.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who suspended the membership.
     */
    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Get the admin who revoked the membership.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Get the previous membership (for renewals).
     */
    public function previousMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'previous_membership_id');
    }

    /**
     * Get the next membership (renewal).
     */
    public function nextMembership(): HasOne
    {
        return $this->hasOne(Membership::class, 'previous_membership_id');
    }

    /**
     * Get certificates for this membership.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get dedicated status applications for this membership.
     */
    public function dedicatedStatusApplications(): HasMany
    {
        return $this->hasMany(DedicatedStatusApplication::class);
    }

    /**
     * Get the affiliated club (if this is a club membership).
     */
    public function affiliatedClub(): BelongsTo
    {
        return $this->belongsTo(AffiliatedClub::class);
    }

    /**
     * Competency certificate uploaded for a transfer application.
     */
    public function transferCompetencyDocument(): BelongsTo
    {
        return $this->belongsTo(MemberDocument::class, 'transfer_competency_document_id');
    }

    /**
     * Current-membership certificate from the previous association uploaded
     * for a transfer application.
     */
    public function transferMembershipDocument(): BelongsTo
    {
        return $this->belongsTo(MemberDocument::class, 'transfer_membership_document_id');
    }

    /**
     * Whether this membership is a transfer from another SAPS-accredited
     * association. Driven by the source flag, not the type slug.
     */
    public function isTransfer(): bool
    {
        return $this->source === 'transfer';
    }

    /**
     * Check if this is an affiliated club membership.
     */
    public function isAffiliatedClubMembership(): bool
    {
        return $this->affiliated_club_id !== null;
    }

    /**
     * Get the amount due for this membership.
     * Takes into account whether it's a new application, renewal, upgrade, or club membership.
     */
    public function getAmountDueAttribute(): float
    {
        // Affiliated club membership: use club fees
        if ($this->isAffiliatedClubMembership() && $this->affiliatedClub) {
            return $this->isRenewal()
                ? (float) $this->affiliatedClub->renewal_fee
                : (float) $this->affiliatedClub->initial_fee;
        }

        // Standard membership: use type fees
        if ($this->isRenewal()) {
            $renewalPrice = (float) $this->type->renewal_price;

            // The late-renewal penalty multiplier is SUSPENDED during the
            // platform launch grace (through 31 Dec 2026). The banner on the
            // approval page promises members "no late-renewal penalty" during
            // this period — this ensures the actual quote honours that promise.
            // From 1 Jan 2027 onwards the multiplier kicks back in.
            if ($this->isLateRenewal() && ! static::isExtendedGraceActive()) {
                $multiplier = (float) SystemSetting::get('late_renewal_fee_multiplier', 2);
                $renewalPrice *= $multiplier;
            }

            return $renewalPrice;
        }

        // Dedicated types: basic initial_price + upgrade_price
        if ($this->type->hasUpgradeFee()) {
            $basicType = MembershipType::where('slug', 'basic')->first();
            $basicInitial = $basicType ? (float) $basicType->initial_price : 0;

            return $basicInitial + (float) $this->type->upgrade_price;
        }

        // Basic membership: initial sign-up
        return (float) $this->type->initial_price;
    }

    /**
     * Check if this affiliated club membership has a valid competency certificate uploaded.
     * Returns null if not an affiliated club membership or competency not required.
     */
    public function hasValidCompetency(): ?bool
    {
        if (! $this->isAffiliatedClubMembership()) {
            return null;
        }

        $club = $this->affiliatedClub;
        if (! $club || ! $club->requires_competency) {
            return null;
        }

        return MemberDocument::where('user_id', $this->user_id)
            ->whereHas('documentType', fn ($q) => $q->where('slug', 'firearm-competency'))
            ->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get the number of approved activities this member has in the current activity year.
     */
    public function getApprovedActivityCountAttribute(): int
    {
        return ShootingActivity::where('user_id', $this->user_id)
            ->approved()
            ->withinActivityYear()
            ->count();
    }

    /**
     * Check if this affiliated club member meets the activity requirement.
     * Returns null if not an affiliated club membership.
     */
    public function meetsActivityRequirement(): ?bool
    {
        if (! $this->isAffiliatedClubMembership()) {
            return null;
        }

        $club = $this->affiliatedClub;
        if (! $club || $club->required_activities_per_year <= 0) {
            return null;
        }

        return $this->approved_activity_count >= $club->required_activities_per_year;
    }

    // ===== Status Checks (Attribute-Driven) =====

    /**
     * Check if the membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the membership is expired.
     */
    public function isExpired(): bool
    {
        if ($this->type && $this->type->isLifetime()) {
            return false;
        }

        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the membership requires renewal (based on type attributes).
     */
    public function requiresRenewal(): bool
    {
        return $this->type->requires_renewal;
    }

    /**
     * Get the configured renewal window (days before expiry).
     */
    public static function renewalWindowDays(): int
    {
        return (int) SystemSetting::get('renewal_window_days', 30);
    }

    /**
     * Get the configured grace period (days after expiry member can still renew).
     *
     * Policy:
     *  - Through 31 Dec 2026: 180 days (~6 months) — new-platform launch grace period
     *    while expiry reminders and the renewal flow stabilise.
     *  - From 1 Jan 2027 onwards: 90 days (~3 months) — standard ongoing policy.
     *
     * The policy is the source of truth so the system auto-flips at the date
     * boundary. An admin can still override by saving a custom value in the
     * `renewal_grace_period_days` system setting.
     */
    public static function renewalGracePeriodDays(): int
    {
        $hasOverride = SystemSetting::where('key', 'renewal_grace_period_days')->exists();
        if ($hasOverride) {
            return (int) SystemSetting::get('renewal_grace_period_days', static::policyGracePeriodDays());
        }

        return static::policyGracePeriodDays();
    }

    /**
     * Hard-coded business policy for the renewal grace period.
     * Returns 180 days through end of 2026, 90 days from 1 Jan 2027 onwards.
     */
    public static function policyGracePeriodDays(): int
    {
        return now()->lt(\Carbon\Carbon::create(2027, 1, 1)) ? 180 : 90;
    }

    /**
     * Is the system currently inside the extended new-platform grace window
     * (i.e. 6-month grace, ending 31 Dec 2026)?
     */
    public static function isExtendedGraceActive(): bool
    {
        return now()->lt(\Carbon\Carbon::create(2027, 1, 1));
    }

    /**
     * Check if the membership is renewable.
     *
     * Policy (May 2026 onwards): renewals are ALWAYS accepted regardless of
     * how long the membership has been expired. The submission goes to admin
     * for review, where they decide whether to apply a penalty/late fee based
     * on context (member's history, length of lapse, etc.).
     *
     * The grace-period helpers (`renewalGracePeriodDays`, `isExpiredBeyondGracePeriod`,
     * `isInRenewalWindow`) still exist and are used to surface guidance to admins
     * — they no longer hard-block the renewal flow.
     */
    public function isRenewable(): bool
    {
        if (! $this->requiresRenewal()) {
            return false;
        }

        if (! in_array($this->status, ['active', 'expired'])) {
            return false;
        }

        if ($this->nextMembership()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the membership is within the renewal window.
     * Returns true if within the configured days of expiry or already expired.
     */
    public function isInRenewalWindow(): bool
    {
        if ($this->type && $this->type->isLifetime()) {
            return false;
        }

        if (! $this->expires_at) {
            return false;
        }

        // Already expired — in the window (grace period checked separately)
        if ($this->expires_at->isPast()) {
            return true;
        }

        // Within configured window before expiry
        $windowDays = static::renewalWindowDays();

        return now()->diffInDays($this->expires_at, false) <= $windowDays;
    }

    /**
     * Check if the membership expired beyond the grace period.
     *
     * This used to hard-block renewals. As of May 2026, renewals are always
     * allowed; this flag is now used only to flag "late" renewals so that the
     * UI and admin review surfaces can highlight them and let admin decide
     * whether to apply a penalty fee.
     */
    public function isExpiredBeyondGracePeriod(): bool
    {
        if ($this->type && $this->type->isLifetime()) {
            return false;
        }

        if (! $this->expires_at) {
            return false;
        }

        if (! $this->expires_at->isPast()) {
            return false;
        }

        $graceDays = static::renewalGracePeriodDays();

        // If grace period is 0, any expiry means they must rejoin
        return now()->diffInDays($this->expires_at, false) < -$graceDays;
    }

    /**
     * Check if this is a late renewal (previous membership lapsed beyond the threshold).
     * Late renewals attract a penalty multiplier on the renewal fee.
     */
    public function isLateRenewal(): bool
    {
        if (! $this->isRenewal() || ! $this->previous_membership_id) {
            return false;
        }

        $previous = $this->previousMembership;
        if (! $previous || ! $previous->expires_at) {
            return false;
        }

        if (! $previous->expires_at->isPast()) {
            return false;
        }

        $thresholdDays = (int) SystemSetting::get('late_renewal_threshold_days', 90);

        return now()->diffInDays($previous->expires_at, false) < -$thresholdDays;
    }

    /**
     * Check if the membership will eventually require renewal but is not yet in the window.
     * Useful for showing "renewal opens on X" messaging.
     */
    public function isRenewalUpcoming(): bool
    {
        if (! $this->requiresRenewal()) {
            return false;
        }

        if (! in_array($this->status, ['active'])) {
            return false;
        }

        if ($this->nextMembership()->exists()) {
            return false;
        }

        if (! $this->expires_at) {
            return false;
        }

        $windowDays = static::renewalWindowDays();

        // Has an expiry in the future but more than the configured window away
        return ! $this->expires_at->isPast() && now()->diffInDays($this->expires_at, false) > $windowDays;
    }

    /**
     * Get the date when the renewal window opens.
     */
    public function getRenewalWindowOpensAtAttribute(): ?\DateTimeInterface
    {
        if (! $this->expires_at) {
            return null;
        }

        return $this->expires_at->copy()->subDays(static::renewalWindowDays());
    }

    /**
     * Get the date when the grace period ends (after which member must rejoin as new).
     */
    public function getGracePeriodEndsAtAttribute(): ?\DateTimeInterface
    {
        if (! $this->expires_at) {
            return null;
        }

        return $this->expires_at->copy()->addDays(static::renewalGracePeriodDays());
    }

    /**
     * Check if the membership allows dedicated status (attribute-driven).
     */
    public function allowsDedicatedStatus(): bool
    {
        return $this->type?->allows_dedicated_status ?? false;
    }

    // ===== State Transitions =====

    /**
     * Approve the membership application.
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
    }

    /**
     * Activate the membership.
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => $this->calculateRenewalAwareExpiry(),
        ]);

        if ($this->proof_of_payment_path) {
            \Illuminate\Support\Facades\Storage::disk('r2')->delete($this->proof_of_payment_path);
            $this->update(['proof_of_payment_path' => null]);
        }
    }

    /**
     * Suspend the membership.
     */
    public function suspend(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_by' => $admin->id,
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Revoke the membership.
     */
    public function revoke(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'revocation_reason' => $reason,
        ]);
    }

    /**
     * Mark the membership as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Retire every OTHER active membership for the same user.
     *
     * Used whenever a new membership row becomes `active` (approval, renewal
     * approval, admin manual create/edit). Guarantees that only the latest
     * active row carries status='active' at any moment, so the UI never has
     * to guess which row is "the current" one.
     *
     * Returns the number of rows that were retired.
     */
    public function retireOtherActiveMemberships(): int
    {
        if (! $this->user_id) {
            return 0;
        }

        return static::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'expires_at' => now(),
            ]);
    }

    // ===== Scopes =====

    /**
     * Scope to only active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to only pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'applied');
    }

    /**
     * Scope to only expired memberships.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            });
    }

    // ===== Billing Scopes =====

    /**
     * Scope to billable memberships (excludes imports, rejections,
     * and pending type-change requests that haven't been paid yet).
     *
     * Billable sources: web, admin
     * Non-billable: import (admin "Remove from billing" sets this),
     *               revoked status (rejections), pending_change status.
     */
    public function scopeBillable($query)
    {
        return $query->whereIn('source', ['web', 'admin'])
            ->whereNotIn('status', ['revoked', 'pending_change']);
    }

    /**
     * Scope to memberships where NRAPA actually received payment in the
     * given month. Drives the "Payments Received" / billing report so the
     * counts only reflect money that hit the books.
     */
    public function scopePaidInMonth($query, int $year, int $month)
    {
        return $query->whereNotNull('payment_confirmed_at')
            ->whereYear('payment_confirmed_at', $year)
            ->whereMonth('payment_confirmed_at', $month);
    }

    /**
     * Scope to new memberships (not renewals) where payment was confirmed
     * in the given month.
     */
    public function scopeNewInMonth($query, int $year, int $month)
    {
        return $query->whereNull('previous_membership_id')
            ->paidInMonth($year, $month);
    }

    /**
     * Scope to renewals where payment was confirmed in the given month.
     */
    public function scopeRenewalsInMonth($query, int $year, int $month)
    {
        return $query->whereNotNull('previous_membership_id')
            ->paidInMonth($year, $month);
    }

    /**
     * Scope to memberships approved in a specific month. Retained for any
     * non-billing callers that want approval-date semantics; the billing
     * report itself uses paidInMonth() so it only counts confirmed payments.
     */
    public function scopeApprovedInMonth($query, int $year, int $month)
    {
        return $query->whereYear('approved_at', $year)
            ->whereMonth('approved_at', $month)
            ->whereNotNull('approved_at');
    }

    /**
     * Check if this is a renewal.
     */
    public function isRenewal(): bool
    {
        return $this->previous_membership_id !== null;
    }

    /**
     * Compute the correct expires_at for this membership at activation time,
     * respecting the renewal-anchor policy: for renewals, the new expiry is
     * anchored on the previous membership's expires_at (the member's sign-up
     * anniversary) instead of "today". For first-time applications, falls back
     * to a normal {@see MembershipType::calculateExpiryDate()} from now.
     *
     * The result is always capped at `now() + duration_months` so that a
     * non-lifetime member can never be active for longer than one full cycle
     * from this activation moment. This prevents an early renewer from
     * effectively buying ~2 years of active membership in one go.
     *
     * Always returns a fresh Carbon instance (or null for non-expiring types).
     */
    public function calculateRenewalAwareExpiry(): ?\DateTimeInterface
    {
        if (! $this->type) {
            return null;
        }

        $expiry = $this->isRenewal()
            ? $this->type->calculateRenewalExpiryDate($this->previousMembership?->expires_at)
            : $this->type->calculateExpiryDate(now());

        if ($expiry === null) {
            return null;
        }

        if ($this->type->duration_months) {
            $cap = now()->addMonths($this->type->duration_months);
            $expiryCarbon = \Carbon\Carbon::parse($expiry);

            return $expiryCarbon->gt($cap) ? $cap : $expiryCarbon;
        }

        return $expiry;
    }

    /**
     * Check if this membership is billable.
     */
    public function isBillable(): bool
    {
        return in_array($this->source, ['web', 'admin']);
    }
}
