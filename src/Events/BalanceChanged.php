<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Events;

use Botdigit\CryptoGateway\Models\CryptoWallet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a wallet's balance changes.
 */
class BalanceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CryptoWallet $wallet,
        public readonly string $coin,
        public readonly string $oldBalance,
        public readonly string $newBalance,
    ) {}

    /**
     * Get the difference between old and new balance.
     */
    public function difference(): string
    {
        return bcsub($this->newBalance, $this->oldBalance, 18);
    }

    /**
     * Check if the balance increased.
     */
    public function increased(): bool
    {
        return bccomp($this->newBalance, $this->oldBalance, 18) > 0;
    }
}
