<?php

namespace App\Helpers;

use App\Models\User;

class AdminMemberContext
{
    /**
     * Resolve the member (User) that the current admin route is "about".
     *
     * Returns null for list pages, the admin dashboard, or any non-admin route.
     */
    public static function resolve(): ?User
    {
        $route = request()->route();
        if (! $route) {
            return null;
        }

        $name = $route->getName() ?? '';
        if (! str_starts_with($name, 'admin.')) {
            return null;
        }

        try {
            return match ($name) {
                'admin.members.show' => self::paramAsUser($route->parameter('user')),
                'admin.endorsements.show' => self::paramAsUser(optional($route->parameter('request'))->user),
                'admin.approvals.show' => self::paramAsUser(optional($route->parameter('membership'))->user),
                'admin.documents.show' => self::paramAsUser(optional($route->parameter('document'))->user),
                'admin.activities.show' => self::paramAsUser(optional($route->parameter('activity'))->user),
                'admin.certificates.show' => self::paramAsUser(optional($route->parameter('certificate'))->user),
                'admin.knowledge-tests.mark-attempt' => self::paramAsUser(optional($route->parameter('attempt'))->user),
                default => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Human-readable label for the list page the current route "came from".
     * Returns [label, route_name, route_params] or null.
     */
    public static function backLink(): ?array
    {
        $name = request()->route()?->getName() ?? '';

        return match ($name) {
            'admin.members.show' => ['Members', 'admin.members.index', []],
            'admin.endorsements.show' => ['Endorsements', 'admin.endorsements.index', []],
            'admin.approvals.show' => ['Approvals', 'admin.approvals.index', []],
            'admin.documents.show' => ['Documents', 'admin.documents.index', []],
            'admin.activities.show' => ['Activities', 'admin.activities.index', []],
            'admin.certificates.show' => ['Certificates', 'admin.certificates.index', []],
            'admin.knowledge-tests.mark-attempt' => ['Knowledge Tests', 'admin.knowledge-tests.index', []],
            default => null,
        };
    }

    protected static function paramAsUser(mixed $value): ?User
    {
        if ($value instanceof User) {
            return $value;
        }
        if (is_numeric($value)) {
            return User::find($value);
        }

        return null;
    }
}
