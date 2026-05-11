<?php

declare(strict_types=1);

namespace infrastructure\presentation\http\attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Route
{
    public function __construct(
        public string $method,
        public string $path
    ) {
    }
}
