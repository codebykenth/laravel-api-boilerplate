<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    private const string CACHE_PREFIX = 'response:';

    private const int DEFAULT_TTL_SECONDS = 60;

    /**
     * Handle an incoming request.
     *
     * Caches successful GET responses for the given TTL. The cache is scoped per authenticated user (or "guest") and the full URL including query params.
     * Respects Cache-Control: no-cache to bypass the cache.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = self::DEFAULT_TTL_SECONDS): Response
    {
        if (! $request->isMethodCacheable() || $request->header('Cache-Control') === 'no-cache') {
            return $next($request);
        }

        $cacheKey = $this->resolveCacheKey($request);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response($cached['body'], $cached['status'])
                ->header('Content-Type', 'application/json')
                ->header('X-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'body' => $response->getContent(),
            ], $ttl);
        }

        $response->headers->set('X-Cache', 'MISS');

        return $response;
    }

    /**
     * Build a user-scoped cache key from the full URL.
     */
    private function resolveCacheKey(Request $request): string
    {
        $scope = $request->user()?->id ?? 'guest';

        return self::CACHE_PREFIX.hash('xxh128', $scope.'|'.$request->fullUrl());
    }
}
