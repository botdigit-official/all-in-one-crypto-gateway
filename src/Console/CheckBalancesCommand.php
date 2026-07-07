<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Console;

use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Illuminate\Console\Command;

/**
 * Display balances for all configured wallets.
 */
class CheckBalancesCommand extends Command
{
    protected $signature = 'cryptogateway:balances
                            {--coin= : Check balance for a specific coin only}
                            {--address= : Check balance for a specific address}';

    protected $description = 'Show cryptocurrency balances for configured wallets';

    public function handle(): int
    {
        $coin    = $this->option('coin');
        $address = $this->option('address');

        if ($coin && $address) {
            return $this->checkSingleBalance($coin, $address);
        }

        return $this->checkAllBalances();
    }

    protected function checkSingleBalance(string $coin, string $address): int
    {
        $this->info("Checking {$coin} balance for {$address}...");

        try {
            $balance = CryptoGateway::driver($coin)->getBalance($address);

            $this->table(
                ['Coin', 'Address', 'Confirmed', 'Unconfirmed', 'Total'],
                [[$balance->coin, $balance->address, $balance->confirmed, $balance->unconfirmed, $balance->total]]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to check balance: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    protected function checkAllBalances(): int
    {
        $drivers = CryptoGateway::configuredDrivers();
        $rows    = [];

        $this->info('Checking balances across all configured drivers...');
        $this->info('');

        $bar = $this->output->createProgressBar(count($drivers));

        foreach ($drivers as $alias) {
            try {
                $driver  = CryptoGateway::driver($alias);
                $symbol  = $driver->getCoinSymbol();
                $network = $driver->getNetwork();

                $rows[] = [
                    $alias,
                    $symbol,
                    $network,
                    $driver->isConnected() ? '✅ Connected' : '❌ Disconnected',
                ];
            } catch (\Throwable $e) {
                $rows[] = [$alias, '?', '?', "❌ Error: {$e->getMessage()}"];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info('');
        $this->info('');

        $this->table(
            ['Alias', 'Coin', 'Network', 'Status'],
            $rows
        );

        $this->info('');
        $this->info('To check a specific balance, use:');
        $this->info('  php artisan cryptogateway:balances --coin=btc --address=YOUR_ADDRESS');

        return self::SUCCESS;
    }
}
