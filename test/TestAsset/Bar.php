<?php

namespace LaminasTest\Json\Server\TestAsset;

use Exception;

class Bar
{
    protected $val;

    public function __construct($someval)
    {
        $this->val = $someval;
    }

    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @param  mixed $three
     * @return array
     */
    public function foo($one, $two = 'two', $three = null): array
    {
        return [$one, $two, $three, $this->val];
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
