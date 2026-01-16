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
        ];
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
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->roles()
            ->get()
            ->flatMap(fn ($role) => $role->permissions ?? [])
            ->contains($permission);
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
}
