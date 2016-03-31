<?php
/**
 * @link      http://github.com/zendframework/zend-json-server for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Json\Server\TestAsset;

use Exception;

/**
 * Class for testing JSON-RPC server
 */
class Foo
{
    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @param  mixed $three
     * @return array
     */
    public function bar($one, $two = 'two', $three = null)
    {
        return [$one, $two, $three];
    }

    /**
     * Baz
     *
     * @return void
     */
    public function baz()
    {
        throw new Exception('application error');
    }
}
