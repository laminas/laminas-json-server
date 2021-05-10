<?php

namespace LaminasTest\Json\Server\TestAsset;

use Laminas\Stdlib\JsonSerializable;

class JsonSerializableLaminasImpl implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [__FUNCTION__];
    }
}
