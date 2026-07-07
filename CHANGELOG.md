# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Core `GatewayManager` with driver-based architecture
- `CryptoGateway` Facade with magic method shortcuts
- Built-in drivers: Bitcoin, Ethereum, TRON, Solana, Litecoin, BNB
- Token drivers: ERC-20, TRC-20 (USDT, USDC)
- RPC/API adapters: JSON-RPC, Web3, TRON HTTP, Solana RPC
- 5 pre-built database migrations (wallets, transactions, addresses, webhooks, configs)
- 5 Eloquent models with scopes and helpers
- Security layer: KeyEncryptor, AddressValidator, HMAC webhook verification
- 5 Laravel events: TransactionReceived, TransactionConfirmed, TransactionFailed, WebhookReceived, BalanceChanged
- Webhook controller with HMAC-SHA256 signature verification
- Rate limiting middleware
- 5 Artisan commands: install, add-coin, balances, sync, health
- Immutable DTOs: BalanceResult, TransactionResult, AddressResult, SendResult, EstimateFeeResult
- Helpers: CoinFormatter, NetworkResolver
- Custom driver support via `extend()` and class-based config
- Full test suite with Orchestra Testbench
- Comprehensive README documentation
