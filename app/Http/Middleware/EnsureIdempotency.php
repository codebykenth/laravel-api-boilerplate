<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class EnsureIdempotency
{
    private const string HEADER = 'Idempotency-Key';

    private const string CACHE_PREFIX = 'idempotency:';

    private const int TTL_SECONDS = 86400; // 24 hours

    private const int LOCK_TIMEOUT_SECONDS = 10;

    private const int LOCK_WAIT_SECONDS = 5;

    /**
     * Handle an incoming request.
     *
     * When an Idempotency-Key header is present on a write request, the middleware acquires an atomic cache lock and either returns a previously cached response or processes the request and caches the result to prevent duplicate operations.
     *
     * Note: Client configuration required:
     * - Clients (e.g., React/Axios) must generate and pass a unique UUID v4 in the `Idempotency-Key` header per distinct user intent/action (e.g., on checkout/submit).
     * - On automatic network retries, clients must reuse the SAME key for that retry attempt to safely prevent duplicate processing or double-charging.
     * - Requests omitting this header bypass idempotency checks and execute as normal.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header(self::HEADER);

        if ($idempotencyKey === null || $request->isMethodSafe()) {
            return $next($request);
        }

        $cacheKey = $this->resolveCacheKey($request, $idempotencyKey);

        // Return cached response if this key was already processed
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $this->replayResponse($cached);
        }

        // Acquire atomic lock to prevent concurrent duplicate processing
        $lock = Cache::lock($cacheKey.':lock', self::LOCK_TIMEOUT_SECONDS);

        try {
            $lock->block(self::LOCK_WAIT_SECONDS);
        } catch (Throwable) {
            throw new ConflictHttpException('A request with this idempotency key is still being processed.');
        }

        try {
            // Double-check after acquiring lock (another request may have completed)
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->replayResponse($cached);
            }

            $response = $next($request);

            // Cache non-server-error responses (including 4xx, per idempotency contract)
            if (! $response->isServerError()) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->forceRelease();
        }
    }

    /**
     * Build a scoped cache key from user identity and the client-provided key.
     */
    private function resolveCacheKey(Request $request, string $idempotencyKey): string
    {
        $scope = $request->user()?->id ?? $request->ip();

        return self::CACHE_PREFIX.hash('xxh128', $scope.':'.$idempotencyKey);
    }

    /**
     * Reconstruct a response from cached data with a replay indicator header.
     */
    private function replayResponse(array $cached): Response
    {
        return response($cached['body'], $cached['status'])
            ->header('Content-Type', 'application/json')
            ->header('X-Idempotent-Replayed', 'true');
    }
}
