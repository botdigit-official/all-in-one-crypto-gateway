<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Console;

use Botdigit\CryptoGateway\Events\TransactionConfirmed;
use Botdigit\CryptoGateway\Events\TransactionReceived;
use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Botdigit\CryptoGateway\Models\CryptoTransaction;
use Botdigit\CryptoGateway\Models\CryptoWallet;
use Illuminate\Console\Command;

/**
 * Sync transactions from the blockchain for all managed wallets.
 *
 * Designed to run as a scheduled task (e.g., every 5 minutes via cron).
 */
class SyncTransactionsCommand extends Command
{
    protected $signature = 'cryptogateway:sync
                            {--coin= : Sync only a specific coin}
                            {--limit=50 : Max transactions to fetch per wallet}';

    protected $description = 'Sync transactions from blockchain for managed wallets';

    public function handle(): int
    {
        $coin  = $this->option('coin');
        $limit = (int) $this->option('limit');

        $query = CryptoWallet::active();

        if ($coin) {
            $query->forCoin($coin);
        }

        $wallets = $query->get();

        if ($wallets->isEmpty()) {
            $this->info('No active wallets found to sync.');
            return self::SUCCESS;
        }

        $this->info("Syncing transactions for {$wallets->count()} wallet(s)...");
        $bar = $this->output->createProgressBar($wallets->count());

        $newCount     = 0;
        $updatedCount = 0;

        foreach ($wallets as $wallet) {
            try {
                $driver = CryptoGateway::driver(strtolower($wallet->coin));
                $txs    = $driver->getTransactions($wallet->address, $limit);

                foreach ($txs as $txResult) {
                    $existing = CryptoTransaction::where('tx_hash', $txResult->txHash)->first();

                    if (! $existing) {
                        // New transaction
                        $transaction = CryptoTransaction::create([
                            'coin'          => $txResult->coin,
                            'tx_hash'       => $txResult->txHash,
                            'from_address'  => $txResult->fromAddress,
                            'to_address'    => $txResult->toAddress,
                            'amount'        => $txResult->amount,
                            'fee'           => $txResult->fee,
                            'confirmations' => $txResult->confirmations,
                            'status'        => $txResult->status,
                            'direction'     => $txResult->direction,
                            'block_number'  => $txResult->blockNumber,
                            'block_hash'    => $txResult->blockHash,
                            'raw_data'      => $txResult->raw,
                            'wallet_id'     => $wallet->id,
                        ]);

                        TransactionReceived::dispatch($transaction, $txResult->coin);
                        $newCount++;
                    } else {
                        // Update confirmations
                        $oldConfirmations = $existing->confirmations;
                        $existing->update([
                            'confirmations' => $txResult->confirmations,
                            'status'        => $txResult->status,
                        ]);

                        // Fire confirmed event if just reached required confirmations
                        $requiredConf = config("cryptogateway.drivers." . strtolower($wallet->coin) . ".confirmations", 1);

                        if ($oldConfirmations < $requiredConf && $txResult->confirmations >= $requiredConf) {
                            TransactionConfirmed::dispatch($existing, $txResult->coin, $txResult->confirmations);
                        }

                        $updatedCount++;
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("  ⚠ Failed to sync {$wallet->coin} wallet {$wallet->address}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info('');
        $this->info("✅ Sync complete: {$newCount} new, {$updatedCount} updated transactions.");

        return self::SUCCESS;
    }
}
