<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Adapters;

use Botdigit\CryptoGateway\Exceptions\RpcConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Solana JSON-RPC adapter.
 *
 * Solana uses JSON-RPC 2.0 but with its own method namespace.
 */
class SolanaRpcAdapter
{
    protected Client $client;
    protected string $rpcUrl;
    protected int $requestId = 0;

    public function __construct(string $rpcUrl, array $options = [])
    {
        $this->rpcUrl = rtrim($rpcUrl, '/');

        $this->client = new Client([
            'base_uri' => $this->rpcUrl,
            'timeout'  => $options['timeout'] ?? 30,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send a Solana JSON-RPC request.
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
                throw new RpcConnectionException('Invalid JSON response from Solana RPC.');
            }

            if (isset($body['error'])) {
                $error   = $body['error'];
                $message = $error['message'] ?? 'Unknown Solana RPC error';
                $code    = $error['code'] ?? -1;

                throw new RpcConnectionException("Solana RPC Error [{$code}]: {$message}", $code);
            }

            return $body['result'] ?? null;
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "Solana RPC connection failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the balance of an account (in lamports).
     */
    public function getBalance(string $pubkey): array
    {
        return $this->call('getBalance', [$pubkey]);
    }

    /**
     * Get transaction details by signature.
     */
    public function getTransaction(string $signature): ?array
    {
        return $this->call('getTransaction', [
            $signature,
            ['encoding' => 'json', 'maxSupportedTransactionVersion' => 0],
        ]);
    }

    /**
     * Get recent transaction signatures for an account.
     */
    public function getSignaturesForAddress(string $address, int $limit = 50): array
    {
        return $this->call('getSignaturesForAddress', [
            $address,
            ['limit' => $limit],
        ]);
    }

    /**
     * Get account info.
     */
    public function getAccountInfo(string $pubkey): ?array
    {
        return $this->call('getAccountInfo', [
            $pubkey,
            ['encoding' => 'jsonParsed'],
        ]);
    }

    /**
     * Get the latest blockhash.
     */
    public function getLatestBlockhash(): array
    {
        return $this->call('getLatestBlockhash');
    }

    /**
     * Send a signed transaction.
     */
    public function sendTransaction(string $signedTx): string
    {
        return $this->call('sendTransaction', [
            $signedTx,
            ['encoding' => 'base64'],
        ]);
    }

    /**
     * Get the current slot (block height equivalent).
     */
    public function getSlot(): int
    {
        return $this->call('getSlot');
    }

    /**
     * Get minimum balance for rent exemption.
     */
    public function getMinimumBalanceForRentExemption(int $dataLength): int
    {
        return $this->call('getMinimumBalanceForRentExemption', [$dataLength]);
    }

    /**
     * Get recent prioritization fees (for fee estimation).
     */
    public function getRecentPrioritizationFees(): array
    {
        return $this->call('getRecentPrioritizationFees');
    }

    /**
     * Get the current cluster health.
     */
    public function getHealth(): string
    {
        return $this->call('getHealth');
    }

    /**
     * Test if the Solana RPC is reachable.
     */
    public function isConnected(): bool
    {
        try {
            $health = $this->getHealth();
            return $health === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Solana Helpers ──────────────────────────────────────────────────

    /**
     * Convert lamports to SOL.
     */
    public static function lamportsToSol(string $lamports): string
    {
        return bcdiv($lamports, '1000000000', 9);
    }

    /**
     * Convert SOL to lamports.
     */
    public static function solToLamports(string $sol): string
    {
        return bcmul($sol, '1000000000', 0);
    }
}
