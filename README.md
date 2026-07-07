# CryptoGateway

**Open-source all-in-one cryptocurrency payment gateway for Laravel.**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2B-red)](https://laravel.com)

> One Facade. Every Coin. Zero Complexity.

CryptoGateway provides a **unified API** for all cryptocurrency operations — balances, payments, wallets, transactions — across 20+ blockchains. Install via Composer, run one Artisan command, and you're ready to accept crypto payments.

---

## ✨ Features

- **Unified API** — `CryptoGateway::btc()->getBalance($address)` — same API for every coin
- **8 Coins Built-in** — BTC, ETH, TRX, SOL, LTC, BNB, USDT (ERC-20 & TRC-20), USDC
- **Driver Architecture** — Add any coin with 1 class. No core changes.
- **Pre-built Migrations** — 5 tables ready to go
- **Security First** — AES-256 key encryption, HMAC webhooks, rate limiting, address validation
- **Laravel Native** — Events, Artisan commands, Facade, auto-discovery
- **AI-Discoverable** — Clean, predictable API that any AI agent can use instantly

---

## 📦 Installation

```bash
composer require botdigit/cryptogateway
```

```bash
php artisan cryptogateway:install
```

This publishes the config, runs migrations, and generates a webhook secret.

---

## 🚀 Quick Start

### Get a Balance

```php
use Botdigit\CryptoGateway\Facades\CryptoGateway;

$balance = CryptoGateway::btc()->getBalance('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa');

echo $balance->confirmed;    // "21000000.00000000"
echo $balance->unconfirmed;  // "0.00000000"
echo $balance->coin;         // "BTC"
```

### Generate an Address

```php
$address = CryptoGateway::eth()->generateAddress('order-123');

echo $address->address;  // "0x..."
echo $address->label;    // "order-123"
```

### Send Crypto

```php
$result = CryptoGateway::eth()->send(
    to: '0x742d35Cc6634C0532925a3b844Bc9e7595f2bD68',
    amount: '0.5',
    options: ['gasLimit' => 21000, 'signedTx' => $signedTransaction]
);

echo $result->txHash;  // "0xabc..."
echo $result->status;  // "broadcast"
```

### Estimate Fees

```php
$fee = CryptoGateway::btc()->estimateFee();

echo $fee->fast;    // "0.00012"
echo $fee->medium;  // "0.00008"
echo $fee->slow;    // "0.00004"
```

### Check All Drivers Health

```php
$health = CryptoGateway::healthCheck();
// ['btc' => true, 'eth' => true, 'trx' => false, ...]
```

### Magic Method Shortcuts

```php
CryptoGateway::btc()->getBalance($addr);   // Bitcoin
CryptoGateway::eth()->getBalance($addr);   // Ethereum
CryptoGateway::trx()->getBalance($addr);   // TRON
CryptoGateway::sol()->getBalance($addr);   // Solana
CryptoGateway::ltc()->getBalance($addr);   // Litecoin
CryptoGateway::bnb()->getBalance($addr);   // BNB (BSC)
```

### Token Support

```php
// USDT on TRON
$balance = CryptoGateway::driver('usdt-trc20')->getBalance($address);

// USDC on Ethereum
$balance = CryptoGateway::driver('usdc-erc20')->getBalance($address);
```

---

## ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=cryptogateway-config
```

Add your RPC credentials to `.env`:

```env
CRYPTO_NETWORK=testnet
CRYPTO_DEFAULT_DRIVER=btc

# Bitcoin
BTC_RPC_HOST=http://127.0.0.1:8332
BTC_RPC_USER=bitcoin
BTC_RPC_PASS=your_password

# Ethereum (Infura, Alchemy, etc.)
ETH_RPC_URL=https://mainnet.infura.io/v3/YOUR_KEY
ETH_CHAIN_ID=1

# TRON
TRX_API_URL=https://api.trongrid.io
TRX_API_KEY=your_trongrid_key

# Solana
SOL_RPC_URL=https://api.mainnet-beta.solana.com

# Webhook Security
CRYPTO_WEBHOOK_SECRET=your_64_char_secret
```

---

## 📡 Events

Listen for blockchain events in your Laravel app:

```php
// In EventServiceProvider
protected $listen = [
    \Botdigit\CryptoGateway\Events\TransactionReceived::class => [
        \App\Listeners\HandleNewPayment::class,
    ],
    \Botdigit\CryptoGateway\Events\TransactionConfirmed::class => [
        \App\Listeners\ProcessConfirmedPayment::class,
    ],
];
```

Available events:
- `TransactionReceived` — New incoming transaction detected
- `TransactionConfirmed` — Transaction reached required confirmations
- `TransactionFailed` — Transaction failed/rejected
- `WebhookReceived` — Webhook notification received
- `BalanceChanged` — Wallet balance changed

---

## 🔧 Artisan Commands

| Command | Description |
|---------|-------------|
| `cryptogateway:install` | Install package (config, migrations, webhook secret) |
| `cryptogateway:health` | Check connectivity to all nodes/APIs |
| `cryptogateway:balances --coin=btc --address=...` | Check balances |
| `cryptogateway:sync` | Sync transactions from blockchain |
| `cryptogateway:add-coin` | Interactive wizard to add a new coin |

---

## 🔌 Custom Drivers

Add support for any coin with a single class:

```php
// app/CryptoDrivers/DogecoinDriver.php

namespace App\CryptoDrivers;

use Botdigit\CryptoGateway\Drivers\AbstractDriver;

class DogecoinDriver extends AbstractDriver
{
    public function getCoinSymbol(): string { return 'DOGE'; }
    public function getDecimals(): int { return 8; }

    public function getBalance(string $address): BalanceResult
    {
        // Your implementation here
    }

    // ... implement remaining DriverInterface methods
}
```

Register in config:

```php
// config/cryptogateway.php → drivers
'doge' => [
    'driver' => \App\CryptoDrivers\DogecoinDriver::class,
    'api_key' => env('DOGE_API_KEY'),
],
```

Or register at runtime:

```php
CryptoGateway::extend('doge', function ($app, $config) {
    return new DogecoinDriver($config);
});
```

---

## 🔒 Security

- **Key Encryption** — Private keys encrypted with AES-256-CBC before storage
- **HMAC Webhooks** — SHA-256 signature verification on all webhook endpoints
- **Rate Limiting** — Configurable per-minute request limits
- **Address Validation** — Format validation for 10+ coins before any operation
- **No Key Exposure** — `toArray()` never includes private keys

---

## 🗄️ Database

Pre-built migrations create 5 tables:

| Table | Purpose |
|-------|---------|
| `crypto_wallets` | Managed wallets with encrypted keys |
| `crypto_transactions` | Transaction history and confirmation tracking |
| `crypto_addresses` | Generated receiving addresses |
| `crypto_webhooks` | Webhook audit log |
| `crypto_gateway_configs` | Admin-managed per-coin settings |

---

## 🧪 Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

Built with ❤️ by [Botdigit](https://botdigit.com)
