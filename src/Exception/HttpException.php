<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Json\Server\Exception;

/**
 * Thrown by Laminas\Json\Server\Client when an HTTP error occurs during a
 * JSON-RPC method call.
 */
class HttpException extends RuntimeException
{
}
