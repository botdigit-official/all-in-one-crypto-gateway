<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers\Tokens;

use Botdigit\CryptoGateway\Drivers\AbstractDriver;
use Botdigit\CryptoGateway\Drivers\EthereumDriver;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Illuminate\Contracts\Foundation\Application;

/**
 * ERC-20 token driver (e.g., USDT on Ethereum, USDC on Ethereum).
 *
 * Delegates to the parent Ethereum driver for RPC communication,
 * but targets a specific token contract address.
 */
class Erc20Driver extends AbstractDriver
{
    protected EthereumDriver $parentDriver;
    protected string $contractAddress;
    protected int $tokenDecimals;
    protected string $tokenSymbol;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $this->contractAddress = $config['contract_address'] ?? '';
        $this->tokenDecimals   = (int) ($config['decimals'] ?? 18);
        $this->tokenSymbol     = $config['symbol'] ?? 'TOKEN';

        // Resolve the parent Ethereum driver
        $parentAlias = $config['parent_driver'] ?? 'eth';
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
        // ERC-20 tokens use the same addresses as ETH
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
        // Token transactions are standard ETH transactions to the contract
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
            txHash: '',
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
        // ERC-20 transfers cost more gas than simple ETH transfers (~65000 vs 21000)
        $ethFee = $this->parentDriver->estimateFee($to, $amount);

        // Roughly 3x the ETH transfer cost
        $multiplier = '3';
        return new EstimateFeeResult(
            coin: 'ETH', // Gas is paid in ETH
            slow: bcmul($ethFee->slow, $multiplier, 18),
            medium: bcmul($ethFee->medium, $multiplier, 18),
            fast: bcmul($ethFee->fast, $multiplier, 18),
            unit: 'ETH',
        );
    }

    public function isConnected(): bool
    {
        return $this->parentDriver->isConnected();
    }
}
