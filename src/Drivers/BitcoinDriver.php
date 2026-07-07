<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Adapters\JsonRpcAdapter;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Bitcoin driver — communicates with bitcoind via JSON-RPC.
 *
 * Supports both self-hosted nodes and third-party providers.
 */
class BitcoinDriver extends AbstractDriver
{
    protected JsonRpcAdapter $rpc;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $this->rpc = new JsonRpcAdapter(
            host: $this->getConfig('host', 'http://127.0.0.1:8332'),
            user: $this->getConfig('user'),
            password: $this->getConfig('password'),
        );
    }

    // ── Identity ────────────────────────────────────────────────────────

    public function getCoinSymbol(): string
    {
        return 'BTC';
    }

    public function getDecimals(): int
    {
        return 8;
    }

    // ── Wallet & Address ────────────────────────────────────────────────

    public function generateAddress(?string $label = null): AddressResult
    {
        return $this->withErrorHandling('generate address', function () use ($label) {
            $address = $this->rpc->call('getnewaddress', [$label ?? '']);

            $this->log('Generated new address', ['address' => $address, 'label' => $label]);

            return new AddressResult(
                coin: $this->getCoinSymbol(),
                address: $address,
                label: $label,
            );
        });
    }

    public function validateAddress(string $address): bool
    {
        try {
            $result = $this->rpc->call('validateaddress', [$address]);
            return $result['isvalid'] ?? false;
        } catch (\Throwable) {
            // Fallback: regex validation for common Bitcoin address formats
            return $this->validateAddressFormat($address);
        }
    }

    // ── Balance ─────────────────────────────────────────────────────────

    public function getBalance(string $address): BalanceResult
    {
        return $this->withErrorHandling('get balance', function () use ($address) {
            return $this->cached("balance:{$address}", function () use ($address) {
                // Try listunspent for specific address balance
                $unspent = $this->rpc->call('listunspent', [0, 9999999, [$address]]);

                $confirmed   = '0';
                $unconfirmed = '0';
                $minConf     = $this->getRequiredConfirmations();

                foreach ($unspent as $utxo) {
                    $amount = bcadd('0', (string) $utxo['amount'], 8);
                    if (($utxo['confirmations'] ?? 0) >= $minConf) {
                        $confirmed = bcadd($confirmed, $amount, 8);
                    } else {
                        $unconfirmed = bcadd($unconfirmed, $amount, 8);
                    }
                }

                $total = bcadd($confirmed, $unconfirmed, 8);

                return new BalanceResult(
                    coin: $this->getCoinSymbol(),
                    address: $address,
                    confirmed: $confirmed,
                    unconfirmed: $unconfirmed,
                    total: $total,
                    decimals: $this->getDecimals(),
                );
            });
        });
    }

    // ── Transactions ────────────────────────────────────────────────────

    public function getTransaction(string $txHash): TransactionResult
    {
        return $this->withErrorHandling('get transaction', function () use ($txHash) {
            $tx = $this->rpc->call('gettransaction', [$txHash]);

            $details = $tx['details'][0] ?? [];

            return new TransactionResult(
                coin: $this->getCoinSymbol(),
                txHash: $tx['txid'],
                fromAddress: $details['address'] ?? '',
                toAddress: $details['address'] ?? '',
                amount: (string) abs($tx['amount']),
                fee: (string) abs($tx['fee'] ?? 0),
                confirmations: $tx['confirmations'] ?? 0,
                status: ($tx['confirmations'] ?? 0) > 0 ? 'confirmed' : 'pending',
                direction: ($tx['amount'] ?? 0) >= 0 ? 'incoming' : 'outgoing',
                blockNumber: null,
                blockHash: $tx['blockhash'] ?? null,
                timestamp: $tx['time'] ?? null,
                raw: $tx,
            );
        });
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        return $this->withErrorHandling('get transactions', function () use ($address, $limit, $offset) {
            $txs = $this->rpc->call('listtransactions', ['*', $limit, $offset]);

            $results = [];
            foreach ($txs as $tx) {
                if (($tx['address'] ?? '') === $address) {
                    $results[] = new TransactionResult(
                        coin: $this->getCoinSymbol(),
                        txHash: $tx['txid'],
                        fromAddress: $tx['address'] ?? '',
                        toAddress: $tx['address'] ?? '',
                        amount: (string) abs($tx['amount']),
                        fee: (string) abs($tx['fee'] ?? 0),
                        confirmations: $tx['confirmations'] ?? 0,
                        status: ($tx['confirmations'] ?? 0) > 0 ? 'confirmed' : 'pending',
                        direction: $tx['category'] === 'receive' ? 'incoming' : 'outgoing',
                        blockNumber: null,
                        blockHash: $tx['blockhash'] ?? null,
                        timestamp: $tx['time'] ?? null,
                        raw: $tx,
                    );
                }
            }

            return $results;
        });
    }

    // ── Sending ─────────────────────────────────────────────────────────

    public function send(string $to, string $amount, array $options = []): SendResult
    {
        return $this->withErrorHandling('send', function () use ($to, $amount, $options) {
            if (! $this->validateAddress($to)) {
                throw new InvalidAddressException("Invalid Bitcoin address: {$to}");
            }

            $this->log('Sending BTC', ['to' => $to, 'amount' => $amount]);

            $txid = $this->rpc->call('sendtoaddress', [
                $to,
                (float) $amount,
                $options['comment'] ?? '',      // comment
                $options['comment_to'] ?? '',    // comment_to
                $options['subtractfee'] ?? false, // subtractfeefromamount
            ]);

            $this->clearCache("balance:{$to}");

            return new SendResult(
                coin: $this->getCoinSymbol(),
                txHash: $txid,
                fromAddress: '',  // Bitcoin doesn't expose sender in sendtoaddress
                toAddress: $to,
                amount: $amount,
                fee: '0',  // Fee is determined by the node
                status: 'broadcast',
            );
        });
    }

    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult
    {
        return $this->withErrorHandling('estimate fee', function () {
            // Estimate for 1, 6, and 25 blocks (fast, medium, slow)
            $fast   = $this->rpc->call('estimatesmartfee', [1]);
            $medium = $this->rpc->call('estimatesmartfee', [6]);
            $slow   = $this->rpc->call('estimatesmartfee', [25]);

            return new EstimateFeeResult(
                coin: $this->getCoinSymbol(),
                slow: (string) ($slow['feerate'] ?? '0.00001'),
                medium: (string) ($medium['feerate'] ?? '0.0001'),
                fast: (string) ($fast['feerate'] ?? '0.001'),
                unit: 'BTC/kB',
            );
        });
    }

    // ── Connection ──────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->rpc->isConnected();
    }

    // ── Private Helpers ─────────────────────────────────────────────────

    /**
     * Basic regex validation for Bitcoin address formats.
     */
    protected function validateAddressFormat(string $address): bool
    {
        // Legacy (1...), P2SH (3...), Bech32 (bc1...)
        return (bool) preg_match('/^(1[a-km-zA-HJ-NP-Z1-9]{25,34}|3[a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[a-z0-9]{8,87})$/', $address);
    }
}
