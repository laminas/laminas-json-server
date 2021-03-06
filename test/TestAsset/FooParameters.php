<?php

/**
 * @see       https://github.com/laminas/laminas-json-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-json-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-json-server/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Json\Server\TestAsset;

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
    public function bar(bool $one, string $two): array
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
    public function baz(bool $one, string $two, string $three = "default"): array
    {
        return [$one, $two, $three];
    }
}
