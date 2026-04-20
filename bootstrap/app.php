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

        $exceptions->report(function (\Illuminate\Session\TokenMismatchException $e) {
            try {
                $req = request();
                $sess = $req->hasSession() ? $req->session() : null;
                \Illuminate\Support\Facades\Log::warning('[CSRF_MISMATCH] '.$e->getMessage(), [
                    'path' => $req->path(),
                    'method' => $req->method(),
                    'session_id' => $sess?->getId(),
                    'session_token' => $sess?->token(),
                    'header_X-XSRF-TOKEN_present' => (bool) $req->header('X-XSRF-TOKEN'),
                    'header_X-CSRF-TOKEN_present' => (bool) $req->header('X-CSRF-TOKEN'),
                    'input_token_present' => (bool) $req->input('_token'),
                    'cookie_XSRF_TOKEN_present' => (bool) $req->cookie('XSRF-TOKEN'),
                    'cookie_session_present' => (bool) $req->cookie(config('session.cookie')),
                    'referer' => $req->header('referer'),
                    'user_id' => auth()->id(),
                ]);
            } catch (\Throwable $inner) {
                \Illuminate\Support\Facades\Log::warning('[CSRF_MISMATCH] reporter failed: '.$inner->getMessage());
            }
        });

        $exceptions->report(function (\Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException $e) {
            try {
                $req = request();
                \Illuminate\Support\Facades\Log::warning('[LIVEWIRE_CHECKSUM_FAIL] Corrupt component payload', [
                    'path' => $req->path(),
                    'method' => $req->method(),
                    'referer' => $req->header('referer'),
                    'user_id' => auth()->id(),
                    'app_key_fp' => substr(hash('sha256', (string) config('app.key')), 0, 12),
                    'app_env' => config('app.env'),
                    'app_debug' => (bool) config('app.debug'),
                    'request_components_count' => is_array($req->input('components')) ? count($req->input('components')) : null,
                    'request_component_names' => collect($req->input('components', []))
                        ->pluck('snapshot')
                        ->map(function ($snap) {
                            if (! is_string($snap)) return null;
                            $decoded = json_decode($snap, true);
                            return $decoded['memo']['name'] ?? null;
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ]);
            } catch (\Throwable $inner) {
                \Illuminate\Support\Facades\Log::warning('[LIVEWIRE_CHECKSUM_FAIL] reporter failed: '.$inner->getMessage());
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
