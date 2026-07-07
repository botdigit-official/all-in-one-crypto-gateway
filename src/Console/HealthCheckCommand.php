<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Console;

use Botdigit\CryptoGateway\Facades\CryptoGateway;
use Illuminate\Console\Command;

/**
 * Check connectivity health for all configured blockchain nodes/APIs.
 */
class HealthCheckCommand extends Command
{
    protected $signature = 'cryptogateway:health';

    protected $description = 'Check connectivity to all configured blockchain nodes/APIs';

    public function handle(): int
    {
        $this->info('Checking connectivity to configured drivers...');
        $this->info('');

        $results = CryptoGateway::healthCheck();
        $rows    = [];
        $allOk   = true;

        foreach ($results as $alias => $connected) {
            $status = $connected ? '✅ Connected' : '❌ Failed';
            if (! $connected) {
                $allOk = false;
            }

            try {
                $driver  = CryptoGateway::driver($alias);
                $symbol  = $driver->getCoinSymbol();
                $network = $driver->getNetwork();
            } catch (\Throwable) {
                $symbol  = '?';
                $network = '?';
            }

            $rows[] = [$alias, $symbol, $network, $status];
        }

        $this->table(['Alias', 'Coin', 'Network', 'Status'], $rows);

        if ($allOk) {
            $this->info('');
            $this->info('✅ All drivers are connected!');
        } else {
            $this->info('');
            $this->warn('⚠️  Some drivers failed. Check your .env configuration and node connectivity.');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
