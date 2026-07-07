<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Adapters\SolanaRpcAdapter;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Solana driver — communicates via Solana JSON-RPC.
 */
class SolanaDriver extends AbstractDriver
{
    protected SolanaRpcAdapter $rpc;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $this->rpc = new SolanaRpcAdapter(
            rpcUrl: $this->getConfig('rpc_url', 'https://api.mainnet-beta.solana.com'),
        );
    }

    public function getCoinSymbol(): string
    {
        return 'SOL';
    }

    public function getDecimals(): int
    {
        return 9;
    }

    public function generateAddress(?string $label = null): AddressResult
    {
        return $this->withErrorHandling('generate address', function () use ($label) {
            // Solana uses ed25519 key pairs — generation requires a crypto library
            // For now, return a placeholder that indicates client-side generation is needed
            $this->log('Solana address generation requires ed25519 keypair library');

            return new AddressResult(
                coin: $this->getCoinSymbol(),
                address: '',
                label: $label,
                raw: ['note' => 'Use Solana CLI or web3.js to generate keypairs'],
            );
        });
    }

    public function validateAddress(string $address): bool
    {
        // Solana addresses are base58-encoded ed25519 public keys (32-44 chars)
        return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
    }

    public function getBalance(string $address): BalanceResult
    {
        return $this->withErrorHandling('get balance', function () use ($address) {
            return $this->cached("balance:{$address}", function () use ($address) {
                $result      = $this->rpc->getBalance($address);
                $lamports    = (string) ($result['value'] ?? 0);
                $sol         = SolanaRpcAdapter::lamportsToSol($lamports);

                return new BalanceResult(
                    coin: $this->getCoinSymbol(),
                    address: $address,
                    confirmed: $sol,
                    unconfirmed: '0',
                    total: $sol,
                    decimals: $this->getDecimals(),
                    raw: $result,
                );
            });
        });
    }

    public function getTransaction(string $txHash): TransactionResult
    {
        return $this->withErrorHandling('get transaction', function () use ($txHash) {
            $tx = $this->rpc->getTransaction($txHash);

            if (! $tx) {
                throw new \RuntimeException("Transaction not found: {$txHash}");
            }

            $meta = $tx['meta'] ?? [];
            $slot = $tx['slot'] ?? null;

            // Calculate amount from pre/post balance differences
            $preBalances  = $meta['preBalances'] ?? [];
            $postBalances = $meta['postBalances'] ?? [];

            $amount = '0';
            $fee    = SolanaRpcAdapter::lamportsToSol((string) ($meta['fee'] ?? 0));

            if (count($preBalances) >= 2 && count($postBalances) >= 2) {
                $senderDiff = $preBalances[0] - $postBalances[0] - ($meta['fee'] ?? 0);
                $amount     = SolanaRpcAdapter::lamportsToSol((string) abs($senderDiff));
            }

            $status = ($meta['err'] ?? null) === null ? 'confirmed' : 'failed';

            $accountKeys = $tx['transaction']['message']['accountKeys'] ?? [];
            $from = $accountKeys[0] ?? '';
            $to   = $accountKeys[1] ?? '';

            // If accountKeys are objects (parsed format)
            if (is_array($from)) {
                $from = $from['pubkey'] ?? '';
            }
            if (is_array($to)) {
                $to = $to['pubkey'] ?? '';
            }

            return new TransactionResult(
                coin: $this->getCoinSymbol(),
                txHash: $txHash,
                fromAddress: $from,
                toAddress: $to,
                amount: $amount,
                fee: $fee,
                confirmations: $slot ? 1 : 0,
                status: $status,
                direction: 'unknown',
                blockNumber: $slot,
                blockHash: null,
                timestamp: $tx['blockTime'] ?? null,
                raw: $tx,
            );
        });
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        return $this->withErrorHandling('get transactions', function () use ($address, $limit) {
            $signatures = $this->rpc->getSignaturesForAddress($address, $limit);
            $results    = [];

            foreach ($signatures as $sig) {
                $status = ($sig['err'] ?? null) === null ? 'confirmed' : 'failed';

                $results[] = new TransactionResult(
                    coin: $this->getCoinSymbol(),
                    txHash: $sig['signature'],
                    fromAddress: '',
                    toAddress: $address,
                    amount: '0',
                    fee: '0',
                    confirmations: 1,
                    status: $status,
                    direction: 'unknown',
                    blockNumber: $sig['slot'] ?? null,
                    blockHash: null,
                    timestamp: $sig['blockTime'] ?? null,
                    raw: $sig,
                );
            }

            return $results;
        });
    }

    public function send(string $to, string $amount, array $options = []): SendResult
    {
        return $this->withErrorHandling('send', function () use ($to, $amount, $options) {
            if (! $this->validateAddress($to)) {
                throw new InvalidAddressException("Invalid Solana address: {$to}");
            }

            // Solana transactions must be signed client-side
            if (isset($options['signedTx'])) {
                $signature = $this->rpc->sendTransaction($options['signedTx']);

                return new SendResult(
                    coin: $this->getCoinSymbol(),
                    txHash: $signature,
                    fromAddress: $options['from'] ?? '',
                    toAddress: $to,
                    amount: $amount,
                    fee: '0',
                    status: 'broadcast',
                );
            }

            // Return info for client-side signing
            $blockhash = $this->rpc->getLatestBlockhash();

            return new SendResult(
                coin: $this->getCoinSymbol(),
                txHash: '',
                fromAddress: $options['from'] ?? '',
                toAddress: $to,
                amount: $amount,
                fee: '0',
                status: 'unsigned',
                raw: [
                    'to'               => $to,
                    'lamports'         => SolanaRpcAdapter::solToLamports($amount),
                    'recentBlockhash'  => $blockhash['value']['blockhash'] ?? '',
                ],
            );
        });
    }

    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult
    {
        return $this->withErrorHandling('estimate fee', function () {
            // Solana has a base fee of 5000 lamports per signature
            $baseFee = SolanaRpcAdapter::lamportsToSol('5000');

            return EstimateFeeResult::flat($this->getCoinSymbol(), $baseFee, 'SOL');
        });
    }

    public function isConnected(): bool
    {
        return $this->rpc->isConnected();
    }
}
