<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Tests\Unit\Security;

use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;
use Botdigit\CryptoGateway\Security\AddressValidator;
use Botdigit\CryptoGateway\Tests\TestCase;

class AddressValidatorTest extends TestCase
{
    // ── Bitcoin ──────────────────────────────────────────────────────────

    public function test_btc_legacy_address(): void
    {
        $this->assertTrue(AddressValidator::validate('BTC', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'));
    }

    public function test_btc_p2sh_address(): void
    {
        $this->assertTrue(AddressValidator::validate('BTC', '3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy'));
    }

    public function test_btc_bech32_address(): void
    {
        $this->assertTrue(AddressValidator::validate('BTC', 'bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4'));
    }

    public function test_btc_rejects_invalid(): void
    {
        $this->assertFalse(AddressValidator::validate('BTC', 'invalid'));
        $this->assertFalse(AddressValidator::validate('BTC', ''));
    }

    // ── Ethereum ────────────────────────────────────────────────────────

    public function test_eth_standard_address(): void
    {
        $this->assertTrue(AddressValidator::validate('ETH', '0x742d35Cc6634C0532925a3b844Bc9e7595f2bD68'));
    }

    public function test_eth_rejects_invalid(): void
    {
        $this->assertFalse(AddressValidator::validate('ETH', '0x123'));
        $this->assertFalse(AddressValidator::validate('ETH', 'not-eth'));
    }

    // ── TRON ────────────────────────────────────────────────────────────

    public function test_trx_address(): void
    {
        $this->assertTrue(AddressValidator::validate('TRX', 'TJCnKsPa7y5okkXvQAidZBzqx3QyQ6sxMW'));
    }

    public function test_trx_rejects_invalid(): void
    {
        $this->assertFalse(AddressValidator::validate('TRX', 'not-tron'));
    }

    // ── Solana ───────────────────────────────────────────────────────────

    public function test_sol_address(): void
    {
        $this->assertTrue(AddressValidator::validate('SOL', '7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU'));
    }

    // ── Validate or Fail ────────────────────────────────────────────────

    public function test_validate_or_fail_passes_valid(): void
    {
        AddressValidator::validateOrFail('ETH', '0x742d35Cc6634C0532925a3b844Bc9e7595f2bD68');
        $this->assertTrue(true); // No exception = pass
    }

    public function test_validate_or_fail_throws_invalid(): void
    {
        $this->expectException(InvalidAddressException::class);
        AddressValidator::validateOrFail('BTC', 'invalid');
    }

    // ── Custom Registration ─────────────────────────────────────────────

    public function test_register_custom_pattern(): void
    {
        AddressValidator::registerPattern('CUSTOM', '/^CUSTOM[0-9]{10}$/');
        $this->assertTrue(AddressValidator::validate('CUSTOM', 'CUSTOM1234567890'));
        $this->assertFalse(AddressValidator::validate('CUSTOM', 'invalid'));
    }

    // ── Unknown Coins ───────────────────────────────────────────────────

    public function test_unknown_coin_allows_any_nonempty(): void
    {
        $this->assertTrue(AddressValidator::validate('UNKNOWN_COIN_XYZ', 'anyaddress'));
        $this->assertFalse(AddressValidator::validate('UNKNOWN_COIN_XYZ', ''));
    }
}
