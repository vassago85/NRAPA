<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
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
            return !$user->isDeveloper();
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
        return match($this->role) {
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
        if (!$grantedBy->canGrantPermissions()) {
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
        if (!$grantedBy->canGrantPermissions()) {
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
        if (!$grantedBy->canGrantPermissions()) {
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
     * Check if user has passed the knowledge test.
     */
    public function hasPassedKnowledgeTest(): bool
    {
        return $this->knowledgeTestAttempts()
            ->where('passed', true)
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
}
