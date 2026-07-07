<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Helpers;

/**
 * Resolves mainnet/testnet RPC endpoints and network-specific parameters.
 */
class NetworkResolver
{
    /**
     * Default testnet endpoints for built-in coins.
     */
    protected static array $testnetEndpoints = [
        'btc' => 'http://127.0.0.1:18332',
        'eth' => 'https://sepolia.infura.io/v3/',
        'trx' => 'https://api.shasta.trongrid.io',
        'sol' => 'https://api.devnet.solana.com',
        'ltc' => 'http://127.0.0.1:19332',
        'bnb' => 'https://data-seed-prebsc-1-s1.binance.org:8545',
    ];

    /**
     * Default mainnet endpoints for built-in coins.
     */
    protected static array $mainnetEndpoints = [
        'btc' => 'http://127.0.0.1:8332',
        'eth' => 'https://mainnet.infura.io/v3/',
        'trx' => 'https://api.trongrid.io',
        'sol' => 'https://api.mainnet-beta.solana.com',
        'ltc' => 'http://127.0.0.1:9332',
        'bnb' => 'https://bsc-dataseed.binance.org',
    ];

    /**
     * Testnet chain IDs for EVM chains.
     */
    protected static array $testnetChainIds = [
        'eth' => 11155111,  // Sepolia
        'bnb' => 97,        // BSC Testnet
    ];

    /**
     * Get the appropriate RPC endpoint based on network mode.
     */
    public static function getEndpoint(string $coin, string $network = 'mainnet'): string
    {
        $coin = strtolower($coin);

        if ($network === 'testnet') {
            return self::$testnetEndpoints[$coin] ?? '';
        }

        return self::$mainnetEndpoints[$coin] ?? '';
    }

    /**
     * Get the chain ID for the given network (EVM chains only).
     */
    public static function getChainId(string $coin, string $network = 'mainnet'): ?int
    {
        $coin = strtolower($coin);

        if ($network === 'testnet' && isset(self::$testnetChainIds[$coin])) {
            return self::$testnetChainIds[$coin];
        }

        return match ($coin) {
            'eth' => 1,
            'bnb' => 56,
            default => null,
        };
    }

    /**
     * Get the block explorer URL for a transaction.
     */
    public static function getExplorerUrl(string $coin, string $txHash, string $network = 'mainnet'): string
    {
        $coin = strtolower($coin);

        return match ($coin) {
            'btc' => $network === 'testnet'
                ? "https://mempool.space/testnet/tx/{$txHash}"
                : "https://mempool.space/tx/{$txHash}",
            'eth' => $network === 'testnet'
                ? "https://sepolia.etherscan.io/tx/{$txHash}"
                : "https://etherscan.io/tx/{$txHash}",
            'trx' => $network === 'testnet'
                ? "https://shasta.tronscan.org/#/transaction/{$txHash}"
                : "https://tronscan.org/#/transaction/{$txHash}",
            'sol' => $network === 'testnet'
                ? "https://explorer.solana.com/tx/{$txHash}?cluster=devnet"
                : "https://explorer.solana.com/tx/{$txHash}",
            'ltc' => "https://blockchair.com/litecoin/transaction/{$txHash}",
            'bnb' => $network === 'testnet'
                ? "https://testnet.bscscan.com/tx/{$txHash}"
                : "https://bscscan.com/tx/{$txHash}",
            default => '',
        };
    }

    /**
     * Get the block explorer URL for an address.
     */
    public static function getAddressExplorerUrl(string $coin, string $address, string $network = 'mainnet'): string
    {
        $coin = strtolower($coin);

        return match ($coin) {
            'btc' => "https://mempool.space/address/{$address}",
            'eth' => "https://etherscan.io/address/{$address}",
            'trx' => "https://tronscan.org/#/address/{$address}",
            'sol' => "https://explorer.solana.com/address/{$address}",
            'ltc' => "https://blockchair.com/litecoin/address/{$address}",
            'bnb' => "https://bscscan.com/address/{$address}",
            default => '',
        };
    }
}
