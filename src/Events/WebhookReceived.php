<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when any webhook/IPN is received from a blockchain service.
 */
class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $coin,
        public readonly string $eventType,
        public readonly array $payload,
        public readonly bool $isVerified,
    ) {}
}
