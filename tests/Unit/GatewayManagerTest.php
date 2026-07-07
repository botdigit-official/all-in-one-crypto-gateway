<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Unit;

use Botdigit\CryptoGateway\Exceptions\DriverNotFoundException;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Botdigit\CryptoGateway\GatewayManager;
use Botdigit\CryptoGateway\Tests\TestCase;

class GatewayManagerTest extends TestCase
{
    public function test_manager_is_registered_as_singleton(): void
    {
        $manager = app('cryptogateway');
        $this->assertInstanceOf(GatewayManager::class, $manager);
        $this->assertSame($manager, app('cryptogateway'));
    }

    public function test_facade_resolves_to_manager(): void
    {
        $this->assertInstanceOf(GatewayManager::class, CryptoGateway::getFacadeRoot());
    }

    public function test_configured_drivers_returns_all_configured(): void
    {
        $drivers = CryptoGateway::configuredDrivers();
        $this->assertIsArray($drivers);
        $this->assertContains('btc', $drivers);
        $this->assertContains('eth', $drivers);
        $this->assertContains('trx', $drivers);
    }

    public function test_has_driver_returns_true_for_configured(): void
    {
        $this->assertTrue(CryptoGateway::hasDriver('btc'));
        $this->assertTrue(CryptoGateway::hasDriver('eth'));
    }

    public function test_has_driver_returns_false_for_unconfigured(): void
    {
        $this->assertFalse(CryptoGateway::hasDriver('nonexistent'));
    }

    public function test_supported_coins_returns_builtin_list(): void
    {
        $coins = CryptoGateway::supportedCoins();
        $this->assertContains('bitcoin', $coins);
        $this->assertContains('ethereum', $coins);
        $this->assertContains('tron', $coins);
        $this->assertContains('solana', $coins);
        $this->assertContains('litecoin', $coins);
    }

    public function test_is_supported_for_builtin_coins(): void
    {
        $this->assertTrue(CryptoGateway::isSupported('bitcoin'));
        $this->assertTrue(CryptoGateway::isSupported('btc')); // Configured alias
    }

    public function test_driver_throws_for_unknown(): void
    {
        $this->expectException(DriverNotFoundException::class);
        CryptoGateway::driver('nonexistent_coin_xyz');
    }

    public function test_default_driver_is_btc(): void
    {
        $manager = app('cryptogateway');
        $this->assertEquals('btc', $manager->getDefaultDriver());
    }

    public function test_health_check_returns_array(): void
    {
        $results = CryptoGateway::healthCheck();
        $this->assertIsArray($results);
        // All drivers should report false since we have no live nodes in tests
        foreach ($results as $status) {
            $this->assertIsBool($status);
        }
    }
}
