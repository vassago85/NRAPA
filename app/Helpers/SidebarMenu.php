<?php

namespace App\Helpers;

class SidebarMenu
{
    /**
     * Get the sidebar menu structure based on user role.
     */
    public static function getMenu(): array
    {
        $user = auth()->user();
        $menu = [];
        
        // Check if admin/owner/dev is viewing as member
        $viewingAsMember = session('view_as_member', false);
        $isAdminRole = $user->hasRoleLevel(\App\Models\User::ROLE_ADMIN);
        $showMemberArea = !$isAdminRole || $viewingAsMember;

        // 1. MEMBER AREA (only visible to members OR admin/owner/dev viewing as member)
        if ($showMemberArea) {
        // Check if user has an active membership
        $hasActiveMembership = $user->activeMembership !== null;
        
        $memberAreaItems = [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
                'roles' => ['member', 'admin', 'owner', 'developer'],
            ],
            [
                'label' => 'My Membership',
                'route' => 'membership.index',
                'icon' => 'badge',
                'roles' => ['member', 'admin', 'owner', 'developer'],
            ],
        ];

        // Add "My Card" if user has active membership
        if ($hasActiveMembership) {
            $memberAreaItems[] = [
                'label' => 'My Card',
                'route' => 'card',
                'icon' => 'credit-card',
                'roles' => ['member', 'admin', 'owner', 'developer'],
            ];
        }

        // Only show these items if user has active membership (or is admin/owner/dev not viewing as member)
        if ($hasActiveMembership || ($isAdminRole && !$viewingAsMember)) {
            // Add active member-only items (require membership.required middleware)
            $activeMemberItems = [
                [
                    'label' => 'Documents',
                    'route' => 'documents.index',
                    'icon' => 'document',
                    'roles' => ['member', 'admin', 'owner', 'developer'],
                ],
                [
                    'label' => 'Activities',
                    'route' => 'activities.index',
                    'icon' => 'clipboard',
                    'roles' => ['member', 'admin', 'owner', 'developer'],
                ],
                [
                    'label' => 'Virtual Safe',
                    'route' => 'armoury.index',
                    'icon' => 'shield-check',
                    'roles' => ['member', 'admin', 'owner', 'developer'],
                ],
                [
                    'label' => 'Endorsements',
                    'route' => 'member.endorsements.index',
                    'icon' => 'document-check',
                    'roles' => ['member', 'admin', 'owner', 'developer'],
                ],
                [
                    'label' => 'Certificates',
                    'route' => $user->isDeveloper() 
                        ? 'developer.certificates.index' 
                        : ($user->isOwner() 
                            ? 'owner.certificates.index' 
                            : ($user->isAdmin() 
                                ? 'admin.certificates.index' 
                                : 'certificates.index')),
                    'icon' => 'badge-check',
                    'roles' => ['member', 'admin', 'owner', 'developer'],
                ],
            ];

            // Learning items under collapsible group (Certificates moved above)
            $learningItems = [
                [
                    'label' => 'Learning Center',
                    'route' => 'learning.index',
                    'icon' => 'book-open',
                ],
                [
                    'label' => 'Knowledge Tests',
                    'route' => 'knowledge-test.index',
                    'icon' => 'academic-cap',
                ],
            ];

            $memberAreaItems = array_merge($memberAreaItems, $activeMemberItems);
            
            // Add Learning group (collapsible) - after Certificates and Endorsements
            $memberAreaItems[] = [
                'label' => 'Learning',
                'route' => 'learning.index',
                'icon' => 'book-open',
                'roles' => ['member', 'admin', 'owner', 'developer'],
                'collapsible' => true,
                'children' => $learningItems,
            ];
        }

            $menu[] = [
                'section' => 'MEMBER AREA',
                'items' => $memberAreaItems,
            ];
        }

        // 2. ADMINISTRATION (admin + owner)
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            // Calculate pending counts
            $pendingDocs = 0;
            $pendingMemberships = 0;
            $pendingActivities = 0;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('member_documents')) {
                    $pendingDocs = \App\Models\MemberDocument::where('status', 'pending')->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('memberships')) {
                    $pendingMemberships = \App\Models\Membership::where('status', 'applied')->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('shooting_activities')) {
                    $pendingActivities = \App\Models\ShootingActivity::where('status', 'pending')->count();
                }
            } catch (\Exception $e) {}
            
            // Approvals = documents + memberships only (activities approved via Activities)
            $totalPending = $pendingDocs + $pendingMemberships;

            $menu[] = [
                'section' => 'ADMINISTRATION',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'icon' => 'squares-2x2',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Members',
                        'route' => 'admin.members.index',
                        'icon' => 'users',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Activities',
                        'route' => 'admin.activities.index',
                        'icon' => 'clipboard',
                        'roles' => ['admin', 'owner', 'developer'],
                        'pending_count' => $pendingActivities,
                    ],
                    // Approvals heading (document/membership approvals only, not activities)
                    [
                        'label' => 'Approvals',
                        'type' => 'heading',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'All Approvals',
                        'route' => 'admin.approvals.index',
                        'icon' => 'check-circle',
                        'roles' => ['admin', 'owner', 'developer'],
                        'pending_count' => $totalPending,
                    ],
                    [
                        'label' => 'Documents',
                        'route' => 'admin.documents.index',
                        'icon' => 'document-text',
                        'roles' => ['admin', 'owner', 'developer'],
                        'pending_count' => $pendingDocs,
                    ],
                    [
                        'label' => 'Memberships',
                        'route' => 'admin.approvals.index',
                        'route_params' => ['type' => 'memberships'],
                        'icon' => 'badge',
                        'roles' => ['admin', 'owner', 'developer'],
                        'pending_count' => $pendingMemberships,
                    ],
                    [
                        'label' => 'Endorsements',
                        'route' => 'admin.endorsements.index',
                        'icon' => 'document-check',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                ],
            ];
        }

        // 3. CONFIGURATION (admin + owner)
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            $menu[] = [
                'section' => 'CONFIGURATION',
                'items' => [
                    [
                        'label' => 'Membership Types',
                        'route' => 'admin.membership-types.index',
                        'icon' => 'badge',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Activity Types',
                        'route' => 'admin.activity-config.index',
                        'icon' => 'clipboard',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Calibres',
                        'route' => 'admin.calibre-requests.index',
                        'icon' => 'cube',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Firearm Settings',
                        'route' => 'admin.firearm-settings.index',
                        'icon' => 'cog',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Firearm Reference Data',
                        'route' => 'admin.firearm-reference.index',
                        'icon' => 'cube',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                ],
            ];
        }

        // 4. LEARNING & COMPLIANCE (admin + owner; optionally member)
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            $menu[] = [
                'section' => 'LEARNING & COMPLIANCE',
                'items' => [
                    [
                        'label' => 'Learning Center',
                        'route' => 'admin.learning.index',
                        'icon' => 'book-open',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'Knowledge Tests',
                        'route' => 'admin.knowledge-tests.index',
                        'icon' => 'academic-cap',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                ],
            ];
        }

        // 5. SYSTEM (admin + owner)
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            $menu[] = [
                'section' => 'SYSTEM',
                'items' => [
                    [
                        'label' => 'Email Logs',
                        'route' => 'admin.email-logs.index',
                        'icon' => 'envelope',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                    [
                        'label' => 'General Settings',
                        'route' => 'admin.settings.index',
                        'icon' => 'cog-6-tooth',
                        'roles' => ['admin', 'owner', 'developer'],
                    ],
                ],
            ];
        }

        // 6. OWNER (owner only, separated by divider)
        if ($user->isOwner() || $user->isDeveloper()) {
            $menu[] = [
                'section' => 'OWNER',
                'items' => [
                    [
                        'label' => 'Owner Dashboard',
                        'route' => 'owner.dashboard',
                        'icon' => 'squares-2x2',
                        'roles' => ['owner', 'developer'],
                    ],
                    [
                        'label' => 'Owner Site Settings',
                        'route' => 'owner.settings.index',
                        'icon' => 'cog-6-tooth',
                        'roles' => ['owner', 'developer'],
                    ],
                ],
            ];
        }

        // 7. DEVELOPER (developer only - at bottom)
        if ($user->isDeveloper()) {
            $menu[] = [
                'section' => 'DEVELOPER',
                'items' => [
                    [
                        'label' => 'Developer Dashboard',
                        'route' => 'developer.dashboard',
                        'icon' => 'squares-2x2',
                        'roles' => ['developer'],
                    ],
                ],
            ];
        }

        return $menu;
    }

    /**
     * Get icon SVG path for Heroicons.
     */
    public static function getIcon(string $iconName): string
    {
        $icons = [
            'home' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            'badge' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>',
            'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'document-text' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
            'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
            'cube' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
            'cog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'cog-6-tooth' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653a2.548 2.548 0 010-3.586L11.42 15.17z"/>',
            'book-open' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            'academic-cap' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l-6.16-3.422a12.083 12.083 0 00-.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 016.824-2.998 12.078 12.078 0 00-.665-6.479L12 14z"/>',
            'envelope' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            'squares-2x2' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
            'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
            'document-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>',
            'badge-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
            'credit-card' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>',
        ];

        return $icons[$iconName] ?? '';
    }

    /**
     * Check if user has access to menu item.
     */
    public static function userCanAccess(array $item): bool
    {
        $user = auth()->user();
        $roles = $item['roles'] ?? [];

        if (empty($roles)) {
            return true;
        }

        foreach ($roles as $role) {
            if ($role === 'developer' && $user->isDeveloper()) {
                return true;
            }
            if ($role === 'owner' && ($user->isOwner() || $user->isDeveloper())) {
                return true;
            }
            if ($role === 'admin' && ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN) || $user->isDeveloper())) {
                return true;
            }
            if ($role === 'member') {
                return true; // All authenticated users are members
            }
        }

        return false;
    }

    /**
     * Check if route is active.
     */
    public static function isRouteActive(string $route, ?array $params = null): bool
    {
        // Check if any of the certificate routes match (for cross-route highlighting)
        $certificateRoutes = [
            'certificates.index',
            'certificates.show',
            'admin.certificates.index',
            'admin.certificates.show',
            'owner.certificates.index',
            'owner.certificates.show',
            'developer.certificates.index',
            'developer.certificates.show',
        ];
        
        // If checking a certificate route, also check if current route is any certificate route
        if (in_array($route, $certificateRoutes)) {
            foreach ($certificateRoutes as $certRoute) {
                if (request()->routeIs($certRoute)) {
                    return true;
                }
            }
        }
        
        if (!request()->routeIs($route)) {
            return false;
        }
        
        // If no params specified, route is active if no query params exist (or match exactly)
        if ($params === null) {
            // For "All Approvals" - should be active when no type param
            if ($route === 'admin.approvals.index') {
                return !request()->has('type');
            }
            return true;
        }
        
        // Check if all params match
        foreach ($params as $key => $value) {
            if (request($key) != $value) {
                return false;
            }
        }
        return true;
    }
}
