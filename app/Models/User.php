<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Role hierarchy constants.
     */
    public const ROLE_DEVELOPER = 'developer';

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    /**
     * Admin type constants.
     */
    public const ADMIN_TYPE_SUPER = 'super_admin';

    public const ADMIN_TYPE_STANDARD = 'standard_admin';

    /**
     * Role hierarchy (higher index = higher privilege).
     */
    public const ROLE_HIERARCHY = [
        self::ROLE_MEMBER => 0,
        self::ROLE_ADMIN => 1,
        self::ROLE_OWNER => 2,
        self::ROLE_DEVELOPER => 3,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Maximum logins allowed without 2FA for admins/owners.
     */
    public const MAX_LOGINS_WITHOUT_2FA = 10;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'id_number',
        'phone',
        'date_of_birth',
        'physical_address',
        'postal_address',
        'password',
        'is_admin',
        'role',
        'admin_type',
        'nominated_by',
        'nominated_at',
        'logins_without_2fa',
        'last_2fa_reminder_at',
        'email_verified_at',
        'welcome_letter_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'id_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_admin' => 'boolean',
            'nominated_at' => 'datetime',
            'last_2fa_reminder_at' => 'datetime',
            'welcome_letter_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the user who nominated this user.
     */
    public function nominatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    /**
     * Get users nominated by this user.
     */
    public function nominees(): HasMany
    {
        return $this->hasMany(User::class, 'nominated_by');
    }

    /**
     * Check if user is a developer (highest level).
     */
    public function isDeveloper(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    /**
     * Check if user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->is_admin;
    }

    /**
     * Check if user has at least the given role level.
     */
    public function hasRoleLevel(string $role): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$this->role] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$role] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if user can manage the given role.
     * Developers can manage owners and below.
     * Owners can manage admins only.
     */
    public function canManageRole(string $role): bool
    {
        if ($this->isDeveloper()) {
            return true; // Developers can manage everyone
        }

        if ($this->isOwner()) {
            return $role === self::ROLE_ADMIN; // Owners can only manage admins
        }

        return false;
    }

    /**
     * Check if user can manage another user.
     */
    public function canManageUser(User $user): bool
    {
        // Can't manage yourself
        if ($this->id === $user->id) {
            return false;
        }

        // Developers can manage anyone except other developers
        if ($this->isDeveloper()) {
            return ! $user->isDeveloper();
        }

        // Owners can manage admins they nominated
        if ($this->isOwner()) {
            return $user->isAdmin() && $user->nominated_by === $this->id;
        }

        return false;
    }

    /**
     * Get role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_DEVELOPER => 'Site Developer',
            self::ROLE_OWNER => 'Owner',
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MEMBER => 'Member',
            default => 'Unknown',
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        // When soft-deleting, modify email and id_number to free them up for reuse
        // and clean up pending/draft endorsement requests and other orphaned data
        static::deleting(function (User $user) {
            if (! $user->isForceDeleting()) {
                $timestamp = now()->timestamp;
                $user->email = "deleted_{$timestamp}_{$user->email}";
                if ($user->id_number) {
                    $user->id_number = "deleted_{$timestamp}_{$user->id_number}";
                }
                $user->saveQuietly();

                // Clean up endorsement requests and their children
                try {
                    $endorsementIds = \App\Models\EndorsementRequest::where('user_id', $user->id)->pluck('id');
                    if ($endorsementIds->isNotEmpty()) {
                        \App\Models\EndorsementDocument::whereIn('endorsement_request_id', $endorsementIds)->delete();
                        \App\Models\EndorsementFirearm::whereIn('endorsement_request_id', $endorsementIds)->delete();
                        \App\Models\EndorsementComponent::whereIn('endorsement_request_id', $endorsementIds)->delete();
                        \App\Models\EndorsementRequest::where('user_id', $user->id)->delete();
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to clean up endorsement data on user delete', [
                        'user_id' => $user->id, 'error' => $e->getMessage(),
                    ]);
                }

                // Clean up calibre requests
                try {
                    \App\Models\CalibreRequest::where('user_id', $user->id)->delete();
                } catch (\Exception $e) {
                    // table may not exist
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
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get all memberships for the user.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the active membership for the user.
     */
    public function activeMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->where('status', 'active');
    }

    /**
     * Get all documents for the user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get all certificates for the user.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get all terms acceptances for this user.
     */
    public function termsAcceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    /**
     * Check if user has accepted the active terms version.
     */
    public function hasAcceptedActiveTerms(): bool
    {
        try {
            $activeTerms = TermsVersion::active();
            if (! $activeTerms) {
                return false; // No active terms = must accept
            }

            return $this->termsAcceptances()
                ->where('terms_version_id', $activeTerms->id)
                ->exists();
        } catch (\Exception $e) {
            // Table doesn't exist yet - return false (migrations need to be run)
            return false;
        }
    }

    /**
     * Get the latest terms acceptance.
     */
    public function latestTermsAcceptance(): ?TermsAcceptance
    {
        return $this->termsAcceptances()
            ->with('termsVersion')
            ->latest('accepted_at')
            ->first();
    }

    /**
     * Get all payments for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    /**
     * Get status history for the user.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(\App\Models\MemberStatusHistory::class);
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Get the permissions for the user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    /**
     * Get dedicated status applications for the user.
     */
    public function dedicatedStatusApplications(): HasMany
    {
        return $this->hasMany(DedicatedStatusApplication::class);
    }

    /**
     * Get shooting activities for the user.
     */
    public function shootingActivities(): HasMany
    {
        return $this->hasMany(ShootingActivity::class);
    }

    /**
     * Get knowledge test attempts for the user.
     */
    public function knowledgeTestAttempts(): HasMany
    {
        return $this->hasMany(KnowledgeTestAttempt::class);
    }

    /**
     * Get firearm motivation requests for the user.
     */
    public function firearmMotivationRequests(): HasMany
    {
        return $this->hasMany(FirearmMotivationRequest::class);
    }

    /**
     * Get all firearms (armoury) for the user.
     */
    public function firearms(): HasMany
    {
        return $this->hasMany(UserFirearm::class);
    }

    /**
     * Get active firearms for the user.
     */
    public function activeFirearms(): HasMany
    {
        return $this->hasMany(UserFirearm::class)->where('is_active', true);
    }

    /**
     * Get all load data for the user.
     */
    public function loadData(): HasMany
    {
        return $this->hasMany(LoadData::class);
    }

    /**
     * Get firearms with licenses expiring soon.
     */
    public function firearmsExpiringSoon(int $days = 90): HasMany
    {
        return $this->hasMany(UserFirearm::class)
            ->where('is_active', true)
            ->whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<=', now()->addDays($days))
            ->where('license_expiry_date', '>=', now());
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has a specific permission.
     *
     * Hard rules:
     * - Developer has ALL permissions (system level)
     * - Owner has ALL permissions (NRAPA level)
     * - Super Admin / Standard Admin have only assigned permissions
     */
    public function hasPermission(string $permission): bool
    {
        // Developers have all permissions (system level)
        if ($this->isDeveloper()) {
            return true;
        }

        // Owners have all permissions (NRAPA level)
        if ($this->isOwner()) {
            return true;
        }

        // Admins check their assigned permissions
        if ($this->isAdmin()) {
            return $this->permissions()->where('slug', $permission)->exists();
        }

        return false;
    }

    /**
     * Check if user can assign roles (Owner only).
     */
    public function canAssignRoles(): bool
    {
        return $this->isOwner() || $this->isDeveloper();
    }

    /**
     * Check if user can grant permissions (Owner only).
     */
    public function canGrantPermissions(): bool
    {
        return $this->isOwner() || $this->isDeveloper();
    }

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && $this->admin_type === self::ADMIN_TYPE_SUPER;
    }

    /**
     * Check if user is a Standard Admin.
     */
    public function isStandardAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && $this->admin_type === self::ADMIN_TYPE_STANDARD;
    }

    /**
     * Grant a permission to this user.
     */
    public function grantPermission(Permission $permission, User $grantedBy): void
    {
        if (! $grantedBy->canGrantPermissions()) {
            throw new \Exception('User cannot grant permissions');
        }

        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'granted_by' => $grantedBy->id,
                'granted_at' => now(),
            ],
        ]);
    }

    /**
     * Revoke a permission from this user.
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Grant multiple permissions to this user.
     */
    public function grantPermissions(array $permissionSlugs, User $grantedBy): void
    {
        if (! $grantedBy->canGrantPermissions()) {
            throw new \Exception('User cannot grant permissions');
        }

        $permissions = Permission::whereIn('slug', $permissionSlugs)->get();

        foreach ($permissions as $permission) {
            $this->permissions()->syncWithoutDetaching([
                $permission->id => [
                    'granted_by' => $grantedBy->id,
                    'granted_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Sync permissions for this user (replace all).
     */
    public function syncPermissions(array $permissionSlugs, User $grantedBy): void
    {
        if (! $grantedBy->canGrantPermissions()) {
            throw new \Exception('User cannot grant permissions');
        }

        $permissions = Permission::whereIn('slug', $permissionSlugs)->pluck('id');

        $syncData = $permissions->mapWithKeys(fn ($id) => [
            $id => [
                'granted_by' => $grantedBy->id,
                'granted_at' => now(),
            ],
        ])->toArray();

        $this->permissions()->sync($syncData);
    }

    /**
     * Get all permission slugs for this user.
     */
    public function getPermissionSlugs(): array
    {
        // Owners and developers have all permissions
        if ($this->isOwner() || $this->isDeveloper()) {
            return Permission::pluck('slug')->toArray();
        }

        return $this->permissions()->pluck('slug')->toArray();
    }

    /**
     * Get admin type display name.
     */
    public function getAdminTypeDisplayNameAttribute(): ?string
    {
        return match ($this->admin_type) {
            self::ADMIN_TYPE_SUPER => 'Super Admin',
            self::ADMIN_TYPE_STANDARD => 'Standard Admin',
            default => null,
        };
    }

    /**
     * Check if user has an active membership.
     */
    public function hasActiveMembership(): bool
    {
        return $this->memberships()->where('status', 'active')->exists();
    }

    /**
     * Check if user has dedicated status.
     */
    public function hasDedicatedStatus(): bool
    {
        return $this->dedicatedStatusApplications()
            ->where('status', 'approved')
            ->where('valid_until', '>=', now())
            ->exists();
    }

    /**
     * Check if user has passed the knowledge test (including manually approved by admin).
     */
    public function hasPassedKnowledgeTest(): bool
    {
        return $this->knowledgeTestAttempts()
            ->where(function ($q) {
                $q->where('passed', true)->orWhereNotNull('marked_by');
            })
            ->exists();
    }

    /**
     * Get learning articles read by the user.
     */
    public function learningArticlesRead(): BelongsToMany
    {
        return $this->belongsToMany(LearningArticle::class, 'learning_article_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Get deletion requests for this user.
     */
    public function deletionRequests(): HasMany
    {
        return $this->hasMany(UserDeletionRequest::class);
    }

    /**
     * Get deletion requests made by this user (as admin).
     */
    public function requestedDeletions(): HasMany
    {
        return $this->hasMany(UserDeletionRequest::class, 'requested_by');
    }

    /**
     * Check if this user has a pending deletion request.
     */
    public function hasPendingDeletionRequest(): bool
    {
        return $this->deletionRequests()->pending()->exists();
    }

    /**
     * Check if the current user can delete another user.
     * Owners and developers can delete directly.
     * Admins can only request deletion.
     */
    public function canDeleteUser(User $targetUser): bool
    {
        // Can't delete yourself
        if ($this->id === $targetUser->id) {
            return false;
        }

        // Developers can delete anyone except other developers
        if ($this->isDeveloper()) {
            return ! $targetUser->isDeveloper();
        }

        // Owners can delete members only (NOT admins)
        if ($this->isOwner()) {
            return $targetUser->isMember();
        }

        // Admins cannot delete anyone (must request deletion)
        if ($this->isAdmin()) {
            return false;
        }

        return false;
    }

    /**
     * Check if the current user can request deletion of another user.
     * Admins can request deletion of members only.
     */
    public function canRequestUserDeletion(User $targetUser): bool
    {
        // Can't request deletion of yourself
        if ($this->id === $targetUser->id) {
            return false;
        }

        // Only admins request deletion (owners/devs delete directly)
        if (! $this->isAdmin()) {
            return false;
        }

        // Can only request deletion of regular members
        return $targetUser->role === self::ROLE_MEMBER;
    }

    // ===== 2FA Enforcement Methods =====

    /**
     * Check if user requires 2FA (admins, owners, developers).
     */
    public function requires2FA(): bool
    {
        return $this->isAdmin() || $this->isOwner() || $this->isDeveloper();
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function has2FAEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && ! empty($this->two_factor_confirmed_at);
    }

    /**
     * Check if user has exceeded login limit without 2FA.
     */
    public function hasExceeded2FALoginLimit(): bool
    {
        return $this->requires2FA() &&
               ! $this->has2FAEnabled() &&
               $this->logins_without_2fa >= self::MAX_LOGINS_WITHOUT_2FA;
    }

    /**
     * Get remaining logins before 2FA is required.
     */
    public function getRemainingLoginsWithout2FA(): int
    {
        if (! $this->requires2FA() || $this->has2FAEnabled()) {
            return -1; // Unlimited
        }

        return max(0, self::MAX_LOGINS_WITHOUT_2FA - $this->logins_without_2fa);
    }

    /**
     * Increment the login counter for users without 2FA.
     */
    public function incrementLoginWithout2FA(): void
    {
        if ($this->requires2FA() && ! $this->has2FAEnabled()) {
            $this->increment('logins_without_2fa');
        }
    }

    /**
     * Reset the login counter (called when 2FA is enabled).
     */
    public function reset2FALoginCounter(): void
    {
        $this->update(['logins_without_2fa' => 0]);
    }

    // ===== Security Questions & Account Reset =====

    /**
     * Get security questions for this user.
     */
    public function securityQuestions(): HasMany
    {
        return $this->hasMany(UserSecurityQuestion::class);
    }

    /**
     * Get account reset logs for this user.
     */
    public function accountResetLogs(): HasMany
    {
        return $this->hasMany(AccountResetLog::class);
    }

    /**
     * Get login logs for this user.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /**
     * Check if user has set up security questions.
     */
    public function hasSecurityQuestions(): bool
    {
        return $this->securityQuestions()->count() >= UserSecurityQuestion::REQUIRED_QUESTIONS;
    }

    /**
     * Check if user has a verified ID document.
     */
    public function hasVerifiedIdDocument(): bool
    {
        return $this->documents()
            ->verified()
            ->whereHas('documentType', function ($query) {
                $query->whereIn('slug', MemberDocument::ID_DOCUMENT_SLUGS);
            })
            ->exists();
    }

    /**
     * Get the verified ID document for this user.
     */
    public function getVerifiedIdDocument(): ?MemberDocument
    {
        return $this->documents()
            ->verified()
            ->whereHas('documentType', function ($query) {
                $query->whereIn('slug', MemberDocument::ID_DOCUMENT_SLUGS);
            })
            ->latest('verified_at')
            ->first();
    }

    /**
     * Get the official name from ID document (surname + names).
     * Falls back to user's display name if no verified ID document.
     */
    public function getIdName(): string
    {
        $idDoc = $this->getVerifiedIdDocument();

        if ($idDoc && $idDoc->metadata) {
            $surname = $idDoc->metadata['surname'] ?? '';
            $names = $idDoc->metadata['names'] ?? '';

            if ($surname || $names) {
                // Format as "SURNAME, Names" for official documents
                return trim(strtoupper($surname).', '.$names);
            }
        }

        // Fallback to display name
        return $this->name;
    }

    /**
     * Get just the surname from ID document.
     * Falls back to last word of display name.
     */
    public function getIdSurname(): string
    {
        $idDoc = $this->getVerifiedIdDocument();

        if ($idDoc && $idDoc->metadata && ! empty($idDoc->metadata['surname'])) {
            return strtoupper($idDoc->metadata['surname']);
        }

        // Fallback to last word of display name
        $parts = explode(' ', $this->name);

        return strtoupper(end($parts));
    }

    /**
     * Get just the first names from ID document.
     * Falls back to first words of display name.
     */
    public function getIdFirstNames(): string
    {
        $idDoc = $this->getVerifiedIdDocument();

        if ($idDoc && $idDoc->metadata && ! empty($idDoc->metadata['names'])) {
            return $idDoc->metadata['names'];
        }

        // Fallback to all but last word of display name
        $parts = explode(' ', $this->name);
        array_pop($parts);

        return implode(' ', $parts) ?: $this->name;
    }

    /**
     * Get the ID/passport number from verified ID document.
     * Falls back to the id_number column on the users table.
     */
    public function getIdNumber(): ?string
    {
        $idDoc = $this->getVerifiedIdDocument();

        if ($idDoc && $idDoc->metadata && ! empty($idDoc->metadata['identity_number'])) {
            return $idDoc->metadata['identity_number'];
        }

        // Fallback to users table column
        return $this->id_number;
    }

    /**
     * Check if user can enable 2FA.
     * Regular members must have security questions set up OR have a verified ID document.
     * Admins and owners can always enable 2FA (they need it for security).
     */
    public function canEnable2FA(): bool
    {
        // Admins and owners can always enable 2FA (it's mandatory for them)
        if ($this->requires2FA()) {
            return true;
        }

        // Regular members need security questions OR verified ID
        return $this->hasSecurityQuestions() || $this->hasVerifiedIdDocument();
    }

    /**
     * Check if user can disable 2FA.
     * Admins, owners, and developers cannot disable 2FA (it's mandatory for them).
     */
    public function canDisable2FA(): bool
    {
        // Users who require 2FA cannot disable it
        return ! $this->requires2FA();
    }

    /**
     * Get the reason why 2FA cannot be enabled.
     */
    public function get2FABlockReason(): ?string
    {
        if ($this->canEnable2FA()) {
            return null;
        }

        return 'You must set up security questions or have a verified ID document before enabling two-factor authentication. This is required to verify your identity if you need to reset 2FA in the future.';
    }

    /**
     * Check if current user can reset password for target user.
     * Developer can reset: Owner, Admin, Member
     * Owner can reset: Admin, Member
     * Admin can reset: Member
     */
    public function canResetPasswordFor(User $targetUser): bool
    {
        if ($this->id === $targetUser->id) {
            return false;
        }

        if ($this->isDeveloper()) {
            return ! $targetUser->isDeveloper();
        }

        if ($this->isOwner()) {
            return ! $targetUser->isDeveloper() && ! $targetUser->isOwner();
        }

        if ($this->isAdmin()) {
            return $targetUser->role === self::ROLE_MEMBER;
        }

        return false;
    }

    /**
     * Check if current user can reset 2FA for target user.
     * Same hierarchy as password reset.
     */
    public function canReset2FAFor(User $targetUser): bool
    {
        return $this->canResetPasswordFor($targetUser);
    }

    /**
     * Reset 2FA for this user.
     */
    public function reset2FA(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'logins_without_2fa' => 0,
        ])->save();
    }
}
