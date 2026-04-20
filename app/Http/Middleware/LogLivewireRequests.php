<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Debug middleware: logs full request/response diagnostics for Livewire update POSTs.
 * Intended to be removed once the 419 issue is diagnosed.
 */
class LogLivewireRequests
{
    public function handle(Request $request, Closure $next)
    {
        $isLivewire = str_contains($request->path(), 'livewire-') && $request->isMethod('POST');

        if ($isLivewire) {
            try {
                Log::info('[LW_REQ_IN] '.$request->method().' /'.$request->path(), [
                    'user_id' => auth()->id(),
                    'session_id_before' => $request->hasSession() ? $request->session()->getId() : null,
                    'session_token_before' => $request->hasSession() ? $request->session()->token() : null,
                    'header_X-CSRF-TOKEN' => $request->header('X-CSRF-TOKEN'),
                    'header_X-XSRF-TOKEN_present' => (bool) $request->header('X-XSRF-TOKEN'),
                    'header_X-Livewire' => $request->header('X-Livewire'),
                    'cookie_XSRF_present' => (bool) $request->cookie('XSRF-TOKEN'),
                    'cookie_session_present' => (bool) $request->cookie(config('session.cookie')),
                    'cookie_session_name' => config('session.cookie'),
                    'all_cookie_names' => array_keys($request->cookies->all()),
                    'input_token_present' => (bool) $request->input('_token'),
                    'components_count' => is_array($request->input('components')) ? count($request->input('components')) : null,
                    'referer' => $request->header('referer'),
                    'user_agent' => substr((string) $request->header('user-agent'), 0, 80),
                    'app_key_fp' => substr(hash('sha256', (string) config('app.key')), 0, 12),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[LW_REQ_IN] logging failed: '.$e->getMessage());
            }
        }

        $response = $next($request);

        if ($isLivewire) {
            try {
                Log::info('[LW_REQ_OUT] status='.$response->getStatusCode().' /'.$request->path(), [
                    'status' => $response->getStatusCode(),
                    'response_class' => get_class($response),
                    'body_length' => strlen((string) $response->getContent()),
                    'body_preview' => substr((string) $response->getContent(), 0, 400),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[LW_REQ_OUT] logging failed: '.$e->getMessage());
            }
        }

        return $response;
    }
}
