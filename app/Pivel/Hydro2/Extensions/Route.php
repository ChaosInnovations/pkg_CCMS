<?php

namespace Pivel\Hydro2\Extensions;

use Attribute;
use Pivel\Hydro2\Models\HTTP\Method;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public Method $method,
        public string $path,
        public int $order=0
    ) {
    }
}