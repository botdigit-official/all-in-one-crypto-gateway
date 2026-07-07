<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Feature;

use Botdigit\CryptoGateway\Contracts\DriverInterface;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Botdigit\CryptoGateway\Tests\TestCase;

class FacadeTest extends TestCase
{
    public function test_facade_driver_method_returns_driver_interface(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertInstanceOf(DriverInterface::class, $driver);
    }

    public function test_facade_magic_method_for_btc(): void
    {
        $driver = CryptoGateway::btc();
        $this->assertInstanceOf(DriverInterface::class, $driver);
        $this->assertEquals('BTC', $driver->getCoinSymbol());
    }

    public function test_facade_magic_method_for_eth(): void
    {
        $driver = CryptoGateway::eth();
        $this->assertInstanceOf(DriverInterface::class, $driver);
        $this->assertEquals('ETH', $driver->getCoinSymbol());
    }

    public function test_facade_magic_method_for_trx(): void
    {
        $driver = CryptoGateway::trx();
        $this->assertInstanceOf(DriverInterface::class, $driver);
        $this->assertEquals('TRX', $driver->getCoinSymbol());
    }

    public function test_facade_magic_method_for_sol(): void
    {
        $driver = CryptoGateway::sol();
        $this->assertInstanceOf(DriverInterface::class, $driver);
        $this->assertEquals('SOL', $driver->getCoinSymbol());
    }

    public function test_facade_configured_drivers(): void
    {
        $drivers = CryptoGateway::configuredDrivers();
        $this->assertIsArray($drivers);
        $this->assertNotEmpty($drivers);
    }

    public function test_facade_extend_registers_custom_driver(): void
    {
        CryptoGateway::extend('custom_test', function ($app) {
            return new class([], 'testnet', 'custom_test', $app) extends \Botdigit\CryptoGateway\Drivers\AbstractDriver {
                public function getCoinSymbol(): string { return 'CUSTOM'; }
                public function getDecimals(): int { return 8; }
                public function generateAddress(?string $label = null): \Botdigit\CryptoGateway\DTOs\AddressResult {
                    return new \Botdigit\CryptoGateway\DTOs\AddressResult(coin: 'CUSTOM', address: 'test123');
                }
                public function validateAddress(string $address): bool { return true; }
                public function getBalance(string $address): \Botdigit\CryptoGateway\DTOs\BalanceResult {
                    return \Botdigit\CryptoGateway\DTOs\BalanceResult::fromConfirmed('CUSTOM', $address, '100');
                }
                public function getTransaction(string $txHash): \Botdigit\CryptoGateway\DTOs\TransactionResult {
                    throw new \RuntimeException('Not implemented');
                }
                public function getTransactions(string $address, int $limit = 50, int $offset = 0): array { return []; }
                public function send(string $to, string $amount, array $options = []): \Botdigit\CryptoGateway\DTOs\SendResult {
                    throw new \RuntimeException('Not implemented');
                }
                public function estimateFee(?string $to = null, ?string $amount = null): \Botdigit\CryptoGateway\DTOs\EstimateFeeResult {
                    return \Botdigit\CryptoGateway\DTOs\EstimateFeeResult::flat('CUSTOM', '0.001');
                }
                public function isConnected(): bool { return true; }
            };
        });

        // Register in config so hasDriver works
        config(['cryptogateway.drivers.custom_test' => ['driver' => 'custom_test']]);

        $driver = CryptoGateway::driver('custom_test');
        $this->assertEquals('CUSTOM', $driver->getCoinSymbol());
    }
}
