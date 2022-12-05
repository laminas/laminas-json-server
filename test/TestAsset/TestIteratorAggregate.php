<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server\TestAsset;

use ArrayIterator;
use IteratorAggregate;

/**
 * @see Laminas-12347
 * @template-implements IteratorAggregate<non-empty-string, non-empty-string|positive-int>
 */
class TestIteratorAggregate implements IteratorAggregate
{
    protected array $array = [
        'foo' => 'bar',
        'baz' => 5,
    ];

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->array);
    }
}
