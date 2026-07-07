<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for crypto gateway API endpoints.
 *
 * Uses a simple sliding window counter stored in cache.
 */
class RateLimitCrypto
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = (int) config('cryptogateway.security.rate_limit', 60);

        if ($limit <= 0) {
            return $next($request);
        }

        $key = 'cryptogateway:ratelimit:' . ($request->ip() ?? 'unknown');

        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= $limit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        Cache::put($key, $attempts + 1, 60); // 60-second window

        $response = $next($request);

        // Add rate limit headers
        if (method_exists($response, 'header')) {
            $response->header('X-RateLimit-Limit', (string) $limit);
            $response->header('X-RateLimit-Remaining', (string) max(0, $limit - $attempts - 1));
        }

        return $response;
    }
}
