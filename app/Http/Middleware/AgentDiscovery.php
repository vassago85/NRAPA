<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AgentDiscovery: makes the site easier for AI agents and crawlers without
 * affecting human/browser/Livewire traffic.
 *
 *  1) On a tightly-scoped set of public routes, append a `Link:` HTTP header
 *     advertising the sitemap, /llms.txt, and (on home) a markdown twin.
 *
 *  2) On the home page only, perform strict content negotiation: if and only
 *     if the request explicitly prefers `text/markdown` and does NOT also
 *     accept `text/html`, redirect to `/about.md`. This is deliberately
 *     stricter than RFC 9110's q-value algorithm so we cannot accidentally
 *     respond with markdown to a browser, curl, Googlebot, or Livewire.
 *
 *  Anything outside the public allow-list is passed through untouched.
 *  Headers added here do not weaken or replace SecurityHeaders.
 */
class AgentDiscovery
{
    /**
     * Exact paths (after leading slash trim) that may receive Link/agent
     * discovery headers. Kept narrow on purpose so dashboard/admin/API
     * responses are never decorated with public-facing hints.
     */
    private const PUBLIC_PATHS_EXACT = [
        '',                          // home '/'
        'terms-and-conditions',
        'privacy-policy',
        'info',
    ];

    /**
     * Path prefixes treated as public for discovery purposes.
     */
    private const PUBLIC_PATH_PREFIXES = [
        'info/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $this->isHome($request) && $this->prefersMarkdown($request)) {
            return redirect('/about.md', 302, [
                'Vary' => 'Accept',
                'Cache-Control' => 'no-store, private',
                'X-Content-Negotiation' => 'markdown',
            ]);
        }

        $response = $next($request);

        if (! $this->shouldDecorate($request, $response)) {
            return $response;
        }

        $this->appendLinkHeader($response, $this->isHome($request));
        $this->mergeVary($response, 'Accept');

        return $response;
    }

    private function isHome(Request $request): bool
    {
        return trim($request->path(), '/') === '';
    }

    private function isPublicPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (in_array($path, self::PUBLIC_PATHS_EXACT, true)) {
            return true;
        }

        foreach (self::PUBLIC_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strict markdown preference check. Returns true only when the client
     * explicitly accepts text/markdown and does NOT accept text/html, so
     * normal browsers (which always include text/html) never match.
     */
    private function prefersMarkdown(Request $request): bool
    {
        $accept = strtolower((string) $request->header('Accept', ''));

        if ($accept === '') {
            return false;
        }

        if (! str_contains($accept, 'text/markdown')) {
            return false;
        }

        if (str_contains($accept, 'text/html')) {
            return false;
        }

        if (str_contains($accept, 'application/xhtml+xml')) {
            return false;
        }

        return true;
    }

    private function shouldDecorate(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if (! $this->isPublicPath($request)) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        // Only decorate HTML responses (skip JSON/XML/file downloads).
        return str_starts_with($contentType, 'text/html');
    }

    private function appendLinkHeader(Response $response, bool $isHome): void
    {
        $links = [
            '</sitemap.xml>; rel="sitemap"; type="application/xml"',
            '</llms.txt>; rel="describedby"; type="text/plain"',
            '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
        ];

        if ($isHome) {
            $links[] = '</about.md>; rel="alternate"; type="text/markdown"; title="About NRAPA (markdown for agents)"';
        }

        $existing = $response->headers->get('Link');
        $value = $existing
            ? $existing.', '.implode(', ', $links)
            : implode(', ', $links);

        $response->headers->set('Link', $value);
    }

    private function mergeVary(Response $response, string $field): void
    {
        $existing = $response->headers->get('Vary');

        if (! $existing) {
            $response->headers->set('Vary', $field);

            return;
        }

        $parts = array_map('trim', explode(',', $existing));
        if (! in_array($field, $parts, true)) {
            $parts[] = $field;
            $response->headers->set('Vary', implode(', ', $parts));
        }
    }
}
