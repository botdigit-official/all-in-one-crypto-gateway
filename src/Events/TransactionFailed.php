<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Events;

use Botdigit\CryptoGateway\Models\CryptoTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a transaction fails or is rejected by the network.
 */
class TransactionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CryptoTransaction $transaction,
        public readonly string $coin,
        public readonly string $reason,
    ) {}
}
