<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\DTOs;

/**
 * Immutable value object representing a send/transfer result.
 */
final class SendResult
{
    public function __construct(
        public readonly string  $coin,
        public readonly string  $txHash,
        public readonly string  $fromAddress,
        public readonly string  $toAddress,
        public readonly string  $amount,
        public readonly string  $fee,
        public readonly string  $status,      // 'broadcast', 'confirmed', 'failed'
        public readonly ?int    $nonce = null,
        public readonly array   $raw = [],
    ) {}

    /**
     * Check if the transaction was successfully broadcast.
     */
    public function isBroadcast(): bool
    {
        return $this->status === 'broadcast' || $this->status === 'confirmed';
    }

    /**
     * Check if the transaction failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'coin'         => $this->coin,
            'tx_hash'      => $this->txHash,
            'from_address' => $this->fromAddress,
            'to_address'   => $this->toAddress,
            'amount'       => $this->amount,
            'fee'          => $this->fee,
            'status'       => $this->status,
            'nonce'        => $this->nonce,
        ];
    }
}
