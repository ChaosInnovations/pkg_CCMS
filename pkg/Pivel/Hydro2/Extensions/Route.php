<?php

namespace Package\Pivel\Hydro2\Extensions;

use Attribute;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Route {
    public Method $method;
    public string $path;
    public string $order;

    public function __construct(Method $method, string $path, int $order=0)
    {
        $this->method = $method;
        $this->path = $path;
        $this->order = $order;
    }
}