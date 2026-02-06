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
        'source',
        'payment_email_sent_at',
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
        ];
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
                $membership->membership_number = static::generateMembershipNumber();
            }
            if (empty($membership->payment_reference)) {
                $membership->payment_reference = static::generatePaymentReference($membership);
            }
            if (empty($membership->applied_at)) {
                $membership->applied_at = now();
            }
        });

        // Send terms acceptance email when membership is activated
        static::updated(function (Membership $membership) {
            // Check if status changed to 'active'
            if ($membership->wasChanged('status') && $membership->status === 'active') {
                $user = $membership->user;
                if ($user) {
                    \App\Helpers\TermsHelper::checkAndNotify($user);
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
     * Generate a unique membership number.
     */
    public static function generateMembershipNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'NRAPA';

        do {
            $number = $prefix . '-' . $year . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('membership_number', $number)->exists());

        return $number;
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
            $reference = $prefix . '-' . $surname . '-' . $random;
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
        if ($this->status === 'expired') {
            return true;
        }

        // Check expiry date if set (attribute-driven)
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
     * Check if the membership is renewable.
     */
    public function isRenewable(): bool
    {
        // Must require renewal (attribute check)
        if (! $this->requiresRenewal()) {
            return false;
        }

        // Must be active or expired
        if (! in_array($this->status, ['active', 'expired'])) {
            return false;
        }

        // Must not already have a renewal
        if ($this->nextMembership()->exists()) {
            return false;
        }

        return true;
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
            'expires_at' => $this->type->calculateExpiryDate(now()),
        ]);
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
     * Scope to billable memberships (excludes imports).
     * Billable sources: web, admin
     * Non-billable: import
     */
    public function scopeBillable($query)
    {
        return $query->whereIn('source', ['web', 'admin']);
    }

    /**
     * Scope to new memberships (not renewals) in a specific month.
     */
    public function scopeNewInMonth($query, int $year, int $month)
    {
        return $query->whereNull('previous_membership_id')
            ->whereYear('approved_at', $year)
            ->whereMonth('approved_at', $month)
            ->whereNotNull('approved_at');
    }

    /**
     * Scope to renewals in a specific month.
     */
    public function scopeRenewalsInMonth($query, int $year, int $month)
    {
        return $query->whereNotNull('previous_membership_id')
            ->whereYear('approved_at', $year)
            ->whereMonth('approved_at', $month)
            ->whereNotNull('approved_at');
    }

    /**
     * Scope to memberships approved in a specific month.
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
     * Check if this membership is billable.
     */
    public function isBillable(): bool
    {
        return in_array($this->source, ['web', 'admin']);
    }
}
