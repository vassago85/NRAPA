<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (for Nginx Proxy Manager / reverse proxy setups)
        $middleware->trustProxies(at: '*');

        // Add CORS middleware globally for Livewire uploads
        $middleware->prepend(\App\Http\Middleware\HandleCors::class);

        // Handle CORS for Livewire uploads
        $middleware->validateCsrfTokens(except: [
            'livewire/upload-file',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'owner' => \App\Http\Middleware\EnsureUserIsOwner::class,
            'developer' => \App\Http\Middleware\EnsureUserIsDeveloper::class,
            'membership.required' => \App\Http\Middleware\RedirectIfNoMembership::class,
            'can' => \App\Http\Middleware\CheckPermission::class,
            '2fa.enforce' => \App\Http\Middleware\Enforce2FAForAdmins::class,
            'terms.accepted' => \App\Http\Middleware\EnsureTermsAccepted::class,
        ]);

        // Add 2FA enforcement to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\Enforce2FAForAdmins::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
