<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Contracts;

use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;

/**
 * Core contract that every coin driver MUST implement.
 *
 * This is the unified API — learn once, use for every coin.
 */
interface DriverInterface
{
    // ── Wallet & Address ────────────────────────────────────────────────

    /**
     * Generate a new receiving address.
     *
     * @param  string|null  $label  Optional label (e.g., order ID, user ID)
     */
    public function generateAddress(?string $label = null): AddressResult;

    /**
     * Validate whether an address is correctly formatted for this coin.
     */
    public function validateAddress(string $address): bool;

    // ── Balance ─────────────────────────────────────────────────────────

    /**
     * Get the balance for a given address.
     */
    public function getBalance(string $address): BalanceResult;

    // ── Transactions ────────────────────────────────────────────────────

    /**
     * Get details of a specific transaction by hash.
     */
    public function getTransaction(string $txHash): TransactionResult;

    /**
     * Get transaction history for an address.
     *
     * @return TransactionResult[]
     */
    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array;

    // ── Sending ─────────────────────────────────────────────────────────

    /**
     * Send cryptocurrency to an address.
     *
     * @param  string  $to       Destination address
     * @param  string  $amount   Amount in coin units (e.g., "0.5" for 0.5 BTC)
     * @param  array   $options  Driver-specific options (gasLimit, memo, etc.)
     */
    public function send(string $to, string $amount, array $options = []): SendResult;

    /**
     * Estimate the network fee for a transaction.
     */
    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult;

    // ── Network Info ────────────────────────────────────────────────────

    /**
     * Get the current network: 'mainnet' or 'testnet'.
     */
    public function getNetwork(): string;

    /**
     * Get the coin symbol: 'BTC', 'ETH', 'TRX', etc.
     */
    public function getCoinSymbol(): string;

    /**
     * Get the number of decimal places for this coin.
     * BTC = 8, ETH = 18, USDT = 6, etc.
     */
    public function getDecimals(): int;

    /**
     * Check if the driver can connect to the node/API.
     */
    public function isConnected(): bool;
}
