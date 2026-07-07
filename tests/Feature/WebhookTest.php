<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Feature;

use Botdigit\CryptoGateway\Events\WebhookReceived;
use Botdigit\CryptoGateway\Models\CryptoWebhook;
use Botdigit\CryptoGateway\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class WebhookTest extends TestCase
{
    public function test_webhook_route_exists(): void
    {
        $response = $this->postJson('/cryptogateway/webhook/btc', [
            'event' => 'transaction.received',
            'tx_hash' => 'abc123',
        ]);

        // Should not be 404 (route exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_webhook_without_signature_returns_403(): void
    {
        config(['cryptogateway.security.webhook_secret' => 'test-secret-123']);

        $response = $this->postJson('/cryptogateway/webhook/btc', [
            'event' => 'transaction.received',
        ]);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_webhook_with_valid_signature_returns_200(): void
    {
        $secret  = 'test-secret-123';
        $payload = json_encode(['event' => 'transaction.received', 'tx_hash' => 'abc123']);

        config(['cryptogateway.security.webhook_secret' => $secret]);

        $signature = hash_hmac('sha256', $payload, $secret);

        Event::fake();

        $response = $this->postJson('/cryptogateway/webhook/btc', json_decode($payload, true), [
            'X-Webhook-Signature' => $signature,
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        Event::assertDispatched(WebhookReceived::class, function ($event) {
            return $event->coin === 'BTC';
        });
    }

    public function test_webhook_with_invalid_signature_returns_403(): void
    {
        config(['cryptogateway.security.webhook_secret' => 'test-secret-123']);

        $response = $this->postJson('/cryptogateway/webhook/btc', [
            'event' => 'transaction.received',
        ], [
            'X-Webhook-Signature' => 'invalid-signature',
        ]);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_webhook_is_logged_to_database(): void
    {
        $secret  = 'test-secret-123';
        $payload = json_encode(['event' => 'transaction.received', 'tx_hash' => 'xyz789']);

        config(['cryptogateway.security.webhook_secret' => $secret]);

        $signature = hash_hmac('sha256', $payload, $secret);

        Event::fake();

        $this->postJson('/cryptogateway/webhook/eth', json_decode($payload, true), [
            'X-Webhook-Signature' => $signature,
        ]);

        $this->assertDatabaseHas(
            (new CryptoWebhook)->getTable(),
            [
                'coin'        => 'ETH',
                'event_type'  => 'transaction.received',
                'is_verified' => true,
            ]
        );
    }
}
