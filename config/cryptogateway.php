<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The default coin driver to use when calling CryptoGateway methods
    | without specifying a driver. Example: CryptoGateway::getBalance(...)
    |
    */
    'default' => env('CRYPTO_DEFAULT_DRIVER', 'btc'),

    /*
    |--------------------------------------------------------------------------
    | Network Mode
    |--------------------------------------------------------------------------
    |
    | Global network mode. Each driver can override this individually.
    | Options: 'mainnet', 'testnet'
    |
    */
    'network' => env('CRYPTO_NETWORK', 'testnet'),

    /*
    |--------------------------------------------------------------------------
    | Drivers Configuration
    |--------------------------------------------------------------------------
    |
    | Each key is a driver alias used like: CryptoGateway::driver('btc')
    | The 'driver' value maps to a registered driver class or built-in name.
    |
    */
    'drivers' => [

        'btc' => [
            'driver'        => 'bitcoin',
            'host'          => env('BTC_RPC_HOST', 'http://127.0.0.1:8332'),
            'user'          => env('BTC_RPC_USER', 'bitcoin'),
            'password'      => env('BTC_RPC_PASS', ''),
            'provider'      => env('BTC_PROVIDER'),      // 'getblock', 'chainstack', null (self-hosted)
            'api_key'       => env('BTC_API_KEY'),
            'confirmations' => 3,
            'network'       => null,                      // null = use global
        ],

        'eth' => [
            'driver'   => 'ethereum',
            'rpc_url'  => env('ETH_RPC_URL', 'https://mainnet.infura.io/v3/YOUR_KEY'),
            'api_key'  => env('ETH_API_KEY'),
            'chain_id' => env('ETH_CHAIN_ID', 1),
            'network'  => null,
        ],

        'trx' => [
            'driver'  => 'tron',
            'api_url' => env('TRX_API_URL', 'https://api.trongrid.io'),
            'api_key' => env('TRX_API_KEY'),
            'network' => null,
        ],

        'sol' => [
            'driver'  => 'solana',
            'rpc_url' => env('SOL_RPC_URL', 'https://api.mainnet-beta.solana.com'),
            'api_key' => env('SOL_API_KEY'),
            'network' => null,
        ],

        'ltc' => [
            'driver'   => 'litecoin',
            'host'     => env('LTC_RPC_HOST', 'http://127.0.0.1:9332'),
            'user'     => env('LTC_RPC_USER', 'litecoin'),
            'password' => env('LTC_RPC_PASS', ''),
            'network'  => null,
        ],

        'bnb' => [
            'driver'   => 'bnb',
            'rpc_url'  => env('BNB_RPC_URL', 'https://bsc-dataseed.binance.org'),
            'api_key'  => env('BNB_API_KEY'),
            'chain_id' => 56,
            'network'  => null,
        ],

        'usdt-erc20' => [
            'driver'           => 'erc20',
            'parent_driver'    => 'eth',
            'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
            'decimals'         => 6,
            'symbol'           => 'USDT',
        ],

        'usdt-trc20' => [
            'driver'           => 'trc20',
            'parent_driver'    => 'trx',
            'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'decimals'         => 6,
            'symbol'           => 'USDT',
        ],

        'usdc-erc20' => [
            'driver'           => 'erc20',
            'parent_driver'    => 'eth',
            'contract_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            'decimals'         => 6,
            'symbol'           => 'USDC',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_keys'      => true,
        'encryption_cipher' => 'AES-256-CBC',
        'webhook_secret'    => env('CRYPTO_WEBHOOK_SECRET'),
        'rate_limit'        => 60, // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection'   => env('CRYPTO_DB_CONNECTION'),  // null = default
        'table_prefix' => 'crypto_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Routes
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'enabled'    => true,
        'prefix'     => 'cryptogateway',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('CRYPTO_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl'     => 30, // seconds — balance cache lifetime
        'driver'  => env('CRYPTO_CACHE_DRIVER'),  // null = default
    ],
];
