<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Helpers;

/**
 * Utility class for converting between coin base units and display units.
 *
 * Every blockchain has a smallest unit:
 *   BTC → Satoshi (10^8), ETH → Wei (10^18), TRX → Sun (10^6), SOL → Lamport (10^9)
 */
class CoinFormatter
{
    /**
     * Known coin decimal places.
     */
    protected static array $decimals = [
        'BTC'  => 8,
        'ETH'  => 18,
        'TRX'  => 6,
        'SOL'  => 9,
        'LTC'  => 8,
        'BNB'  => 18,
        'USDT' => 6,
        'USDC' => 6,
        'DOGE' => 8,
        'XRP'  => 6,
    ];

    /**
     * Convert from base units (satoshi, wei, etc.) to display units (BTC, ETH, etc.).
     *
     * @param  string  $baseAmount  Amount in smallest unit
     * @param  string  $coin        Coin symbol
     * @return string  Amount in display units
     */
    public static function fromBase(string $baseAmount, string $coin): string
    {
        $decimals = self::getDecimals($coin);
        return bcdiv($baseAmount, bcpow('10', (string) $decimals), $decimals);
    }

    /**
     * Convert from display units (BTC, ETH, etc.) to base units (satoshi, wei, etc.).
     *
     * @param  string  $displayAmount  Amount in display units
     * @param  string  $coin           Coin symbol
     * @return string  Amount in smallest unit
     */
    public static function toBase(string $displayAmount, string $coin): string
    {
        $decimals = self::getDecimals($coin);
        return bcmul($displayAmount, bcpow('10', (string) $decimals), 0);
    }

    /**
     * Format an amount for display with proper decimal places.
     */
    public static function format(string $amount, string $coin, bool $trimZeros = true): string
    {
        $decimals  = self::getDecimals($coin);
        $formatted = bcadd($amount, '0', $decimals);

        if ($trimZeros) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
            if ($formatted === '' || $formatted === '-') {
                $formatted = '0';
            }
        }

        return $formatted;
    }

    /**
     * Get the decimal places for a coin.
     */
    public static function getDecimals(string $coin): int
    {
        return self::$decimals[strtoupper($coin)] ?? 8;
    }

    /**
     * Register a custom coin's decimal places.
     */
    public static function registerCoin(string $coin, int $decimals): void
    {
        self::$decimals[strtoupper($coin)] = $decimals;
    }

    /**
     * Compare two amounts.
     *
     * @return int  -1, 0, or 1
     */
    public static function compare(string $a, string $b, string $coin): int
    {
        $decimals = self::getDecimals($coin);
        return bccomp($a, $b, $decimals);
    }

    /**
     * Add two amounts.
     */
    public static function add(string $a, string $b, string $coin): string
    {
        return bcadd($a, $b, self::getDecimals($coin));
    }

    /**
     * Subtract two amounts.
     */
    public static function subtract(string $a, string $b, string $coin): string
    {
        return bcsub($a, $b, self::getDecimals($coin));
    }
}
