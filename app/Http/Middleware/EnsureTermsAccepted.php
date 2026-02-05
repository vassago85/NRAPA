<?php

namespace App\Http\Middleware;

use App\Models\TermsVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow admins, owners, and developers to bypass
        if ($user && ($user->isAdmin() || $user->isOwner() || $user->isDeveloper())) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Allow access to terms acceptance page
        if ($request->routeIs('terms.accept') || $request->routeIs('terms.*')) {
            return $next($request);
        }

        // Check if there's an active terms version
        // Use try-catch to handle case where table doesn't exist yet
        try {
            $activeTerms = TermsVersion::active();
            if (!$activeTerms) {
                // No active terms = allow access (admin should set one)
                return $next($request);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet - allow access (migrations need to be run)
            return $next($request);
        }

        // Check if user has accepted the active terms
        if (!$user->hasAcceptedActiveTerms()) {
            return redirect()->route('terms.accept')
                ->with('error', 'You must accept the Terms & Conditions to continue.');
        }

        return $next($request);
    }
}
