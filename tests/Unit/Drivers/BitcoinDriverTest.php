<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Unit\Drivers;

use Botdigit\CryptoGateway\Drivers\BitcoinDriver;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Botdigit\CryptoGateway\Tests\TestCase;

class BitcoinDriverTest extends TestCase
{
    public function test_driver_instance_is_created(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertInstanceOf(BitcoinDriver::class, $driver);
    }

    public function test_coin_symbol_is_btc(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertEquals('BTC', $driver->getCoinSymbol());
    }

    public function test_decimals_is_8(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertEquals(8, $driver->getDecimals());
    }

    public function test_network_is_testnet(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertEquals('testnet', $driver->getNetwork());
    }

    public function test_validate_address_accepts_valid_legacy(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertTrue($driver->validateAddress('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'));
    }

    public function test_validate_address_accepts_valid_segwit(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertTrue($driver->validateAddress('bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4'));
    }

    public function test_validate_address_rejects_invalid(): void
    {
        $driver = CryptoGateway::driver('btc');
        $this->assertFalse($driver->validateAddress('not-a-valid-address'));
        $this->assertFalse($driver->validateAddress(''));
    }

    public function test_magic_method_shortcut(): void
    {
        $driver = CryptoGateway::btc();
        $this->assertInstanceOf(BitcoinDriver::class, $driver);
    }
}
