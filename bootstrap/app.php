<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

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

        // Add 2FA enforcement and security headers to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\Enforce2FAForAdmins::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            if (request()->is('livewire*') || str_contains((string) request()->path(), 'livewire')) {
                \Illuminate\Support\Facades\Log::error('[LIVEWIRE_EXCEPTION] '.get_class($e).': '.$e->getMessage(), [
                    'path' => request()->path(),
                    'method' => request()->method(),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'trace' => collect($e->getTrace())->take(5)->map(fn ($t) => ($t['file'] ?? '?').':'.($t['line'] ?? '?').' '.($t['class'] ?? '').($t['type'] ?? '').($t['function'] ?? ''))->all(),
                ]);
            }
        });

        $exceptions->respond(function (Response $response) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('server');

            return $response;
        });
    })->create();
