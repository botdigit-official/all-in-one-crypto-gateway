<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Contracts;

use Botdigit\CryptoGateway\DTOs\BalanceResult;

/**
 * Drivers that support tokens (ERC-20, TRC-20, SPL, etc.) implement this interface.
 */
interface HasTokens
{
    /**
     * Get the token balance for an address.
     *
     * @param  string  $address          Holder address
     * @param  string  $contractAddress  Token contract address
     */
    public function getTokenBalance(string $address, string $contractAddress): BalanceResult;

    /**
     * Send tokens to an address.
     *
     * @param  string  $contractAddress  Token contract address
     * @param  string  $to               Recipient address
     * @param  string  $amount           Amount in token units
     * @param  array   $options          Driver-specific options
     */
    public function sendToken(string $contractAddress, string $to, string $amount, array $options = []): mixed;

    /**
     * Get the decimals of a token contract.
     */
    public function getTokenDecimals(string $contractAddress): int;

    /**
     * Get the symbol of a token contract.
     */
    public function getTokenSymbol(string $contractAddress): string;
}
