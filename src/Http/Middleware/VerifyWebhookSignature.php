<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify HMAC-SHA256 webhook signatures.
 *
 * Applied to webhook routes to ensure requests are authentic.
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret    = config('cryptogateway.security.webhook_secret');
        $signature = $request->header('X-Webhook-Signature', '');

        // If no secret is configured, allow the request through
        // (the controller will handle verification and logging)
        if (empty($secret)) {
            return $next($request);
        }

        if (empty($signature)) {
            Log::channel(config('cryptogateway.logging.channel', 'stack'))
                ->warning('[CryptoGateway] Webhook request missing signature header');

            return response()->json([
                'status'  => 'error',
                'message' => 'Missing webhook signature',
            ], 401);
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::channel(config('cryptogateway.logging.channel', 'stack'))
                ->warning('[CryptoGateway] Webhook signature mismatch', [
                    'provided' => $signature,
                ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid webhook signature',
            ], 403);
        }

        return $next($request);
    }
}
