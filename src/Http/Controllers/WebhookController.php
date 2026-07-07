<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Http\Controllers;

use Botdigit\CryptoGateway\Events\WebhookReceived;
use Botdigit\CryptoGateway\Models\CryptoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming webhook/IPN notifications from blockchain services.
 *
 * Route: POST /cryptogateway/webhook/{driver}
 */
class WebhookController extends Controller
{
    /**
     * Handle an incoming webhook request.
     */
    public function handle(Request $request, string $driver): JsonResponse
    {
        $coin = strtoupper($driver);

        // Verify webhook signature
        $signature  = $request->header('X-Webhook-Signature', '');
        $isVerified = $this->verifySignature($request->getContent(), $signature);

        // Log the webhook
        $webhook = CryptoWebhook::create([
            'coin'        => $coin,
            'event_type'  => $request->input('event', $request->input('type', 'unknown')),
            'payload'     => $request->all(),
            'signature'   => $signature,
            'is_verified' => $isVerified,
        ]);

        if (! $isVerified) {
            Log::channel(config('cryptogateway.logging.channel', 'stack'))
                ->warning("[CryptoGateway] Unverified webhook received for {$coin}", [
                    'driver'    => $driver,
                    'signature' => $signature,
                ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid signature',
            ], 403);
        }

        // Dispatch event for the application to handle
        WebhookReceived::dispatch(
            $coin,
            $webhook->event_type,
            $request->all(),
            $isVerified,
        );

        // Mark as processed
        $webhook->markAsProcessed();

        return response()->json([
            'status'  => 'ok',
            'message' => 'Webhook processed',
        ]);
    }

    /**
     * Verify the webhook signature using HMAC-SHA256.
     */
    protected function verifySignature(string $payload, string $signature): bool
    {
        $secret = config('cryptogateway.security.webhook_secret');

        if (empty($secret) || empty($signature)) {
            // If no secret is configured, skip verification but log a warning
            if (empty($secret)) {
                Log::channel(config('cryptogateway.logging.channel', 'stack'))
                    ->warning('[CryptoGateway] No webhook secret configured. Set CRYPTO_WEBHOOK_SECRET in .env');
            }
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
