<?php

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
    public function bar($one, $two = 'two', $three = null): array
    {
        return [$one, $two, $three];
    }

    /**
     * Baz
     *
     * @return void
     */
    public function baz(): void
    {
        throw new Exception('application error');
    }
}
