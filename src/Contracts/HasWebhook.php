<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Contracts;

/**
 * Drivers that support webhook/IPN notifications implement this interface.
 */
interface HasWebhook
{
    /**
     * Register a webhook URL for transaction notifications.
     *
     * @param  string  $url     Callback URL
     * @param  array   $events  Events to subscribe to (e.g., ['transaction.received'])
     * @return string  Webhook ID or subscription reference
     */
    public function registerWebhook(string $url, array $events = []): string;

    /**
     * Remove a previously registered webhook.
     */
    public function removeWebhook(string $webhookId): bool;

    /**
     * Verify the signature of an incoming webhook payload.
     *
     * @param  string  $payload    Raw request body
     * @param  string  $signature  Signature header value
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse a raw webhook payload into a normalized structure.
     *
     * @return array{event: string, tx_hash: string, address: string, amount: string, confirmations: int}
     */
    public function parseWebhookPayload(string $payload): array;
}
