<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Adapters;

use Botdigit\CryptoGateway\Exceptions\RpcConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Ethereum JSON-RPC adapter for EVM-compatible chains (ETH, BSC, Polygon, etc.).
 *
 * Uses eth_* JSON-RPC methods via Guzzle HTTP client.
 */
class Web3Adapter
{
    protected Client $client;
    protected string $rpcUrl;
    protected int $chainId;
    protected int $requestId = 0;

    public function __construct(string $rpcUrl, int $chainId = 1, array $options = [])
    {
        $this->rpcUrl  = rtrim($rpcUrl, '/');
        $this->chainId = $chainId;

        $this->client = new Client([
            'base_uri' => $this->rpcUrl,
            'timeout'  => $options['timeout'] ?? 30,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send an eth_* JSON-RPC request.
     *
     * @param  string  $method  e.g., 'eth_getBalance', 'eth_sendRawTransaction'
     * @param  array   $params  Method parameters
     * @return mixed   The 'result' field from the RPC response
     *
     * @throws RpcConnectionException
     */
    public function call(string $method, array $params = []): mixed
    {
        $this->requestId++;

        $payload = [
            'jsonrpc' => '2.0',
            'id'      => $this->requestId,
            'method'  => $method,
            'params'  => $params,
        ];

        try {
            $response = $this->client->post('/', [
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RpcConnectionException('Invalid JSON response from Ethereum RPC.');
            }

            if (isset($body['error'])) {
                $error   = $body['error'];
                $message = $error['message'] ?? 'Unknown Ethereum RPC error';
                $code    = $error['code'] ?? -1;

                throw new RpcConnectionException("ETH RPC Error [{$code}]: {$message}", $code);
            }

            return $body['result'] ?? null;
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "Ethereum RPC connection failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the balance of an address in Wei (hex string).
     */
    public function getBalance(string $address, string $block = 'latest'): string
    {
        return $this->call('eth_getBalance', [$address, $block]);
    }

    /**
     * Get the current block number.
     */
    public function getBlockNumber(): string
    {
        return $this->call('eth_blockNumber');
    }

    /**
     * Get transaction details by hash.
     */
    public function getTransaction(string $txHash): ?array
    {
        return $this->call('eth_getTransactionByHash', [$txHash]);
    }

    /**
     * Get transaction receipt (confirmation status).
     */
    public function getTransactionReceipt(string $txHash): ?array
    {
        return $this->call('eth_getTransactionReceipt', [$txHash]);
    }

    /**
     * Get the current gas price in Wei.
     */
    public function getGasPrice(): string
    {
        return $this->call('eth_gasPrice');
    }

    /**
     * Estimate gas for a transaction.
     */
    public function estimateGas(array $tx): string
    {
        return $this->call('eth_estimateGas', [$tx]);
    }

    /**
     * Get the transaction count (nonce) for an address.
     */
    public function getTransactionCount(string $address, string $block = 'latest'): string
    {
        return $this->call('eth_getTransactionCount', [$address, $block]);
    }

    /**
     * Send a raw signed transaction.
     */
    public function sendRawTransaction(string $signedTx): string
    {
        return $this->call('eth_sendRawTransaction', [$signedTx]);
    }

    /**
     * Call a smart contract function (read-only, no gas).
     */
    public function ethCall(array $tx, string $block = 'latest'): string
    {
        return $this->call('eth_call', [$tx, $block]);
    }

    /**
     * Get the chain ID.
     */
    public function getChainId(): int
    {
        return $this->chainId;
    }

    /**
     * Test if the RPC endpoint is reachable.
     */
    public function isConnected(): bool
    {
        try {
            $this->getBlockNumber();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Hex Conversion Helpers ──────────────────────────────────────────

    /**
     * Convert a hex string to a decimal string (for large numbers).
     */
    public static function hexToDec(string $hex): string
    {
        $hex = str_starts_with($hex, '0x') ? substr($hex, 2) : $hex;

        if (empty($hex) || $hex === '0') {
            return '0';
        }

        // Use bcmath for large number support
        $dec = '0';
        $len = strlen($hex);

        for ($i = 0; $i < $len; $i++) {
            $dec = bcadd(
                bcmul($dec, '16'),
                (string) hexdec($hex[$i])
            );
        }

        return $dec;
    }

    /**
     * Convert a decimal string to a hex string.
     */
    public static function decToHex(string $dec): string
    {
        if ($dec === '0') {
            return '0x0';
        }

        $hex = '';
        $chars = '0123456789abcdef';

        while (bccomp($dec, '0') > 0) {
            $remainder = bcmod($dec, '16');
            $hex       = $chars[(int) $remainder] . $hex;
            $dec       = bcdiv($dec, '16', 0);
        }

        return '0x' . $hex;
    }

    /**
     * Convert Wei to Ether.
     */
    public static function weiToEther(string $wei): string
    {
        return bcdiv($wei, bcpow('10', '18'), 18);
    }

    /**
     * Convert Ether to Wei.
     */
    public static function etherToWei(string $ether): string
    {
        return bcmul($ether, bcpow('10', '18'), 0);
    }
}
