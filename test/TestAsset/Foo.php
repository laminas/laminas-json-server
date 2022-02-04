<?php

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
    public function bar($one, $two = 'two', $three = null): array
    {
        return [$one, $two, $three];
    }

    /**
     * Baz
     */
    public function baz(): void
    {
        throw new Exception('application error');
    }
}
