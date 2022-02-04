<?php

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
    public function bar($one, $two): array
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
    public function baz($one, $two, $three = "default"): array
    {
        return [$one, $two, $three];
    }
}
