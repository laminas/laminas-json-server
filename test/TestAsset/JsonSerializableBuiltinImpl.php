<?php

declare(strict_types=1);

namespace LaminasTest\Json\Server\TestAsset;

use JsonSerializable;
use Override;

/**
 * Implementation of the built-in JsonSerializable interface.
 */
class JsonSerializableBuiltinImpl implements JsonSerializable
{
    #[Override]
    public function jsonSerialize(): array
    {
        return [__FUNCTION__];
    }
}
