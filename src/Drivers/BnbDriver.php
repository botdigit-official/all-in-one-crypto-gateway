<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Adapters\Web3Adapter;
use Illuminate\Contracts\Foundation\Application;

/**
 * BNB (Binance Smart Chain) driver — EVM-compatible.
 *
 * Extends EthereumDriver since BSC is fully EVM-compatible.
 * Overrides only the coin-specific metadata.
 */
class BnbDriver extends EthereumDriver
{
    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        // Call AbstractDriver constructor directly to avoid EthereumDriver re-creating the RPC
        AbstractDriver::__construct($config, $network, $alias, $app);

        $this->rpc = new Web3Adapter(
            rpcUrl: $this->getConfig('rpc_url', 'https://bsc-dataseed.binance.org'),
            chainId: (int) $this->getConfig('chain_id', 56),
        );
    }

    public function getCoinSymbol(): string
    {
        return 'BNB';
    }

    public function getDecimals(): int
    {
        return 18; // Same as ETH
    }
}
