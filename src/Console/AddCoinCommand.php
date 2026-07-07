<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Console;

use Illuminate\Console\Command;

/**
 * Interactive wizard to add a new coin driver to the config.
 */
class AddCoinCommand extends Command
{
    protected $signature = 'cryptogateway:add-coin
                            {coin? : The coin symbol (e.g., DOGE, XRP)}';

    protected $description = 'Add a new cryptocurrency driver to the configuration';

    public function handle(): int
    {
        $coin = $this->argument('coin') ?? $this->ask('Enter the coin symbol (e.g., DOGE, XRP)');
        $coin = strtolower(trim($coin));

        $this->info("Adding {$coin} driver configuration...");
        $this->info('');

        $driverType = $this->choice('Select driver type', [
            'Custom class (you will implement DriverInterface)',
            'JSON-RPC (Bitcoin-like UTXO chains)',
            'EVM-compatible (Ethereum-like chains)',
            'REST API (custom HTTP endpoint)',
        ], 0);

        $config = match ($driverType) {
            'JSON-RPC (Bitcoin-like UTXO chains)' => $this->buildJsonRpcConfig($coin),
            'EVM-compatible (Ethereum-like chains)' => $this->buildEvmConfig($coin),
            'REST API (custom HTTP endpoint)' => $this->buildRestConfig($coin),
            default => $this->buildCustomConfig($coin),
        };

        $this->info('');
        $this->info('Add the following to your config/cryptogateway.php drivers array:');
        $this->info('');
        $this->line("'{$coin}' => " . $this->formatArray($config));
        $this->info('');

        $this->info("Don't forget to add the corresponding .env variables!");

        return self::SUCCESS;
    }

    protected function buildJsonRpcConfig(string $coin): array
    {
        $coinUpper = strtoupper($coin);

        return [
            'driver'   => 'bitcoin',
            'host'     => "env('{$coinUpper}_RPC_HOST', 'http://127.0.0.1:8332')",
            'user'     => "env('{$coinUpper}_RPC_USER', '')",
            'password' => "env('{$coinUpper}_RPC_PASS', '')",
        ];
    }

    protected function buildEvmConfig(string $coin): array
    {
        $coinUpper = strtoupper($coin);

        return [
            'driver'   => 'ethereum',
            'rpc_url'  => "env('{$coinUpper}_RPC_URL', '')",
            'api_key'  => "env('{$coinUpper}_API_KEY')",
            'chain_id' => "env('{$coinUpper}_CHAIN_ID', 1)",
        ];
    }

    protected function buildRestConfig(string $coin): array
    {
        $coinUpper = strtoupper($coin);

        return [
            'driver'  => "App\\CryptoDrivers\\" . ucfirst($coin) . "Driver::class",
            'api_url' => "env('{$coinUpper}_API_URL', '')",
            'api_key' => "env('{$coinUpper}_API_KEY')",
        ];
    }

    protected function buildCustomConfig(string $coin): array
    {
        $coinUpper = strtoupper($coin);

        return [
            'driver'  => "App\\CryptoDrivers\\" . ucfirst($coin) . "Driver::class",
            'api_key' => "env('{$coinUpper}_API_KEY')",
        ];
    }

    protected function formatArray(array $array, int $indent = 1): string
    {
        $pad    = str_repeat('    ', $indent);
        $output = "[\n";

        foreach ($array as $key => $value) {
            $output .= "{$pad}'{$key}' => '{$value}',\n";
        }

        $output .= str_repeat('    ', $indent - 1) . '],';
        return $output;
    }
}
