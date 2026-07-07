<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Exceptions;

/**
 * Thrown when a wallet has insufficient balance for a send operation.
 */
class InsufficientBalanceException extends CryptoGatewayException
{
}
