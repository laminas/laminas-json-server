<?php

namespace LaminasTest\Json\Server\TestAsset;

use JsonSerializable;

/**
 * Implementation of the built-in JsonSerializable interface.
 */
class JsonSerializableBuiltinImpl implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [__FUNCTION__];
    }
}
