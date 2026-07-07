<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway;

use Botdigit\CryptoGateway\Contracts\DriverInterface;
use Botdigit\CryptoGateway\Drivers\BitcoinDriver;
use Botdigit\CryptoGateway\Drivers\BnbDriver;
use Botdigit\CryptoGateway\Drivers\EthereumDriver;
use Botdigit\CryptoGateway\Drivers\LitecoinDriver;
use Botdigit\CryptoGateway\Drivers\SolanaDriver;
use Botdigit\CryptoGateway\Drivers\Tokens\Erc20Driver;
use Botdigit\CryptoGateway\Drivers\Tokens\Trc20Driver;
use Botdigit\CryptoGateway\Drivers\TronDriver;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\Exceptions\DriverNotFoundException;
use Illuminate\Support\Manager;

/**
 * Central manager that resolves and caches coin driver instances.
 *
 * Usage:
 *   CryptoGateway::driver('btc')->getBalance($address);
 *   CryptoGateway::btc()->getBalance($address);
 *   CryptoGateway::allBalances($address);
 */
class GatewayManager extends Manager
{
    /**
     * Map of built-in driver names to their classes.
     */
    protected array $builtInDrivers = [
        'bitcoin'  => BitcoinDriver::class,
        'ethereum' => EthereumDriver::class,
        'tron'     => TronDriver::class,
        'solana'   => SolanaDriver::class,
        'litecoin' => LitecoinDriver::class,
        'bnb'      => BnbDriver::class,
        'erc20'    => Erc20Driver::class,
        'trc20'    => Trc20Driver::class,
    ];

    /**
     * Get the default driver name from config.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('cryptogateway.default', 'btc');
    }

    /**
     * Create a driver instance by alias (e.g., 'btc', 'eth', 'usdt-trc20').
     */
    protected function createDriver($driver): DriverInterface
    {
        $config = $this->getDriverConfig($driver);

        if (empty($config)) {
            throw new DriverNotFoundException("Driver [{$driver}] is not configured in cryptogateway.drivers.");
        }

        $driverName = $config['driver'] ?? $driver;

        // Check if the 'driver' value is a fully-qualified class name (custom driver)
        if (class_exists($driverName)) {
            return $this->buildDriver($driverName, $config, $driver);
        }

        // Check built-in drivers
        if (isset($this->builtInDrivers[$driverName])) {
            return $this->buildDriver($this->builtInDrivers[$driverName], $config, $driver);
        }

        // Check if there's a custom creator registered via extend()
        if (isset($this->customCreators[$driverName])) {
            return $this->callCustomCreator($driverName);
        }

        throw new DriverNotFoundException("Driver [{$driverName}] is not a recognized built-in driver or valid class name.");
    }

    /**
     * Instantiate a driver class with its configuration.
     */
    protected function buildDriver(string $class, array $config, string $alias): DriverInterface
    {
        $network = $config['network'] ?? $this->config->get('cryptogateway.network', 'testnet');

        $instance = new $class(
            config: $config,
            network: $network,
            alias: $alias,
            app: $this->container,
        );

        if (! $instance instanceof DriverInterface) {
            throw new DriverNotFoundException("Driver class [{$class}] must implement DriverInterface.");
        }

        return $instance;
    }

    /**
     * Get the configuration array for a driver alias.
     */
    protected function getDriverConfig(string $alias): array
    {
        return $this->config->get("cryptogateway.drivers.{$alias}", []);
    }

    /**
     * Check if a driver alias is configured.
     */
    public function hasDriver(string $alias): bool
    {
        return ! empty($this->getDriverConfig($alias));
    }

    /**
     * Get a list of all configured driver aliases.
     */
    public function configuredDrivers(): array
    {
        return array_keys($this->config->get('cryptogateway.drivers', []));
    }

    /**
     * Get a list of all supported built-in coin names.
     */
    public function supportedCoins(): array
    {
        return array_keys($this->builtInDrivers);
    }

    /**
     * Check if a coin name is supported as a built-in.
     */
    public function isSupported(string $coin): bool
    {
        return isset($this->builtInDrivers[$coin]) || $this->hasDriver($coin);
    }

    /**
     * Get balances across all configured drivers for a single address.
     *
     * @return BalanceResult[]  Keyed by driver alias
     */
    public function allBalances(string $address): array
    {
        $results = [];

        foreach ($this->configuredDrivers() as $alias) {
            try {
                $results[$alias] = $this->driver($alias)->getBalance($address);
            } catch (\Throwable $e) {
                // Skip failed drivers, log the error
                $this->logError($alias, 'allBalances', $e);
            }
        }

        return $results;
    }

    /**
     * Run a health check against all configured drivers.
     *
     * @return array<string, bool>  Keyed by driver alias
     */
    public function healthCheck(): array
    {
        $results = [];

        foreach ($this->configuredDrivers() as $alias) {
            try {
                $results[$alias] = $this->driver($alias)->isConnected();
            } catch (\Throwable) {
                $results[$alias] = false;
            }
        }

        return $results;
    }

    /**
     * Magic method: CryptoGateway::btc() → driver('btc')
     */
    public function __call($method, $parameters)
    {
        // If the method name matches a configured driver alias, return that driver
        if ($this->hasDriver($method)) {
            return $this->driver($method);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Log a driver error if logging is enabled.
     */
    protected function logError(string $alias, string $method, \Throwable $e): void
    {
        if ($this->config->get('cryptogateway.logging.enabled', true)) {
            $channel = $this->config->get('cryptogateway.logging.channel', 'stack');

            $this->container->make('log')
                ->channel($channel)
                ->error("[CryptoGateway] Driver [{$alias}] {$method} failed: {$e->getMessage()}", [
                    'driver'    => $alias,
                    'method'    => $method,
                    'exception' => $e,
                ]);
        }
    }
}
