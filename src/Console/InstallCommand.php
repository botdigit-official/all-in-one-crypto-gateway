<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Install the CryptoGateway package.
 *
 * Publishes config, runs migrations, and generates a webhook secret.
 */
class InstallCommand extends Command
{
    protected $signature = 'cryptogateway:install
                            {--force : Overwrite existing config}
                            {--no-migrate : Skip running migrations}';

    protected $description = 'Install the CryptoGateway package (publish config, run migrations, generate webhook secret)';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════╗');
        $this->info('║     CryptoGateway — All-in-One Crypto Gateway    ║');
        $this->info('║         Installing...                            ║');
        $this->info('╚═══════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Publish config
        $this->info('📦 Publishing configuration...');
        $params = ['--tag' => 'cryptogateway-config'];
        if ($this->option('force')) {
            $params['--force'] = true;
        }
        $this->call('vendor:publish', $params);

        // 2. Run migrations
        if (! $this->option('no-migrate')) {
            $this->info('🗄️  Running migrations...');
            $this->call('migrate');
        }

        // 3. Generate webhook secret if not set
        $this->generateWebhookSecret();

        // 4. Summary
        $this->info('');
        $this->info('✅ CryptoGateway installed successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('  1. Configure your RPC endpoints in .env');
        $this->info('  2. Set CRYPTO_NETWORK=testnet (or mainnet)');
        $this->info('  3. Run: php artisan cryptogateway:health');
        $this->info('');
        $this->info('Quick start:');
        $this->info('  use Botdigit\CryptoGateway\Facades\CryptoGateway;');
        $this->info('  $balance = CryptoGateway::btc()->getBalance($address);');
        $this->info('');

        return self::SUCCESS;
    }

    protected function generateWebhookSecret(): void
    {
        $envPath = $this->laravel->basePath('.env');

        if (! file_exists($envPath)) {
            $this->warn('⚠️  .env file not found. Skipping webhook secret generation.');
            return;
        }

        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'CRYPTO_WEBHOOK_SECRET=') &&
            ! str_contains($envContent, 'CRYPTO_WEBHOOK_SECRET='.PHP_EOL) &&
            ! str_contains($envContent, 'CRYPTO_WEBHOOK_SECRET=""')) {
            $this->info('🔑 Webhook secret already set.');
            return;
        }

        $secret = Str::random(64);

        if (str_contains($envContent, 'CRYPTO_WEBHOOK_SECRET=')) {
            $envContent = preg_replace(
                '/^CRYPTO_WEBHOOK_SECRET=.*$/m',
                "CRYPTO_WEBHOOK_SECRET={$secret}",
                $envContent
            );
        } else {
            $envContent .= PHP_EOL . "CRYPTO_WEBHOOK_SECRET={$secret}" . PHP_EOL;
        }

        file_put_contents($envPath, $envContent);
        $this->info("🔑 Webhook secret generated and saved to .env");
    }
}
