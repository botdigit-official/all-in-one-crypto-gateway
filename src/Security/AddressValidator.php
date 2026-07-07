<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Security;

use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;

/**
 * Validates cryptocurrency addresses for correct format per coin.
 *
 * Performs format-level validation (regex). Does NOT verify addresses
 * against the blockchain (that requires an RPC call).
 */
class AddressValidator
{
    /**
     * Address regex patterns per coin.
     *
     * These cover the most common address formats for each chain.
     */
    protected static array $patterns = [
        'BTC' => [
            '/^1[a-km-zA-HJ-NP-Z1-9]{25,34}$/',           // Legacy P2PKH
            '/^3[a-km-zA-HJ-NP-Z1-9]{25,34}$/',           // P2SH
            '/^bc1[a-z0-9]{8,87}$/',                        // Bech32 (SegWit)
        ],
        'ETH' => [
            '/^0x[0-9a-fA-F]{40}$/',                        // Standard Ethereum
        ],
        'TRX' => [
            '/^T[a-km-zA-HJ-NP-Z1-9]{33}$/',               // TRON Base58
        ],
        'SOL' => [
            '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',             // Solana Base58
        ],
        'LTC' => [
            '/^[LM][a-km-zA-HJ-NP-Z1-9]{26,33}$/',        // Legacy
            '/^3[a-km-zA-HJ-NP-Z1-9]{25,34}$/',            // P2SH
            '/^ltc1[a-z0-9]{8,87}$/',                       // Bech32
        ],
        'BNB' => [
            '/^0x[0-9a-fA-F]{40}$/',                        // BSC (EVM compatible)
        ],
        'USDT' => [
            '/^0x[0-9a-fA-F]{40}$/',                        // ERC-20
            '/^T[a-km-zA-HJ-NP-Z1-9]{33}$/',               // TRC-20
        ],
        'USDC' => [
            '/^0x[0-9a-fA-F]{40}$/',                        // ERC-20
        ],
        'DOGE' => [
            '/^D[5-9A-HJ-NP-U][1-9A-HJ-NP-Za-km-z]{32}$/', // Dogecoin
        ],
        'XRP' => [
            '/^r[0-9a-zA-Z]{24,34}$/',                      // Ripple
        ],
    ];

    /**
     * Validate an address for a given coin.
     *
     * @param  string  $coin     Coin symbol (BTC, ETH, etc.)
     * @param  string  $address  Address to validate
     * @return bool
     */
    public static function validate(string $coin, string $address): bool
    {
        $coin = strtoupper($coin);

        if (! isset(self::$patterns[$coin])) {
            // Unknown coin — allow any non-empty string
            return strlen($address) > 0;
        }

        foreach (self::$patterns[$coin] as $pattern) {
            if (preg_match($pattern, $address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate and throw exception if invalid.
     *
     * @throws InvalidAddressException
     */
    public static function validateOrFail(string $coin, string $address): void
    {
        if (! self::validate($coin, $address)) {
            throw new InvalidAddressException(
                "Invalid {$coin} address format: {$address}"
            );
        }
    }

    /**
     * Register a custom pattern for a coin.
     */
    public static function registerPattern(string $coin, string $pattern): void
    {
        $coin = strtoupper($coin);

        if (! isset(self::$patterns[$coin])) {
            self::$patterns[$coin] = [];
        }

        self::$patterns[$coin][] = $pattern;
    }

    /**
     * Get all supported coins with address validation.
     */
    public static function supportedCoins(): array
    {
        return array_keys(self::$patterns);
    }
}
