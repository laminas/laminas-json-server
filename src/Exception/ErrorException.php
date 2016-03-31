<?php
/**
 * @link      http://github.com/zendframework/zend-json-server for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Json\Server\Exception;

/**
 * Thrown by Zend\Json\Server\Client when a JSON-RPC fault response is returned.
 */
class ErrorException extends BadMethodCallException implements ExceptionInterface
{
}
