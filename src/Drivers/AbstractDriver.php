<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Contracts\DriverInterface;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Exceptions\RpcConnectionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Base class for all coin drivers.
 *
 * Provides shared functionality: config resolution, logging, caching,
 * error handling. Subclasses only implement coin-specific logic.
 */
abstract class AbstractDriver implements DriverInterface
{
    protected array $config;
    protected string $network;
    protected string $alias;
    protected Application $app;

    public function __construct(
        array $config,
        string $network,
        string $alias,
        Application $app,
    ) {
        $this->config  = $config;
        $this->network = $network;
        $this->alias   = $alias;
        $this->app     = $app;
    }

    // ── Network Info (shared implementation) ────────────────────────────

    public function getNetwork(): string
    {
        return $this->config['network'] ?? $this->network;
    }

    public function isTestnet(): bool
    {
        return $this->getNetwork() === 'testnet';
    }

    public function isMainnet(): bool
    {
        return $this->getNetwork() === 'mainnet';
    }

    // ── Caching ─────────────────────────────────────────────────────────

    /**
     * Cache a result if caching is enabled.
     */
    protected function cached(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheConfig = $this->app['config']->get('cryptogateway.cache', []);

        if (! ($cacheConfig['enabled'] ?? true)) {
            return $callback();
        }

        $ttl      = $ttl ?? ($cacheConfig['ttl'] ?? 30);
        $cacheKey = "cryptogateway:{$this->alias}:{$key}";
        $store    = $cacheConfig['driver'] ?? null;

        $cache = $store ? Cache::store($store) : Cache::store();

        return $cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear a cached key.
     */
    protected function clearCache(string $key): void
    {
        $cacheKey = "cryptogateway:{$this->alias}:{$key}";
        Cache::forget($cacheKey);
    }

    // ── Logging ─────────────────────────────────────────────────────────

    /**
     * Log an info message.
     */
    protected function log(string $message, array $context = []): void
    {
        if ($this->app['config']->get('cryptogateway.logging.enabled', true)) {
            $channel = $this->app['config']->get('cryptogateway.logging.channel', 'stack');
            Log::channel($channel)->info("[CryptoGateway:{$this->alias}] {$message}", $context);
        }
    }

    /**
     * Log an error message.
     */
    protected function logError(string $message, array $context = []): void
    {
        if ($this->app['config']->get('cryptogateway.logging.enabled', true)) {
            $channel = $this->app['config']->get('cryptogateway.logging.channel', 'stack');
            Log::channel($channel)->error("[CryptoGateway:{$this->alias}] {$message}", $context);
        }
    }

    // ── Configuration Helpers ───────────────────────────────────────────

    /**
     * Get a config value for this driver.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get the required confirmations for this coin.
     */
    protected function getRequiredConfirmations(): int
    {
        return (int) ($this->config['confirmations'] ?? 1);
    }

    // ── Error Handling ──────────────────────────────────────────────────

    /**
     * Wrap an RPC/API call with error handling.
     *
     * @throws RpcConnectionException
     */
    protected function withErrorHandling(string $operation, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (RpcConnectionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logError("{$operation} failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            throw new RpcConnectionException(
                "Failed to {$operation} for [{$this->alias}]: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }
}
