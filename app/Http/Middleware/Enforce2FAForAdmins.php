<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Enforce2FAForAdmins
{
    /**
     * Routes that are exempt from 2FA enforcement.
     */
    protected array $exemptRoutes = [
        'two-factor.*',
        'settings/two-factor',
        'logout',
        'password.confirm',
        'password.confirmation',
        'user/confirm-password',
        'user/two-factor-authentication',
        'user/two-factor-qr-code',
        'user/two-factor-secret-key',
        'user/confirmed-two-factor-authentication',
        'user/two-factor-recovery-codes',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Only apply to users who require 2FA
        if (!$user->requires2FA()) {
            return $next($request);
        }

        // Allow exempt routes
        foreach ($this->exemptRoutes as $route) {
            if ($request->routeIs($route) || $request->is($route)) {
                return $next($request);
            }
        }

        // Check if user has exceeded login limit without 2FA
        if ($user->hasExceeded2FALoginLimit()) {
            // Redirect to 2FA setup page with message
            session()->flash('error', 'You must enable two-factor authentication to continue. You have exceeded the maximum number of logins without 2FA.');
            return redirect()->route('two-factor.show');
        }

        // Show warning if approaching limit
        $remaining = $user->getRemainingLoginsWithout2FA();
        if ($remaining > 0 && $remaining <= 3 && !$user->has2FAEnabled()) {
            // Only show once per session to avoid annoyance
            if (!session()->has('2fa_warning_shown')) {
                session()->flash('warning', "You have {$remaining} login(s) remaining before two-factor authentication becomes mandatory.");
                session()->put('2fa_warning_shown', true);
            }
        }

        return $next($request);
    }
}
