<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNoMembership
{
    /**
     * Routes that should be accessible without an active membership.
     * These routes are available to "free members" (registered but not paid).
     */
    protected array $freeRoutes = [
        'dashboard',
        'membership.*',
        'logout',
        'profile.edit',
        'user-password.edit',
        'two-factor.show',
        'appearance.edit',
        'verification.*',
        'settings.*',
    ];

    /**
     * Handle an incoming request.
     * 
     * Free members (registered but no active paid membership) can only access:
     * - Dashboard
     * - My Membership page (to select/pay for a package)
     * - Profile/settings pages
     * 
     * All other member features require an active paid membership.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Skip for admin/owner/developer roles when NOT viewing as member
        // When viewing as member, they must have active membership like regular members
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN) && !session('view_as_member', false)) {
            return $next($request);
        }

        // Check if current route is accessible to free members
        foreach ($this->freeRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Check if user has an ACTIVE membership (not just pending)
        $activeMembership = $user->activeMembership;

        if (!$activeMembership) {
            // Free member trying to access paid features
            session()->flash('warning', 'An active membership is required to access this feature. Please select a membership package to continue.');
            return redirect()->route('membership.index');
        }

        return $next($request);
    }
}
