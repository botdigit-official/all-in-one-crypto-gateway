<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Adapters\TronHttpAdapter;
use Botdigit\CryptoGateway\Contracts\HasTokens;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;
use Illuminate\Contracts\Foundation\Application;

/**
 * TRON driver — communicates via TronGrid / Full Node HTTP API.
 */
class TronDriver extends AbstractDriver implements HasTokens
{
    protected TronHttpAdapter $api;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $this->api = new TronHttpAdapter(
            apiUrl: $this->getConfig('api_url', 'https://api.trongrid.io'),
            apiKey: $this->getConfig('api_key'),
        );
    }

    public function getCoinSymbol(): string
    {
        return 'TRX';
    }

    public function getDecimals(): int
    {
        return 6;
    }

    public function generateAddress(?string $label = null): AddressResult
    {
        return $this->withErrorHandling('generate address', function () use ($label) {
            $result = $this->api->post('/wallet/generateaddress');

            return new AddressResult(
                coin: $this->getCoinSymbol(),
                address: $result['base58check_address'] ?? $result['address'] ?? '',
                privateKey: $result['private_key'] ?? null,
                label: $label,
                raw: $result,
            );
        });
    }

    public function validateAddress(string $address): bool
    {
        try {
            $result = $this->api->validateAddress($address);
            return ($result['result'] ?? false) === true;
        } catch (\Throwable) {
            return (bool) preg_match('/^T[a-km-zA-HJ-NP-Z1-9]{33}$/', $address);
        }
    }

    public function getBalance(string $address): BalanceResult
    {
        return $this->withErrorHandling('get balance', function () use ($address) {
            return $this->cached("balance:{$address}", function () use ($address) {
                $account = $this->api->getAccount($address);

                $balanceSun = (string) ($account['balance'] ?? 0);
                $balanceTrx = TronHttpAdapter::sunToTrx($balanceSun);

                return new BalanceResult(
                    coin: $this->getCoinSymbol(),
                    address: $address,
                    confirmed: $balanceTrx,
                    unconfirmed: '0',
                    total: $balanceTrx,
                    decimals: $this->getDecimals(),
                    raw: $account,
                );
            });
        });
    }

    public function getTransaction(string $txHash): TransactionResult
    {
        return $this->withErrorHandling('get transaction', function () use ($txHash) {
            $tx   = $this->api->getTransactionById($txHash);
            $info = $this->api->getTransactionInfoById($txHash);

            $contract = $tx['raw_data']['contract'][0] ?? [];
            $value    = $contract['parameter']['value'] ?? [];

            $amount = TronHttpAdapter::sunToTrx((string) ($value['amount'] ?? 0));
            $fee    = TronHttpAdapter::sunToTrx((string) ($info['fee'] ?? 0));

            $status = 'pending';
            if (isset($info['receipt']['result'])) {
                $status = $info['receipt']['result'] === 'SUCCESS' ? 'confirmed' : 'failed';
            } elseif (isset($info['blockNumber'])) {
                $status = 'confirmed';
            }

            return new TransactionResult(
                coin: $this->getCoinSymbol(),
                txHash: $tx['txID'] ?? $txHash,
                fromAddress: $value['owner_address'] ?? '',
                toAddress: $value['to_address'] ?? '',
                amount: $amount,
                fee: $fee,
                confirmations: isset($info['blockNumber']) ? 1 : 0,
                status: $status,
                direction: 'unknown',
                blockNumber: $info['blockNumber'] ?? null,
                blockHash: null,
                timestamp: isset($info['blockTimeStamp']) ? (int) ($info['blockTimeStamp'] / 1000) : null,
                raw: $tx,
            );
        });
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        $this->log('getTransactions for TRON requires TronGrid API v1 indexer');
        return [];
    }

    public function send(string $to, string $amount, array $options = []): SendResult
    {
        return $this->withErrorHandling('send', function () use ($to, $amount, $options) {
            if (! $this->validateAddress($to)) {
                throw new InvalidAddressException("Invalid TRON address: {$to}");
            }

            $from      = $options['from'] ?? '';
            $amountSun = (int) TronHttpAdapter::trxToSun($amount);

            $tx = $this->api->createTransaction($from, $to, $amountSun);

            // The transaction needs to be signed client-side
            return new SendResult(
                coin: $this->getCoinSymbol(),
                txHash: $tx['txID'] ?? '',
                fromAddress: $from,
                toAddress: $to,
                amount: $amount,
                fee: '0',
                status: isset($tx['txID']) ? 'unsigned' : 'failed',
                raw: $tx,
            );
        });
    }

    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult
    {
        // TRON uses bandwidth/energy, not traditional gas fees for TRX transfers
        return EstimateFeeResult::flat($this->getCoinSymbol(), '0', 'bandwidth');
    }

    public function isConnected(): bool
    {
        return $this->api->isConnected();
    }

    // ── HasTokens ───────────────────────────────────────────────────────

    public function getTokenBalance(string $address, string $contractAddress): BalanceResult
    {
        return $this->withErrorHandling('get token balance', function () use ($address, $contractAddress) {
            // balanceOf(address) — pad address to 32 bytes
            $addressHex = str_pad(substr($address, 1), 64, '0', STR_PAD_LEFT);

            $result = $this->api->triggerConstantContract(
                $contractAddress,
                'balanceOf(address)',
                $addressHex,
                $address,
            );

            $hex     = $result['constant_result'][0] ?? '0';
            $raw     = hexdec($hex) ?: 0;
            $decimals = $this->getTokenDecimals($contractAddress);
            $balance  = bcdiv((string) $raw, bcpow('10', (string) $decimals), $decimals);

            return new BalanceResult(
                coin: $this->getTokenSymbol($contractAddress),
                address: $address,
                confirmed: $balance,
                unconfirmed: '0',
                total: $balance,
                decimals: $decimals,
            );
        });
    }

    public function sendToken(string $contractAddress, string $to, string $amount, array $options = []): mixed
    {
        return $this->withErrorHandling('send token', function () use ($contractAddress, $to, $amount, $options) {
            $decimals  = $this->getTokenDecimals($contractAddress);
            $amountRaw = bcmul($amount, bcpow('10', (string) $decimals), 0);
            $amountHex = str_pad(dechex((int) $amountRaw), 64, '0', STR_PAD_LEFT);

            $addressHex = str_pad(substr($to, 1), 64, '0', STR_PAD_LEFT);
            $parameter  = $addressHex . $amountHex;

            $from = $options['from'] ?? $to;

            return $this->api->triggerSmartContract(
                $contractAddress,
                'transfer(address,uint256)',
                $parameter,
                $from,
            );
        });
    }

    public function getTokenDecimals(string $contractAddress): int
    {
        return $this->cached("trc20_decimals:{$contractAddress}", function () use ($contractAddress) {
            $result = $this->api->triggerConstantContract(
                $contractAddress,
                'decimals()',
                '',
                $contractAddress,
            );

            return (int) hexdec($result['constant_result'][0] ?? '6');
        }, 86400);
    }

    public function getTokenSymbol(string $contractAddress): string
    {
        return $this->cached("trc20_symbol:{$contractAddress}", function () use ($contractAddress) {
            $result = $this->api->triggerConstantContract(
                $contractAddress,
                'symbol()',
                '',
                $contractAddress,
            );

            $hex = $result['constant_result'][0] ?? '';
            if (strlen($hex) >= 128) {
                $length    = (int) hexdec(substr($hex, 64, 64));
                $stringHex = substr($hex, 128, $length * 2);
                return trim(hex2bin($stringHex));
            }

            return 'UNKNOWN';
        }, 86400);
    }

    public function getAdapter(): TronHttpAdapter
    {
        return $this->api;
    }
}
