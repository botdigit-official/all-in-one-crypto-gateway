<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\DTOs;

/**
 * Immutable value object representing fee estimation results.
 */
final class EstimateFeeResult
{
    public function __construct(
        public readonly string $coin,
        public readonly string $slow,
        public readonly string $medium,
        public readonly string $fast,
        public readonly string $unit,       // 'BTC', 'ETH', 'sat/byte', 'gwei', etc.
        public readonly array  $raw = [],
    ) {}

    /**
     * Create with a single flat fee (for chains without priority levels).
     */
    public static function flat(string $coin, string $fee, string $unit = ''): self
    {
        return new self(
            coin: $coin,
            slow: $fee,
            medium: $fee,
            fast: $fee,
            unit: $unit ?: $coin,
        );
    }

    /**
     * Get the recommended fee (medium priority).
     */
    public function recommended(): string
    {
        return $this->medium;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'coin'   => $this->coin,
            'slow'   => $this->slow,
            'medium' => $this->medium,
            'fast'   => $this->fast,
            'unit'   => $this->unit,
        ];
    }
}
