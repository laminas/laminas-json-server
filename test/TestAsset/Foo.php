<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Json\Server\TestAsset;

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
    public function bar(bool $one, string $two = 'two', $three = null) : array
    {
        return [$one, $two, $three];
    }

    /**
     * Baz
     *
     * @return void
     */
    public function baz() : void
    {
        throw new Exception('application error');
    }
}
