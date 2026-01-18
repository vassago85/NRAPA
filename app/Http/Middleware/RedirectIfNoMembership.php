<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNoMembership
{
    /**
     * Routes that should be accessible without membership.
     */
    protected array $except = [
        'membership.select-package',
        'membership.payment',
        'logout',
        'profile.edit',
        'user-password.edit',
        'two-factor.show',
        'appearance.edit',
        'verification.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Skip for admin/owner/developer roles
        if ($user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            return $next($request);
        }

        // Skip for excepted routes
        foreach ($this->except as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Check if user has any membership (pending_payment, pending, active)
        $membership = Membership::where('user_id', $user->id)
            ->whereIn('status', ['pending_payment', 'pending', 'active'])
            ->first();

        if (!$membership) {
            return redirect()->route('membership.select-package');
        }

        return $next($request);
    }
}
