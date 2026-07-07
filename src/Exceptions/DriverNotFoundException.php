<?php

declare(strict_types=1);

namespace Botdigit\CryptoGateway\Exceptions;

/**
 * Thrown when a requested coin driver is not found or not configured.
 */
class DriverNotFoundException extends CryptoGatewayException
{
}
