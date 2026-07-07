<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\DTOs;

/**
 * Immutable value object representing a balance inquiry result.
 */
final class BalanceResult
{
    public function __construct(
        public readonly string $coin,
        public readonly string $address,
        public readonly string $confirmed,
        public readonly string $unconfirmed,
        public readonly string $total,
        public readonly int    $decimals,
        public readonly array  $raw = [],
    ) {}

    /**
     * Create from a simple confirmed balance.
     */
    public static function fromConfirmed(string $coin, string $address, string $confirmed, int $decimals = 8): self
    {
        return new self(
            coin: $coin,
            address: $address,
            confirmed: $confirmed,
            unconfirmed: '0',
            total: $confirmed,
            decimals: $decimals,
        );
    }

    /**
     * Check if the balance is zero.
     */
    public function isZero(): bool
    {
        return bccomp($this->total, '0', $this->decimals) === 0;
    }

    /**
     * Check if the balance has enough funds for a given amount.
     */
    public function hasEnough(string $amount): bool
    {
        return bccomp($this->confirmed, $amount, $this->decimals) >= 0;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'coin'        => $this->coin,
            'address'     => $this->address,
            'confirmed'   => $this->confirmed,
            'unconfirmed' => $this->unconfirmed,
            'total'       => $this->total,
            'decimals'    => $this->decimals,
        ];
    }
}
