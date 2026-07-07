<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\DTOs;

/**
 * Immutable value object representing a transaction.
 */
final class TransactionResult
{
    public function __construct(
        public readonly string  $coin,
        public readonly string  $txHash,
        public readonly string  $fromAddress,
        public readonly string  $toAddress,
        public readonly string  $amount,
        public readonly string  $fee,
        public readonly int     $confirmations,
        public readonly string  $status,       // 'pending', 'confirmed', 'failed'
        public readonly string  $direction,    // 'incoming', 'outgoing'
        public readonly ?int    $blockNumber,
        public readonly ?string $blockHash,
        public readonly ?int    $timestamp,
        public readonly array   $raw = [],
    ) {}

    /**
     * Check if the transaction is confirmed (1+ confirmations).
     */
    public function isConfirmed(): bool
    {
        return $this->confirmations > 0 && $this->status === 'confirmed';
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transaction has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if this transaction has met the required confirmations.
     */
    public function meetsConfirmations(int $required): bool
    {
        return $this->confirmations >= $required;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'coin'          => $this->coin,
            'tx_hash'       => $this->txHash,
            'from_address'  => $this->fromAddress,
            'to_address'    => $this->toAddress,
            'amount'        => $this->amount,
            'fee'           => $this->fee,
            'confirmations' => $this->confirmations,
            'status'        => $this->status,
            'direction'     => $this->direction,
            'block_number'  => $this->blockNumber,
            'block_hash'    => $this->blockHash,
            'timestamp'     => $this->timestamp,
        ];
    }
}
