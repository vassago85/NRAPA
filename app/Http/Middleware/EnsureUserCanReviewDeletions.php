<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanReviewDeletions
{
    /**
     * Handle an incoming request.
     * Allows Owners, Developers, and Super Admins to review member deletion requests.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->hasRoleLevel(User::ROLE_OWNER) || $user->isSuperAdmin()) {
            return $next($request);
        }

        abort(403, 'Unauthorized. You do not have permission to review deletion requests.');
    }
}
