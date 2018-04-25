<?php
/**
 * @link      http://github.com/zendframework/zend-json-server for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Json\Server\TestAsset;

/**
 * Class for testing JSON-RPC server
 */
class FooParameters
{
    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @return array
     */
    public function bar($one, $two)
    {
        return [$one, $two];
    }

    /**
     * Baz
     *
     * @param  bool $one
     * @param  string $two
     * @param  string $three
     * @return array
     */
    public function baz($one, $two, $three = "default")
    {
        return [$one, $two, $three];
    }
}
