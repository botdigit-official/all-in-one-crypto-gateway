<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Unit\Drivers;

use Botdigit\CryptoGateway\Drivers\EthereumDriver;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Botdigit\CryptoGateway\Tests\TestCase;

class EthereumDriverTest extends TestCase
{
    public function test_driver_instance_is_created(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertInstanceOf(EthereumDriver::class, $driver);
    }

    public function test_coin_symbol_is_eth(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertEquals('ETH', $driver->getCoinSymbol());
    }

    public function test_decimals_is_18(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertEquals(18, $driver->getDecimals());
    }

    public function test_validate_address_accepts_valid(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertTrue($driver->validateAddress('0x742d35Cc6634C0532925a3b844Bc9e7595f2bD68'));
    }

    public function test_validate_address_rejects_invalid(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertFalse($driver->validateAddress('not-an-eth-address'));
        $this->assertFalse($driver->validateAddress('0x123'));  // Too short
        $this->assertFalse($driver->validateAddress(''));
    }

    public function test_validate_address_case_insensitive(): void
    {
        $driver = CryptoGateway::driver('eth');
        $this->assertTrue($driver->validateAddress('0x742d35cc6634c0532925a3b844bc9e7595f2bd68'));
        $this->assertTrue($driver->validateAddress('0x742D35CC6634C0532925A3B844BC9E7595F2BD68'));
    }

    public function test_magic_method_shortcut(): void
    {
        $driver = CryptoGateway::eth();
        $this->assertInstanceOf(EthereumDriver::class, $driver);
    }
}
