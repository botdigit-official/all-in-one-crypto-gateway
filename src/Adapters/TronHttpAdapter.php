<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Adapters;

use Botdigit\CryptoGateway\Exceptions\RpcConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * TRON HTTP API adapter.
 *
 * TRON uses a REST-style HTTP API (not JSON-RPC) via TronGrid or full nodes.
 */
class TronHttpAdapter
{
    protected Client $client;
    protected string $apiUrl;
    protected ?string $apiKey;

    public function __construct(string $apiUrl, ?string $apiKey = null, array $options = [])
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        if ($apiKey) {
            $headers['TRON-PRO-API-KEY'] = $apiKey;
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout'  => $options['timeout'] ?? 30,
            'headers'  => $headers,
        ]);
    }

    /**
     * Send a POST request to a TRON API endpoint.
     *
     * @throws RpcConnectionException
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RpcConnectionException('Invalid JSON response from TRON API.');
            }

            // TRON returns error info in the 'Error' or 'error' key
            if (isset($body['Error'])) {
                throw new RpcConnectionException("TRON API Error: {$body['Error']}");
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "TRON API request failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Send a GET request to a TRON API endpoint.
     *
     * @throws RpcConnectionException
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $query,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RpcConnectionException('Invalid JSON response from TRON API.');
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "TRON API request failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get account information (balance, resources, etc.).
     */
    public function getAccount(string $address): array
    {
        return $this->post('/wallet/getaccount', [
            'address' => $address,
            'visible' => true,
        ]);
    }

    /**
     * Get transaction info by ID.
     */
    public function getTransactionById(string $txId): array
    {
        return $this->post('/wallet/gettransactionbyid', [
            'value' => $txId,
        ]);
    }

    /**
     * Get transaction info (includes fee, receipt, etc.).
     */
    public function getTransactionInfoById(string $txId): array
    {
        return $this->post('/wallet/gettransactioninfobyid', [
            'value' => $txId,
        ]);
    }

    /**
     * Create a TRX transfer transaction (unsigned).
     */
    public function createTransaction(string $ownerAddress, string $toAddress, int $amount): array
    {
        return $this->post('/wallet/createtransaction', [
            'owner_address' => $ownerAddress,
            'to_address'    => $toAddress,
            'amount'        => $amount,
            'visible'       => true,
        ]);
    }

    /**
     * Broadcast a signed transaction.
     */
    public function broadcastTransaction(array $signedTransaction): array
    {
        return $this->post('/wallet/broadcasttransaction', $signedTransaction);
    }

    /**
     * Get current block.
     */
    public function getNowBlock(): array
    {
        return $this->post('/wallet/getnowblock');
    }

    /**
     * Validate a TRON address.
     */
    public function validateAddress(string $address): array
    {
        return $this->post('/wallet/validateaddress', [
            'address' => $address,
            'visible' => true,
        ]);
    }

    /**
     * Trigger a TRC-20 smart contract call (e.g., balanceOf, transfer).
     */
    public function triggerSmartContract(
        string $contractAddress,
        string $functionSelector,
        string $parameter,
        string $ownerAddress,
        int $feeLimit = 100000000,
    ): array {
        return $this->post('/wallet/triggersmartcontract', [
            'owner_address'     => $ownerAddress,
            'contract_address'  => $contractAddress,
            'function_selector' => $functionSelector,
            'parameter'         => $parameter,
            'fee_limit'         => $feeLimit,
            'visible'           => true,
        ]);
    }

    /**
     * Trigger a constant (read-only) smart contract call.
     */
    public function triggerConstantContract(
        string $contractAddress,
        string $functionSelector,
        string $parameter,
        string $ownerAddress,
    ): array {
        return $this->post('/wallet/triggerconstantcontract', [
            'owner_address'     => $ownerAddress,
            'contract_address'  => $contractAddress,
            'function_selector' => $functionSelector,
            'parameter'         => $parameter,
            'visible'           => true,
        ]);
    }

    /**
     * Test if the TRON API is reachable.
     */
    public function isConnected(): bool
    {
        try {
            $this->getNowBlock();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── TRON Helpers ────────────────────────────────────────────────────

    /**
     * Convert SUN to TRX.
     */
    public static function sunToTrx(string $sun): string
    {
        return bcdiv($sun, '1000000', 6);
    }

    /**
     * Convert TRX to SUN.
     */
    public static function trxToSun(string $trx): string
    {
        return bcmul($trx, '1000000', 0);
    }
}
