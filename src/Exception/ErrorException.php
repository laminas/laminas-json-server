<?php

declare(strict_types=1);

namespace Laminas\Json\Server\Exception;

/**
 * Thrown by Laminas\Json\Server\Client when a JSON-RPC fault response is returned.
 */
class ErrorException extends BadMethodCallException implements ExceptionInterface
{
}
