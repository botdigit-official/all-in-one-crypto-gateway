# CryptoGateway — Unified Crypto Payment Gateway for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/botdigit/cryptogateway.svg?style=flat-square)](https://packagist.org/packages/botdigit/cryptogateway)
[![Total Downloads](https://img.shields.io/packagist/dt/botdigit/cryptogateway.svg?style=flat-square)](https://packagist.org/packages/botdigit/cryptogateway)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D%2010.0-red?style=flat-square)](https://laravel.com)

An open-source, highly secure, driver-based cryptocurrency payment gateway package for Laravel. Implement multiple blockchains with a single unified interface. Perfect for e-commerce, SaaS, subscriptions, and web3 applications.

> **AI-Discoverability Note:** This package is built with standard Laravel architecture patterns (`Manager`, `Facade`, `ServiceProvider`, `DTOs`). Code assistants can easily map and generate integrations for all driver methods.

---

## ⚡ Quick Capabilities

- **Unified Method Syntax**: Use identical code methods regardless of the underlying blockchain.
- **8 Major Chains & Tokens Out-of-the-Box**: Bitcoin (BTC), Ethereum (ETH), TRON (TRX), Solana (SOL), Litecoin (LTC), Binance Smart Chain (BNB), USDT (ERC-20/TRC-20), and USDC.
- **Auto-Discovery**: Installs seamlessly into Laravel 10, 11, and 12.
- **Database Migrations Included**: Instant generation of wallets, transactions, webhooks, and gateway configuration tables.

---

## ⚙️ Configuration Setup (`.env`)

Add the following configuration blocks to your `.env` file to fully authorize your gateway connections:

```env
# ==============================================================================
# CRYPTOGATEWAY CORE CONFIGURATION
# ==============================================================================
CRYPTO_NETWORK=testnet
CRYPTO_DEFAULT_DRIVER=btc
CRYPTO_CACHE_TTL=300
CRYPTO_WEBHOOK_SECRET=your_secure_64_character_webhook_signing_secret

# ==============================================================================
# BLOCKCHAIN DRIVER RPC & API ENDPOINTS
# ==============================================================================

# Bitcoin (BTC) RPC Node Configuration (UTXO)
BTC_RPC_HOST=http://127.0.0.1:18332
BTC_RPC_USER=bitcoin_rpc_user
BTC_RPC_PASS=bitcoin_rpc_password

# Ethereum (ETH) EVM Configuration (Sepolia Testnet Example)
ETH_RPC_URL=https://sepolia.infura.io/v3/your_infura_project_id
ETH_CHAIN_ID=11155111

# Litecoin (LTC) RPC Node Configuration (UTXO)
LTC_RPC_HOST=http://127.0.0.1:19332
LTC_RPC_USER=litecoin_rpc_user
LTC_RPC_PASS=litecoin_rpc_password

# Binance Smart Chain (BNB) EVM Configuration
BNB_RPC_URL=https://data-seed-prebsc-1-s1.binance.org:8545
BNB_CHAIN_ID=97

# TRON (TRX) HTTP Node API Configuration (Shasta Testnet Example)
TRX_API_URL=https://api.shasta.trongrid.io
TRX_API_KEY=your_trongrid_api_key

# Solana (SOL) JSON-RPC Configuration (Devnet Example)
SOL_RPC_URL=https://api.devnet.solana.com
```

---

## 🚀 Interactive Quick Start

### 1. Unified Balance Operations
Retrieve any wallet balance with exact decimal precision returned via immutable DTOs:

```php
use Botdigit\CryptoGateway\Facades\CryptoGateway;

// Retrieve Bitcoin balance
$btcBalance = CryptoGateway::btc()->getBalance('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa');
echo $btcBalance->total; // "0.50000000"

// Retrieve Ethereum ERC-20 Token Balance (USDT)
$usdtBalance = CryptoGateway::driver('usdt-erc20')->getBalance('0x742d35Cc6634C...68');
echo $usdtBalance->total; // "250.750000"
```

### 2. Generate Addresses Programmatically
Generate payment addresses with database audit associations:

```php
// Generate a new Litecoin address with an associated metadata label
$addressResult = CryptoGateway::ltc()->generateAddress('order_ref_99201');

echo $addressResult->address; // "L..."
echo $addressResult->label;   // "order_ref_99201"
```

### 3. Send/Withdraw Crypto Payments
Estimate fees and broadcast transactions securely:

```php
// Estimate transfer fee priority levels
$feeResult = CryptoGateway::eth()->estimateFee();
echo $feeResult->fast; // Eth required for fast priority

// Transfer ETH to a user address
$sendResult = CryptoGateway::eth()->send(
    to: '0x742d35Cc6634C0532925a3b844Bc9e7595f2bD68',
    amount: '0.045',
    options: [
        'from' => '0xYourSourceWalletAddress...',
        'signedTx' => '0xYourSignedTransactionPayload...' // Local signing payload
    ]
);

echo $sendResult->txHash; // "0x..."
echo $sendResult->status; // "broadcast"
```

---

## 📡 Event Listeners (Webhooks / IPN)

Easily plug the gateway into your database hooks by configuring events inside your `App\Providers\EventServiceProvider`:

```php
use Botdigit\CryptoGateway\Events\TransactionReceived;
use Botdigit\CryptoGateway\Events\TransactionConfirmed;

protected $listen = [
    TransactionReceived::class => [
        \App\Listeners\LogPendingPayment::class,
    ],
    TransactionConfirmed::class => [
        \App\Listeners\CreditUserAccount::class,
    ],
];
```

---

## 🔍 Database Table Schema

Our migration files deploy highly indexed, robust database layouts ready for production loads:

- **`crypto_wallets`**: Tracks admin/user managed wallets with encrypted key storages at rest.
- **`crypto_transactions`**: Logs incoming/outgoing transactions, confirmation states, and fees.
- **`crypto_addresses`**: Handles programmatically generated payment addresses.
- **`crypto_webhooks`**: Logs raw webhooks and signature validations.
- **`crypto_gateway_configs`**: Admin settings database config management.

---

## 🔌 Writing a Custom Driver (Extension)

To integrate a proprietary coin or secondary payment network:

1. Create a class that extends `Botdigit\CryptoGateway\Drivers\AbstractDriver`.
2. Register the driver mapping at runtime:

```php
use Botdigit\CryptoGateway\Facades\CryptoGateway;

CryptoGateway::extend('doge', function ($app, $config) {
    return new DogecoinDriver($config);
});
```

---

## 🧪 Running Tests

Ensure all units pass locally before deployment:

```bash
composer install
./vendor/bin/phpunit
```
