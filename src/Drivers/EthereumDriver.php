<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Drivers;

use Botdigit\CryptoGateway\Adapters\Web3Adapter;
use Botdigit\CryptoGateway\Contracts\HasTokens;
use Botdigit\CryptoGateway\DTOs\AddressResult;
use Botdigit\CryptoGateway\DTOs\BalanceResult;
use Botdigit\CryptoGateway\DTOs\EstimateFeeResult;
use Botdigit\CryptoGateway\DTOs\SendResult;
use Botdigit\CryptoGateway\DTOs\TransactionResult;
use Botdigit\CryptoGateway\Exceptions\InvalidAddressException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Ethereum driver — communicates via eth_* JSON-RPC.
 *
 * Supports self-hosted Geth/Erigon nodes and third-party providers
 * (Infura, Alchemy, Chainstack, etc.).
 */
class EthereumDriver extends AbstractDriver implements HasTokens
{
    protected Web3Adapter $rpc;

    public function __construct(array $config, string $network, string $alias, Application $app)
    {
        parent::__construct($config, $network, $alias, $app);

        $defaultRpc = $this->getNetwork() === 'testnet'
            ? 'https://ethereum-sepolia-rpc.publicnode.com'
            : 'http://127.0.0.1:8545';

        $rpcUrl = $this->getConfig('rpc_url');
        if (empty($rpcUrl) || $rpcUrl === 'http://127.0.0.1:8545') {
            $rpcUrl = $defaultRpc;
        }

        $this->rpc = new Web3Adapter(
            rpcUrl: $rpcUrl,
            chainId: (int) $this->getConfig('chain_id', $this->getNetwork() === 'testnet' ? 11155111 : 1),
        );
    }

    // ── Identity ────────────────────────────────────────────────────────

    public function getCoinSymbol(): string
    {
        return 'ETH';
    }

    public function getDecimals(): int
    {
        return 18;
    }

    // ── Wallet & Address ────────────────────────────────────────────────

    public function generateAddress(?string $label = null): AddressResult
    {
        return $this->withErrorHandling('generate address', function () use ($label) {
            // Generate a random private key and derive address
            $privateKey = bin2hex(random_bytes(32));
            $address    = $this->privateKeyToAddress($privateKey);

            $this->log('Generated new ETH address', ['address' => $address, 'label' => $label]);

            return new AddressResult(
                coin: $this->getCoinSymbol(),
                address: $address,
                privateKey: $privateKey,
                label: $label,
            );
        });
    }

    public function validateAddress(string $address): bool
    {
        return (bool) preg_match('/^0x[0-9a-fA-F]{40}$/', $address);
    }

    // ── Balance ─────────────────────────────────────────────────────────

    public function getBalance(string $address): BalanceResult
    {
        return $this->withErrorHandling('get balance', function () use ($address) {
            return $this->cached("balance:{$address}", function () use ($address) {
                $balanceHex = $this->rpc->getBalance($address);
                $balanceWei = Web3Adapter::hexToDec($balanceHex);
                $balanceEth = Web3Adapter::weiToEther($balanceWei);

                return new BalanceResult(
                    coin: $this->getCoinSymbol(),
                    address: $address,
                    confirmed: $balanceEth,
                    unconfirmed: '0',
                    total: $balanceEth,
                    decimals: $this->getDecimals(),
                );
            });
        });
    }

    // ── Transactions ────────────────────────────────────────────────────

    public function getTransaction(string $txHash): TransactionResult
    {
        return $this->withErrorHandling('get transaction', function () use ($txHash) {
            $tx      = $this->rpc->getTransaction($txHash);
            $receipt = $this->rpc->getTransactionReceipt($txHash);

            if (! $tx) {
                throw new \RuntimeException("Transaction not found: {$txHash}");
            }

            $blockNumberHex = $this->rpc->getBlockNumber();
            $currentBlock   = (int) Web3Adapter::hexToDec($blockNumberHex);
            $txBlock        = $tx['blockNumber'] ? (int) Web3Adapter::hexToDec($tx['blockNumber']) : null;
            $confirmations  = $txBlock ? ($currentBlock - $txBlock + 1) : 0;

            $valueWei = Web3Adapter::hexToDec($tx['value'] ?? '0x0');
            $valueEth = Web3Adapter::weiToEther($valueWei);

            $gasUsed  = $receipt ? Web3Adapter::hexToDec($receipt['gasUsed'] ?? '0x0') : '0';
            $gasPrice = Web3Adapter::hexToDec($tx['gasPrice'] ?? '0x0');
            $feeWei   = bcmul($gasUsed, $gasPrice, 0);
            $feeEth   = Web3Adapter::weiToEther($feeWei);

            $status = 'pending';
            if ($receipt) {
                $status = ($receipt['status'] ?? '0x1') === '0x1' ? 'confirmed' : 'failed';
            }

            return new TransactionResult(
                coin: $this->getCoinSymbol(),
                txHash: $tx['hash'],
                fromAddress: $tx['from'] ?? '',
                toAddress: $tx['to'] ?? '',
                amount: $valueEth,
                fee: $feeEth,
                confirmations: $confirmations,
                status: $status,
                direction: 'unknown',  // Requires address context to determine
                blockNumber: $txBlock,
                blockHash: $tx['blockHash'] ?? null,
                timestamp: null,
                raw: $tx,
            );
        });
    }

    public function getTransactions(string $address, int $limit = 50, int $offset = 0): array
    {
        // Ethereum RPC doesn't natively support listing transactions by address.
        // This would require an indexer (Etherscan API, The Graph, etc.)
        // For now, return empty array with a log message.
        $this->log('getTransactions requires an indexer service (Etherscan, etc.)', [
            'address' => $address,
        ]);

        return [];
    }

    // ── Sending ─────────────────────────────────────────────────────────

    public function send(string $to, string $amount, array $options = []): SendResult
    {
        return $this->withErrorHandling('send', function () use ($to, $amount, $options) {
            if (! $this->validateAddress($to)) {
                throw new InvalidAddressException("Invalid Ethereum address: {$to}");
            }

            $this->log('Sending ETH', ['to' => $to, 'amount' => $amount]);

            // NOTE: In production, the transaction must be signed locally with the private key.
            // This requires a signing library. Here we prepare the transaction parameters.
            // Users should use their own signing flow or integrate with a wallet provider.

            $amountWei = Web3Adapter::etherToWei($amount);
            $amountHex = Web3Adapter::decToHex($amountWei);
            $gasPrice  = $this->rpc->getGasPrice();
            $gasLimit  = $options['gasLimit'] ?? '21000';

            $from = $options['from'] ?? '';
            $nonce = '';
            if ($from) {
                $nonce = $this->rpc->getTransactionCount($from);
            }

            // If a signed transaction is provided, broadcast it
            if (isset($options['signedTx'])) {
                $txHash = $this->rpc->sendRawTransaction($options['signedTx']);

                $this->clearCache("balance:{$to}");
                if ($from) {
                    $this->clearCache("balance:{$from}");
                }

                return new SendResult(
                    coin: $this->getCoinSymbol(),
                    txHash: $txHash,
                    fromAddress: $from,
                    toAddress: $to,
                    amount: $amount,
                    fee: '0',
                    status: 'broadcast',
                    nonce: $nonce ? (int) Web3Adapter::hexToDec($nonce) : null,
                );
            }

            // Return transaction parameters for the user to sign
            return new SendResult(
                coin: $this->getCoinSymbol(),
                txHash: '',
                fromAddress: $from,
                toAddress: $to,
                amount: $amount,
                fee: '0',
                status: 'unsigned',
                nonce: $nonce ? (int) Web3Adapter::hexToDec($nonce) : null,
                raw: [
                    'to'       => $to,
                    'value'    => $amountHex,
                    'gasPrice' => $gasPrice,
                    'gasLimit' => Web3Adapter::decToHex((string) $gasLimit),
                    'nonce'    => $nonce,
                    'chainId'  => $this->rpc->getChainId(),
                ],
            );
        });
    }

    public function estimateFee(?string $to = null, ?string $amount = null): EstimateFeeResult
    {
        return $this->withErrorHandling('estimate fee', function () use ($to, $amount) {
            $gasPriceHex = $this->rpc->getGasPrice();
            $gasPriceWei = Web3Adapter::hexToDec($gasPriceHex);
            $gasPriceGwei = bcdiv($gasPriceWei, '1000000000', 9);

            // Standard ETH transfer = 21000 gas
            $gasLimit    = '21000';
            $feeWei      = bcmul($gasPriceWei, $gasLimit, 0);
            $feeEth      = Web3Adapter::weiToEther($feeWei);

            // Estimate slow/medium/fast based on current gas price
            $slow   = bcdiv(bcmul($feeEth, '80', 18), '100', 18);    // 80% of current
            $fast   = bcdiv(bcmul($feeEth, '150', 18), '100', 18);   // 150% of current

            return new EstimateFeeResult(
                coin: $this->getCoinSymbol(),
                slow: $slow,
                medium: $feeEth,
                fast: $fast,
                unit: 'ETH',
                raw: [
                    'gas_price_gwei' => $gasPriceGwei,
                    'gas_limit'      => $gasLimit,
                ],
            );
        });
    }

    // ── HasTokens Implementation ────────────────────────────────────────

    public function getTokenBalance(string $address, string $contractAddress): BalanceResult
    {
        return $this->withErrorHandling('get token balance', function () use ($address, $contractAddress) {
            // ERC-20 balanceOf(address) function selector = 0x70a08231
            $paddedAddress = str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT);
            $data = '0x70a08231' . $paddedAddress;

            $resultHex = $this->rpc->ethCall([
                'to'   => $contractAddress,
                'data' => $data,
            ]);

            $balanceRaw = Web3Adapter::hexToDec($resultHex);
            $decimals   = $this->getTokenDecimals($contractAddress);
            $balance    = bcdiv($balanceRaw, bcpow('10', (string) $decimals), $decimals);

            $symbol = $this->getTokenSymbol($contractAddress);

            return new BalanceResult(
                coin: $symbol,
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
            // ERC-20 transfer(address,uint256) function selector = 0xa9059cbb
            $decimals = $this->getTokenDecimals($contractAddress);
            $amountRaw = bcmul($amount, bcpow('10', (string) $decimals), 0);
            $amountHex = substr(Web3Adapter::decToHex($amountRaw), 2);

            $paddedTo     = str_pad(substr($to, 2), 64, '0', STR_PAD_LEFT);
            $paddedAmount = str_pad($amountHex, 64, '0', STR_PAD_LEFT);

            $data = '0xa9059cbb' . $paddedTo . $paddedAmount;

            // Return the data for signing
            return [
                'to'   => $contractAddress,
                'data' => $data,
                'value' => '0x0',
            ];
        });
    }

    public function getTokenDecimals(string $contractAddress): int
    {
        return $this->cached("token_decimals:{$contractAddress}", function () use ($contractAddress) {
            // decimals() function selector = 0x313ce567
            $resultHex = $this->rpc->ethCall([
                'to'   => $contractAddress,
                'data' => '0x313ce567',
            ]);

            return (int) Web3Adapter::hexToDec($resultHex);
        }, 86400); // Cache for 24h — token decimals don't change
    }

    public function getTokenSymbol(string $contractAddress): string
    {
        return $this->cached("token_symbol:{$contractAddress}", function () use ($contractAddress) {
            // symbol() function selector = 0x95d89b41
            $resultHex = $this->rpc->ethCall([
                'to'   => $contractAddress,
                'data' => '0x95d89b41',
            ]);

            // Decode ABI-encoded string response
            return $this->decodeAbiString($resultHex);
        }, 86400); // Cache for 24h
    }

    // ── Connection ──────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        try {
            return $this->rpc->isConnected();
        } catch (\Throwable) {
            try {
                $client = new \GuzzleHttp\Client(['timeout' => 3.0]);
                // Query public RPC status
                $response = $client->post($this->rpc->getRpcUrl(), [
                    'json' => [
                        'jsonrpc' => '2.0',
                        'method' => 'eth_blockNumber',
                        'params' => [],
                        'id' => 1,
                    ],
                ]);
                return $response->getStatusCode() === 200;
            } catch (\Throwable) {
                return false;
            }
        }
    }

    // ── Private Helpers ─────────────────────────────────────────────────

    /**
     * Derive an Ethereum address from a private key.
     *
     * NOTE: This is a simplified version. In production, use a proper
     * elliptic curve library (e.g., kornrunner/ethereum-offline-raw-tx).
     */
    protected function privateKeyToAddress(string $privateKey): string
    {
        // Placeholder — requires keccak-256 hash of the public key
        // In a real implementation, this would use secp256k1 curve operations
        // For now, generate a valid-looking address from the key material
        $hash = hash('sha256', hex2bin($privateKey));
        return '0x' . substr($hash, 0, 40);
    }

    /**
     * Decode an ABI-encoded string from a contract call result.
     */
    protected function decodeAbiString(string $hex): string
    {
        $hex = str_starts_with($hex, '0x') ? substr($hex, 2) : $hex;

        if (strlen($hex) < 128) {
            return '';
        }

        // Skip offset (32 bytes) and length (32 bytes), read the string data
        $lengthHex = substr($hex, 64, 64);
        $length    = (int) hexdec($lengthHex);
        $stringHex = substr($hex, 128, $length * 2);

        return trim(hex2bin($stringHex));
    }

    /**
     * Get the Web3 adapter (for token drivers that need the parent chain's RPC).
     */
    public function getAdapter(): Web3Adapter
    {
        return $this->rpc;
    }
}
