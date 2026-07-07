<?php

use Botdigit\CryptoGateway\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CryptoGateway Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhook/IPN notifications from blockchain
| services. The {driver} parameter specifies which coin driver to use
| for processing the webhook (e.g., /cryptogateway/webhook/btc).
|
*/

$prefix     = config('cryptogateway.webhook.prefix', 'cryptogateway');
$middleware = config('cryptogateway.webhook.middleware', ['api']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        Route::post('/webhook/{driver}', [WebhookController::class, 'handle'])
            ->name('cryptogateway.webhook');
    });
