<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'group',
        'description',
        'is_high_risk',
        'sort_order',
    ];

    protected $casts = [
        'is_high_risk' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Permission Groups
    public const GROUP_MEMBERSHIP = 'membership';

    public const GROUP_DOCUMENTS = 'documents';

    public const GROUP_FIREARM = 'firearm';

    public const GROUP_ADMIN = 'admin';

    // Membership Management Permissions
    public const APPROVE_MEMBERSHIP = 'approve_membership';

    public const REJECT_MEMBERSHIP = 'reject_membership';

    public const SUSPEND_MEMBERSHIP = 'suspend_membership';

    public const EDIT_MEMBER_PROFILE = 'edit_member_profile';

    // Document Management Permissions
    public const VIEW_DOCUMENTS = 'view_documents';

    public const VERIFY_DOCUMENTS = 'verify_documents';

    public const REJECT_DOCUMENTS = 'reject_documents';

    public const ARCHIVE_DOCUMENTS = 'archive_documents';

    // Firearm & Compliance Permissions
    public const REVIEW_FIREARM_MOTIVATION = 'review_firearm_motivation';

    public const APPROVE_FIREARM_MOTIVATION = 'approve_firearm_motivation';

    public const OVERRIDE_COMPLIANCE_DECISION = 'override_compliance_decision';

    // Admin & System Permissions
    public const VIEW_AUDIT_LOGS = 'view_audit_logs';

    public const MANAGE_ANNOUNCEMENTS = 'manage_announcements';

    public const EXPORT_DATA = 'export_data';

    public const SYSTEM_OVERRIDE = 'system_override';

    public const MANAGE_CONFIG = 'manage_config'; // For membership types, document types, etc.

    /**
     * All permissions grouped.
     */
    public static function getAllPermissions(): array
    {
        return [
            self::GROUP_MEMBERSHIP => [
                self::APPROVE_MEMBERSHIP => [
                    'name' => 'Approve Membership',
                    'description' => 'Approve new membership applications',
                    'high_risk' => false,
                ],
                self::REJECT_MEMBERSHIP => [
                    'name' => 'Reject Membership',
                    'description' => 'Reject membership applications',
                    'high_risk' => false,
                ],
                self::SUSPEND_MEMBERSHIP => [
                    'name' => 'Suspend Membership',
                    'description' => 'Suspend active memberships',
                    'high_risk' => true,
                ],
                self::EDIT_MEMBER_PROFILE => [
                    'name' => 'Edit Member Profile',
                    'description' => 'Edit member personal information',
                    'high_risk' => false,
                ],
            ],
            self::GROUP_DOCUMENTS => [
                self::VIEW_DOCUMENTS => [
                    'name' => 'View Documents',
                    'description' => 'View member uploaded documents',
                    'high_risk' => false,
                ],
                self::VERIFY_DOCUMENTS => [
                    'name' => 'Verify Documents',
                    'description' => 'Mark documents as verified',
                    'high_risk' => false,
                ],
                self::REJECT_DOCUMENTS => [
                    'name' => 'Reject Documents',
                    'description' => 'Reject member documents',
                    'high_risk' => false,
                ],
                self::ARCHIVE_DOCUMENTS => [
                    'name' => 'Archive Documents',
                    'description' => 'Archive expired documents',
                    'high_risk' => false,
                ],
            ],
            self::GROUP_FIREARM => [
                self::REVIEW_FIREARM_MOTIVATION => [
                    'name' => 'Review Firearm Motivation',
                    'description' => 'Review firearm motivation requests',
                    'high_risk' => true,
                ],
                self::APPROVE_FIREARM_MOTIVATION => [
                    'name' => 'Approve Firearm Motivation',
                    'description' => 'Approve firearm motivation requests',
                    'high_risk' => true,
                ],
                self::OVERRIDE_COMPLIANCE_DECISION => [
                    'name' => 'Override Compliance Decision',
                    'description' => 'Override compliance-related decisions',
                    'high_risk' => true,
                ],
            ],
            self::GROUP_ADMIN => [
                self::VIEW_AUDIT_LOGS => [
                    'name' => 'View Audit Logs',
                    'description' => 'Access system audit logs',
                    'high_risk' => false,
                ],
                self::MANAGE_ANNOUNCEMENTS => [
                    'name' => 'Manage Announcements',
                    'description' => 'Create and manage system announcements',
                    'high_risk' => false,
                ],
                self::EXPORT_DATA => [
                    'name' => 'Export Data',
                    'description' => 'Export member and system data',
                    'high_risk' => true,
                ],
                self::SYSTEM_OVERRIDE => [
                    'name' => 'System Override',
                    'description' => 'Bypass system restrictions (emergency use)',
                    'high_risk' => true,
                ],
                self::MANAGE_CONFIG => [
                    'name' => 'Manage Configuration',
                    'description' => 'Manage membership types, document types, etc.',
                    'high_risk' => true,
                ],
            ],
        ];
    }

    /**
     * Default permissions for Super Admin.
     */
    public static function getDefaultSuperAdminPermissions(): array
    {
        return [
            self::APPROVE_MEMBERSHIP,
            self::REJECT_MEMBERSHIP,
            self::SUSPEND_MEMBERSHIP,
            self::VIEW_DOCUMENTS,
            self::VERIFY_DOCUMENTS,
            self::REJECT_DOCUMENTS,
            self::ARCHIVE_DOCUMENTS,
            self::REVIEW_FIREARM_MOTIVATION,
            self::APPROVE_FIREARM_MOTIVATION,
            self::OVERRIDE_COMPLIANCE_DECISION,
            self::VIEW_AUDIT_LOGS,
            self::MANAGE_ANNOUNCEMENTS,
            self::MANAGE_CONFIG,
        ];
    }

    /**
     * Default permissions for Standard Admin.
     */
    public static function getDefaultStandardAdminPermissions(): array
    {
        return [
            self::APPROVE_MEMBERSHIP,
            self::REJECT_MEMBERSHIP,
            self::EDIT_MEMBER_PROFILE,
            self::VIEW_DOCUMENTS,
            self::VERIFY_DOCUMENTS,
            self::REJECT_DOCUMENTS,
        ];
    }

    /**
     * Get users with this permission.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['granted_by', 'granted_at'])
            ->withTimestamps();
    }

    /**
     * Scope to order by group and sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('group')->orderBy('sort_order');
    }

    /**
     * Scope to high risk permissions.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('is_high_risk', true);
    }

    /**
     * Get group display name.
     */
    public function getGroupDisplayNameAttribute(): string
    {
        return match ($this->group) {
            self::GROUP_MEMBERSHIP => 'Membership Management',
            self::GROUP_DOCUMENTS => 'Document Management',
            self::GROUP_FIREARM => 'Firearm & Compliance',
            self::GROUP_ADMIN => 'Admin & System',
            default => ucfirst($this->group),
        };
    }
}
