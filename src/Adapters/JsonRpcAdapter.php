<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Adapters;

use Botdigit\CryptoGateway\Exceptions\RpcConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * JSON-RPC 2.0 adapter for Bitcoin, Litecoin, and similar UTXO-based coins.
 *
 * Communicates with bitcoind/litecoind via standard JSON-RPC protocol.
 */
class JsonRpcAdapter
{
    protected Client $client;
    protected string $host;
    protected ?string $user;
    protected ?string $password;
    protected int $requestId = 0;

    public function __construct(string $host, ?string $user = null, ?string $password = null, array $options = [])
    {
        $this->host     = rtrim($host, '/');
        $this->user     = $user;
        $this->password = $password;

        $clientConfig = [
            'base_uri' => $this->host,
            'timeout'  => $options['timeout'] ?? 30,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ];

        // Basic auth for local nodes
        if ($user && $password) {
            $clientConfig['auth'] = [$user, $password];
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * Send a JSON-RPC request.
     *
     * @param  string  $method  RPC method name (e.g., 'getbalance', 'getnewaddress')
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
                throw new RpcConnectionException('Invalid JSON response from RPC server.');
            }

            if (isset($body['error']) && $body['error'] !== null) {
                $error = $body['error'];
                $message = $error['message'] ?? 'Unknown RPC error';
                $code    = $error['code'] ?? -1;

                throw new RpcConnectionException("RPC Error [{$code}]: {$message}", $code);
            }

            return $body['result'] ?? null;
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "JSON-RPC connection failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Send multiple RPC requests in a batch.
     *
     * @param  array  $calls  Array of ['method' => string, 'params' => array]
     * @return array  Array of results in the same order
     *
     * @throws RpcConnectionException
     */
    public function batch(array $calls): array
    {
        $payload = [];

        foreach ($calls as $call) {
            $this->requestId++;
            $payload[] = [
                'jsonrpc' => '2.0',
                'id'      => $this->requestId,
                'method'  => $call['method'],
                'params'  => $call['params'] ?? [],
            ];
        }

        try {
            $response = $this->client->post('/', [
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RpcConnectionException('Invalid JSON response from batch RPC request.');
            }

            // Sort results by ID to maintain order
            usort($body, fn($a, $b) => ($a['id'] ?? 0) - ($b['id'] ?? 0));

            return array_map(fn($item) => $item['result'] ?? null, $body);
        } catch (GuzzleException $e) {
            throw new RpcConnectionException(
                "Batch JSON-RPC connection failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Test if the RPC server is reachable.
     */
    public function isConnected(): bool
    {
        try {
            $this->call('getblockchaininfo');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
