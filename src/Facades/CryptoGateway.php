<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Facades;

use Botdigit\CryptoGateway\Contracts\DriverInterface;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\GatewayManager;
use Illuminate\Support\Facades\Facade;

/**
 * CryptoGateway Facade.
 *
 * @method static DriverInterface driver(string $driver)
 * @method static DriverInterface btc()
 * @method static DriverInterface eth()
 * @method static DriverInterface trx()
 * @method static DriverInterface sol()
 * @method static DriverInterface ltc()
 * @method static DriverInterface bnb()
 * @method static BalanceResult[] allBalances(string $address)
 * @method static array healthCheck()
 * @method static array configuredDrivers()
 * @method static array supportedCoins()
 * @method static bool isSupported(string $coin)
 * @method static bool hasDriver(string $alias)
 * @method static void extend(string $driver, \Closure $callback)
 *
 * @see GatewayManager
 */
class CryptoGateway extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cryptogateway';
    }
}
