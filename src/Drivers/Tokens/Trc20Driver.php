<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers\Tokens;

use Botdigit\CryptoGateway\Drivers\AbstractDriver;
use Botdigit\CryptoGateway\Drivers\TronDriver;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Illuminate\Contracts\Foundation\Application;

/**
 * TRC-20 token driver (e.g., USDT on TRON).
 *
 * Delegates to the parent TRON driver for API communication,
 * but targets a specific token contract address.
 */
class Trc20Driver extends AbstractDriver
{
    protected TronDriver $parentDriver;
    protected string $contractAddress;
    protected int $tokenDecimals;
    protected string $tokenSymbol;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $this->contractAddress = $config['contract_address'] ?? '';
        $this->tokenDecimals   = (int) ($config['decimals'] ?? 6);
        $this->tokenSymbol     = $config['symbol'] ?? 'TOKEN';

        // Resolve the parent TRON driver
        $parentAlias = $config['parent_driver'] ?? 'trx';
        $this->parentDriver = CryptoGateway::driver($parentAlias);
    }

    public function getCoinSymbol(): string
    {
        return $this->tokenSymbol;
    }

    public function getDecimals(): int
    {
        return $this->tokenDecimals;
    }

    public function generateAddress(?string $label = null): AddressResult
    {
        // TRC-20 tokens use the same addresses as TRX
        return $this->parentDriver->generateAddress($label);
    }

    public function validateAddress(string $address): bool
    {
        return $this->parentDriver->validateAddress($address);
    }

    public function getBalance(string $address): BalanceResult
    {
        return $this->parentDriver->getTokenBalance($address, $this->contractAddress);
    }

    public function getTransaction(string $txHash): TransactionResult
    {
        return $this->parentDriver->getTransaction($txHash);
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        return $this->parentDriver->getTransactions($address, $limit, $offset);
    }

    public function send(string $to, string $amount, array $options = []): SendResult
    {
        $result = $this->parentDriver->sendToken($this->contractAddress, $to, $amount, $options);

        return new SendResult(
            coin: $this->getCoinSymbol(),
            txHash: $result['transaction']['txID'] ?? '',
            fromAddress: $options['from'] ?? '',
            toAddress: $to,
            amount: $amount,
            fee: '0',
            status: 'unsigned',
            raw: is_array($result) ? $result : [],
        );
    }

    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult
    {
        // TRC-20 transfers consume energy, which may cost TRX if not staked
        // Typical energy cost: ~30,000-65,000 energy = ~13-28 TRX
        return new EstimateFeeResult(
            coin: 'TRX',
            slow: '13.0',
            medium: '20.0',
            fast: '28.0',
            unit: 'TRX (energy)',
        );
    }

    public function isConnected(): bool
    {
        return $this->parentDriver->isConnected();
    }
}
